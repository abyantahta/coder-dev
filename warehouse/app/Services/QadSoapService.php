<?php

namespace App\Services;

use App\Models\DepartmentQadConfig;
use App\Models\PurchaseRequest;
use App\Models\QadSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QadSoapService
{
    private string $url;
    private string $username;
    private string $password;
    private int    $timeout;
    private string $wsaUrl;
    private string $wsaNamespace;

    public function __construct()
    {
        // URL QXI/WSA bisa di-override dari layar Setting QAD (kolom qad_settings)
        // supaya ganti port/host QAD tidak perlu edit .env + deploy — kosong = pakai .env.
        $setting = QadSetting::current();

        $this->url          = $setting->qad_soap_url ?: config('services.qad_soap.url', '');
        $this->username     = config('services.qad_soap.username', '');
        $this->password     = config('services.qad_soap.password', '');
        $this->timeout      = (int) config('services.qad_soap.timeout', 30);
        $this->wsaUrl       = $setting->qad_wsa_url ?: config('services.qad_soap.wsa_url', '');
        $this->wsaNamespace = config('services.qad_soap.wsa_namespace', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->url) && !empty($this->username);
    }

    public function isWsaConfigured(): bool
    {
        return !empty($this->wsaUrl) && !empty($this->wsaNamespace);
    }

    /**
     * Kirim satu PurchaseRequest (source=ga_aggregation, sudah department_id-nya
     * terisi) sebagai satu requisition ke QAD via SOAP (action SDI_CreatePR).
     */
    public function createRequisition(PurchaseRequest $pr): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'QAD SOAP belum dikonfigurasi', 'qad_pr_no' => null];
        }

        $config = DepartmentQadConfig::where('department_id', $pr->department_id)->first();
        if (!$config || !$config->site_code) {
            return ['success' => false, 'message' => 'Konfigurasi QAD (site/buyer/approver) untuk departemen ini belum diisi di Master Data.', 'qad_pr_no' => null];
        }

        $pr->loadMissing('details.procurementItem.uom', 'creator');
        $isUpdate = !empty($pr->qad_req_no);

        $xml = $this->buildRequisitionXml($pr, $config);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => ''])
                ->timeout($this->timeout)
                ->post($this->url);

            $body = $response->body();

            if (!$response->successful()) {
                Log::error('QAD SOAP createRequisition HTTP error', ['status' => $response->status(), 'body' => $body]);
                return ['success' => false, 'message' => "QAD HTTP error: {$response->status()}", 'qad_pr_no' => null, 'raw_response' => $body];
            }

            if (stripos($body, '<soapenv:Fault') !== false || stripos($body, ':Fault>') !== false) {
                Log::error('QAD SOAP createRequisition Fault', ['body' => $body]);
                return ['success' => false, 'message' => 'QAD mengembalikan SOAP Fault — cek raw response.', 'qad_pr_no' => null, 'raw_response' => $body];
            }

            $result   = $this->extractResult($body);
            $qadPrNo  = $this->extractPrNumber($body);
            $warnings = $this->extractWarnings($body);

            // QAD: result 'success' atau 'warning' berarti requisition TETAP dibuat
            // (no. PR sudah ada) — 'warning' cuma catatan (mis. item tidak dikenal,
            // jadi baris di-set jadi tipe Memo). 'error' berarti gagal total.
            if ($result === 'error') {
                Log::error('QAD SOAP createRequisition error result', ['body' => $body]);
                return ['success' => false, 'message' => $warnings ?: 'QAD mengembalikan status error.', 'qad_pr_no' => $qadPrNo, 'raw_response' => $body];
            }

            $verb = $isUpdate ? 'diperbarui' : 'dibuat';
            $message = $result === 'warning' && $warnings
                ? "Requisition {$verb} dengan catatan: {$warnings}"
                : "Requisition berhasil {$verb} di QAD.";

            return [
                'success'      => true,
                'message'      => $message,
                'qad_pr_no'    => $qadPrNo,
                'raw_response' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('QAD SOAP createRequisition exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qad_pr_no' => null];
        }
    }

    /**
     * Kirim keputusan approve/deny/reverse Direktur ke QAD (action Approval_PR,
     * operasi maintainRequisitionApproval). $action: '1'=Approve, '2'=Deny, '3'=Reverse.
     */
    public function approveRequisition(string $rqmNbr, string $approverCode, ?string $approverPassword, string $action, ?string $comment = null, ?string $buyerCode = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'QAD SOAP belum dikonfigurasi'];
        }
        if (empty($approverCode)) {
            return ['success' => false, 'message' => 'Kode approver QAD belum diisi untuk user ini.'];
        }
        if (empty($approverPassword)) {
            return ['success' => false, 'message' => 'Password QAD belum diisi untuk user ini. QAD mewajibkan login sebagai approver asli untuk approve/deny.'];
        }

        $xml = $this->buildApprovalXml($rqmNbr, $approverCode, $approverPassword, $action, $comment, $buyerCode);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => ''])
                ->timeout($this->timeout)
                ->post($this->url);

            $body = $response->body();

            if (!$response->successful()) {
                Log::error('QAD SOAP approveRequisition HTTP error', ['status' => $response->status(), 'body' => $body]);
                return ['success' => false, 'message' => "QAD HTTP error: {$response->status()}", 'raw_response' => $body];
            }

            if (stripos($body, '<soapenv:Fault') !== false || stripos($body, ':Fault>') !== false) {
                Log::error('QAD SOAP approveRequisition Fault', ['body' => $body]);
                return ['success' => false, 'message' => 'QAD mengembalikan SOAP Fault — cek raw response.', 'raw_response' => $body];
            }

            $result   = $this->extractResult($body);
            $warnings = $this->extractWarnings($body);

            if ($result === 'error') {
                Log::error('QAD SOAP approveRequisition error result', ['body' => $body]);
                return ['success' => false, 'message' => $warnings ?: 'QAD mengembalikan status error.', 'raw_response' => $body];
            }

            $actionLabel = match($action) { '1' => 'Approve', '2' => 'Deny', '3' => 'Reverse', default => $action };
            $message = $result === 'warning' && $warnings
                ? "{$actionLabel} terkirim dengan catatan: {$warnings}"
                : "{$actionLabel} berhasil dikirim ke QAD.";

            return ['success' => true, 'message' => $message, 'raw_response' => $body];
        } catch (\Exception $e) {
            Log::error('QAD SOAP approveRequisition exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildApprovalXml(string $rqmNbr, string $approverCode, string $approverPassword, string $action, ?string $comment, ?string $buyerCode = null): string
    {
        $rqmNbrEsc  = $this->esc($rqmNbr);
        $approverEsc = $this->esc($approverCode);
        $actionEsc  = $this->esc($action);

        $commentXml = '';
        if ($comment) {
            $commentEsc = $this->esc($comment);
            $commentXml = <<<XML

                        <ttRequisitionApprovalComment>
                            <RequisitionNumber>{$rqmNbrEsc}</RequisitionNumber>
                            <cmtSeq>1</cmtSeq>
                            <cmtCmmt>{$commentEsc}</cmtCmmt>
                        </ttRequisitionApprovalComment>
XML;
        }

        // Sesuai alur manual QAD: setelah Approve (Action=1), transaksi juga
        // butuh info "Route To" (siapa buyer tujuan) di request yang sama.
        $routeXml = '';
        if ($action === '1' && $buyerCode) {
            $buyerEsc = $this->esc($buyerCode);
            $routeXml = <<<XML

                        <ttRequisitionApprovalRoute>
                            <RequisitionNumber>{$rqmNbrEsc}</RequisitionNumber>
                            <RouteToPurchasing>true</RouteToPurchasing>
                            <Buyer>{$buyerEsc}</Buyer>
                        </ttRequisitionApprovalRoute>
XML;
        }

        return <<<XML
<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
    <soapenv:Header>
        <wsa:Action/>
        <wsa:To>urn:services-qad-com:Approval_PR</wsa:To>
        <wsa:MessageID>urn:services-qad-com::Approval_PR</wsa:MessageID>
        <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
        </wsa:ReferenceParameters>
        <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
        </wsa:ReplyTo>
    </soapenv:Header>
    <soapenv:Body>
        <maintainRequisitionApproval>
            <qcom:dsSessionContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>domain</qcom:propertyName>
                    <qcom:propertyValue>7000</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>receiver</qcom:propertyName>
                    <qcom:propertyValue>Approval_PR</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>scopeTransaction</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>version</qcom:propertyName>
                    <qcom:propertyValue>ERP3_1</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>username</qcom:propertyName>
                    <qcom:propertyValue>{$approverEsc}</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>password</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($approverPassword)}</qcom:propertyValue>
                </qcom:ttContext>
            </qcom:dsSessionContext>
            <dsRequisitionApproval>
                <ttRequisitionApproval>
                    <RequisitionNumber>{$rqmNbrEsc}</RequisitionNumber>
                    <Approver>{$approverEsc}</Approver>
                    <Action>{$actionEsc}</Action>{$commentXml}{$routeXml}
                </ttRequisitionApproval>
            </dsRequisitionApproval>
        </maintainRequisitionApproval>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    /**
     * Catat penerimaan barang terhadap PO QAD (action SDI_eKanbanGR,
     * operasi receivePurchaseOrder). $lines: [['line' => int, 'qty' => float], ...]
     * — nomor line PO diasumsikan sama urutannya dengan baris requisition asal.
     */
    public function receivePurchaseOrder(string $poNumber, array $lines): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'QAD SOAP belum dikonfigurasi'];
        }
        if (empty($poNumber)) {
            return ['success' => false, 'message' => 'Nomor PO QAD belum diisi.'];
        }
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Tidak ada baris item untuk diterima.'];
        }

        $xml = $this->buildReceivePurchaseOrderXml($poNumber, $lines);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => ''])
                ->timeout($this->timeout)
                ->post($this->url);

            $body = $response->body();

            if (!$response->successful()) {
                Log::error('QAD SOAP receivePurchaseOrder HTTP error', ['status' => $response->status(), 'body' => $body]);
                return ['success' => false, 'message' => "QAD HTTP error: {$response->status()}", 'raw_response' => $body];
            }

            if (stripos($body, '<soapenv:Fault') !== false || stripos($body, ':Fault>') !== false) {
                Log::error('QAD SOAP receivePurchaseOrder Fault', ['body' => $body]);
                return ['success' => false, 'message' => 'QAD mengembalikan SOAP Fault — cek raw response.', 'raw_response' => $body];
            }

            $result   = $this->extractResult($body);
            $warnings = $this->extractWarnings($body);

            if ($result === 'error') {
                Log::error('QAD SOAP receivePurchaseOrder error result', ['body' => $body]);
                return ['success' => false, 'message' => $warnings ?: 'QAD mengembalikan status error.', 'raw_response' => $body];
            }

            $message = $result === 'warning' && $warnings
                ? "Penerimaan PO tercatat dengan catatan: {$warnings}"
                : "Penerimaan PO berhasil dicatat di QAD.";

            // Selalu log walau "sukses" — respons QAD pernah result=success tanpa
            // dsExceptions padahal qty tidak benar-benar ter-posting, jadi raw
            // response perlu tersimpan buat investigasi tanpa perlu re-test manual.
            Log::info('QAD SOAP receivePurchaseOrder response', [
                'po_no' => $poNumber,
                'lines' => $lines,
                'result' => $result,
                'warnings' => $warnings,
                'body' => $body,
            ]);

            return ['success' => true, 'message' => $message, 'raw_response' => $body];
        } catch (\Exception $e) {
            Log::error('QAD SOAP receivePurchaseOrder exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildReceivePurchaseOrderXml(string $poNumber, array $lines): string
    {
        $poEsc = $this->esc($poNumber);
        $today = now()->format('Y-m-d');

        $lineXml = '';
        foreach ($lines as $line) {
            $lineNo = (int) $line['line'];
            $qtyEsc = $this->esc((string) $line['qty']);
            $umXml  = !empty($line['um']) ? '<receiptUm>' . $this->esc($line['um']) . '</receiptUm>' : '';
            $lineXml .= <<<XML

                        <lineDetail>
                            <ordernum>{$poEsc}</ordernum>
                            <line>{$lineNo}</line>{$umXml}
                            <receiptDetail>
                                <ordernum>{$poEsc}</ordernum>
                                <line>{$lineNo}</line>
                                <lotserialQty>{$qtyEsc}</lotserialQty>
                            </receiptDetail>
                        </lineDetail>
XML;
        }

        return <<<XML
<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
    <soapenv:Header>
        <wsa:Action/>
        <wsa:To>urn:services-qad-com:SDI_eKanbanGR</wsa:To>
        <wsa:MessageID>urn:services-qad-com::SDI_eKanbanGR</wsa:MessageID>
        <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
        </wsa:ReferenceParameters>
        <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
        </wsa:ReplyTo>
    </soapenv:Header>
    <soapenv:Body>
        <receivePurchaseOrder>
            <qcom:dsSessionContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>domain</qcom:propertyName>
                    <qcom:propertyValue>7000</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>receiver</qcom:propertyName>
                    <qcom:propertyValue>SDI_eKanbanGR</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>scopeTransaction</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>version</qcom:propertyName>
                    <qcom:propertyValue>ERP3_3</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>username</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($this->username)}</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>password</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($this->password)}</qcom:propertyValue>
                </qcom:ttContext>
            </qcom:dsSessionContext>
            <dsPurchaseOrderReceive>
                <purchaseOrderReceive>
                    <operation>A</operation>
                    <ordernum>{$poEsc}</ordernum>
                    <effDate>{$today}</effDate>
                    <move>true</move>
                    <fillAll>true</fillAll>{$lineXml}
                </purchaseOrderReceive>
            </dsPurchaseOrderReceive>
        </receivePurchaseOrder>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    /**
     * Cari PO yang dibuat dari nomor requisition tertentu lewat WSA
     * (Web Service Adapter, bukan QXtend Outbound — operasi SDI_getPRtoPO_,
     * program QAD com/qad/.../SDI_getPRtoPO.p) — dipakai buat sinkron
     * qad_po_no otomatis tanpa GA perlu input manual. Return null kalau
     * belum ada PO atau requisition-nya tidak ketemu.
     */
    public function findPurchaseOrderByRequisition(string $rqmNbr): ?array
    {
        if (!$this->isWsaConfigured()) {
            return null;
        }

        $xml = $this->buildPRtoPOXml($rqmNbr);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => '""'])
                ->timeout($this->timeout)
                ->post($this->wsaUrl);

            $body = $response->body();

            if (!$response->successful() || stripos($body, ':Fault>') !== false) {
                Log::error('QAD WSA findPurchaseOrderByRequisition gagal', ['rqmNbr' => $rqmNbr, 'body' => $body]);
                return null;
            }

            if (preg_match('/<opOK>([^<]*)<\/opOK>/i', $body, $m) && strtolower(trim($m[1])) !== 'true') {
                return null;
            }

            if (!preg_match_all('/<ttPRtoPORow>(.*?)<\/ttPRtoPORow>/is', $body, $rows)) {
                return null;
            }

            // Semua baris (per line item) requisition biasanya jadi 1 PO yang
            // sama — ambil PONbr pertama yang tidak kosong DAN benar-benar
            // berformat nomor PO (awalan "PO"). Pernah ketemu kasus PONbr
            // ke-isi sama persis dengan nomor requisition (mis. "PR3886") —
            // itu artefak "routed ke purchasing" sebelum PO asli dibuat,
            // bukan PO sungguhan, jadi harus ditolak di sini.
            foreach ($rows[1] as $row) {
                if (preg_match('/<PONbr>([^<]+)<\/PONbr>/i', $row, $m)) {
                    $poNo = trim($m[1]);
                    if ($poNo !== '' && stripos($poNo, 'PO') === 0 && strcasecmp($poNo, $rqmNbr) !== 0) {
                        $status = preg_match('/<POStatus>([^<]*)<\/POStatus>/i', $row, $sm) ? trim($sm[1]) : null;
                        return ['po_no' => $poNo, 'po_status' => $status, 'raw_response' => $body];
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('QAD WSA findPurchaseOrderByRequisition exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil qty received per baris requisition (key = ReqLine) langsung dari
     * QAD lewat WSA — dipakai buat VALIDASI NYATA setelah receivePurchaseOrder,
     * karena QAD pernah terbukti balas result=success padahal Quantity
     * Received di PO tetap 0 (silent no-op). Return [] kalau gagal/tidak ketemu.
     *
     * @return array<int, float> line number => qty received
     */
    public function getReceivedQtyByLine(string $rqmNbr): array
    {
        if (!$this->isWsaConfigured()) {
            return [];
        }

        $xml = $this->buildPRtoPOXml($rqmNbr);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => '""'])
                ->timeout($this->timeout)
                ->post($this->wsaUrl);

            $body = $response->body();

            if (!$response->successful() || stripos($body, ':Fault>') !== false) {
                Log::error('QAD WSA getReceivedQtyByLine gagal', ['rqmNbr' => $rqmNbr, 'body' => $body]);
                return [];
            }

            if (!preg_match_all('/<ttPRtoPORow>(.*?)<\/ttPRtoPORow>/is', $body, $rows)) {
                return [];
            }

            $result = [];
            foreach ($rows[1] as $row) {
                if (!preg_match('/<ReqLine>([^<]+)<\/ReqLine>/i', $row, $lineM)) {
                    continue;
                }
                $line = (int) trim($lineM[1]);
                $qty  = preg_match('/<POQtyReceived>([^<]*)<\/POQtyReceived>/i', $row, $qtyM)
                    ? (float) trim($qtyM[1])
                    : 0.0;
                $result[$line] = $qty;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('QAD WSA getReceivedQtyByLine exception: ' . $e->getMessage());
            return [];
        }
    }

    private function buildPRtoPOXml(string $rqmNbr): string
    {
        $nsEsc  = $this->esc($this->wsaNamespace);
        $reqEsc = $this->esc($rqmNbr);

        // Filter tanggal WSA ini WAJIB diisi dan sifatnya AND terhadap
        // ipReqNbr (bukan OR) — jadi kirim rentang lebar biar requisition
        // manapun tetap ketemu walau need_date-nya di luar dugaan.
        $from = now()->subYears(2);
        $to   = now()->addYear();

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="{$nsEsc}">
    <soapenv:Header/>
    <soapenv:Body>
        <wsa:SDI_getPRtoPO_>
            <wsa:ipDomain>7000</wsa:ipDomain>
            <wsa:ipReqNbr>{$reqEsc}</wsa:ipReqNbr>
            <wsa:ipMonthFrom>{$from->month}</wsa:ipMonthFrom>
            <wsa:ipDayFrom>{$from->day}</wsa:ipDayFrom>
            <wsa:ipYearFrom>{$from->year}</wsa:ipYearFrom>
            <wsa:ipMonthTo>{$to->month}</wsa:ipMonthTo>
            <wsa:ipDayTo>{$to->day}</wsa:ipDayTo>
            <wsa:ipYearTo>{$to->year}</wsa:ipYearTo>
            <wsa:ipPOStatus>ALL</wsa:ipPOStatus>
            <wsa:ipMaxRows>10</wsa:ipMaxRows>
        </wsa:SDI_getPRtoPO_>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function extractResult(string $body): ?string
    {
        if (preg_match('/<(?:\w+:)?result>([^<]+)<\/(?:\w+:)?result>/i', $body, $m)) {
            return strtolower(trim($m[1]));
        }
        return null;
    }

    /** No. requisition QAD — ada di dsRequisitionResponse/requisition/rqmNbr saat suppressResponseDetail=false. */
    private function extractPrNumber(string $body): ?string
    {
        if (preg_match('/<(?:\w+:)?rqmNbr>([^<]+)<\/(?:\w+:)?rqmNbr>/i', $body, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /** Gabungkan tt_msg_desc + tt_msg_field (kalau ada) jadi satu string ringkas per pesan. */
    private function extractWarnings(string $body): ?string
    {
        if (!preg_match_all('/<(?:\w+:)?tt_msg_desc>([^<]*)<\/(?:\w+:)?tt_msg_desc>/i', $body, $descMatches)) {
            return null;
        }
        preg_match_all('/<(?:\w+:)?tt_msg_field>([^<]*)<\/(?:\w+:)?tt_msg_field>/i', $body, $fieldMatches);

        $messages = [];
        foreach ($descMatches[1] as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') continue;
            $field = trim($fieldMatches[1][$i] ?? '');
            $messages[] = $field !== '' ? "{$desc} (field: {$field})" : $desc;
        }

        return $messages ? implode('; ', array_unique($messages)) : null;
    }

    private function buildRequisitionXml(PurchaseRequest $pr, DepartmentQadConfig $config): string
    {
        $today    = now()->format('Y-m-d');
        $dueDate  = now()->addDays(10)->format('Y-m-d');
        $purpose  = $this->esc($pr->notes ?: 'Kebutuhan GA - ' . $pr->pr_no);

        // rqmNbr diisi kalau PR ini sudah pernah terkirim (revisi GA setelah
        // ditolak Direktur) — QAD update requisition yang sama, bukan bikin baru.
        $rqmNbrTag = $pr->qad_req_no ? '<rqmNbr>' . $this->esc($pr->qad_req_no) . '</rqmNbr>' : '';

        $lines = '';
        foreach ($pr->details as $i => $detail) {
            $item = $detail->procurementItem;
            $lines .= $this->buildLineXml($i + 1, $item, $detail->qty_needed, $dueDate, $config, $pr->qad_req_no);
        }

        $creator = $pr->requesterUser() ?? $pr->creator;

        $siteCode      = $this->esc($config->site_code);
        $rqbyUserid    = $this->esc(($creator->qad_requested_by ?? null) ?: $config->requester_userid ?: $this->username);
        $endUserid     = $this->esc(($creator->qad_end_user_id ?? null) ?: $config->end_user_id ?: $config->site_code);
        $routeToApr    = $this->esc(($creator->qad_route_to_apr ?? null) ?: $config->approver_code ?: '');
        $routeToBuyer  = $this->esc(($creator->qad_route_to_buyer ?? null) ?: $config->buyer_code ?: '');

        return <<<XML
<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
    <soapenv:Header>
        <wsa:Action/>
        <wsa:To>urn:services-qad-com:SDI_CreatePR</wsa:To>
        <wsa:MessageID>urn:services-qad-com::SDI_CreatePR</wsa:MessageID>
        <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
        </wsa:ReferenceParameters>
        <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
        </wsa:ReplyTo>
    </soapenv:Header>
    <soapenv:Body>
        <maintainRequisition>
            <qcom:dsSessionContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>7000</qcom:propertyName>
                    <qcom:propertyValue/>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>scopeTransaction</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>version</qcom:propertyName>
                    <qcom:propertyValue>eB2_2</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                    <qcom:propertyValue>false</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>username</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($this->username)}</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>password</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($this->password)}</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>action</qcom:propertyName>
                    <qcom:propertyValue/>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>entity</qcom:propertyName>
                    <qcom:propertyValue/>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>email</qcom:propertyName>
                    <qcom:propertyValue/>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>emailLevel</qcom:propertyName>
                    <qcom:propertyValue/>
                </qcom:ttContext>
            </qcom:dsSessionContext>
            <dsRequisition>
                <requisition>
                    {$rqmNbrTag}
                    <rqmVend></rqmVend>
                    <rqmShip>{$siteCode}</rqmShip>
                    <rqmReqDate>{$today}</rqmReqDate>
                    <rqmNeedDate>{$today}</rqmNeedDate>
                    <rqmDueDate>{$today}</rqmDueDate>
                    <rqmRqbyUserid>{$rqbyUserid}</rqmRqbyUserid>
                    <rqmEndUserid>{$endUserid}</rqmEndUserid>
                    <rqmRmks>{$purpose}</rqmRmks>
                    <rqmSite>{$siteCode}</rqmSite>
                    <rqmStatus></rqmStatus>
                    <yn>true</yn>
                    <approveOrRoute>true</approveOrRoute>
                    <routeToApr>{$routeToApr}</routeToApr>
                    <routeToBuyer>{$routeToBuyer}</routeToBuyer>
                    <allInfoCorrect>true</allInfoCorrect>
{$lines}
                </requisition>
            </dsRequisition>
        </maintainRequisition>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function buildLineXml(int $lineNo, $item, float $qty, string $dueDate, DepartmentQadConfig $config, ?string $rqmNbr = null): string
    {
        $part = $this->esc($item->item_code);
        $desc = $this->esc($item->name);
        $um   = $this->esc($item->uom->code ?? '');
        $site = $this->esc($config->site_code);
        $rqmNbrTag = $rqmNbr ? '<rqmNbr>' . $this->esc($rqmNbr) . '</rqmNbr>' : '';

        return <<<XML
                    <lineDetail>
                        {$rqmNbrTag}
                        <line>{$lineNo}</line>
                        <lYn>true</lYn>
                        <rqdSite>{$site}</rqdSite>
                        <rqdPart>{$part}</rqdPart>
                        <rqdVend></rqdVend>
                        <rqdReqQty>{$qty}</rqdReqQty>
                        <rqdUm>{$um}</rqdUm>
                        <rqdDueDate>{$dueDate}</rqdDueDate>
                        <rqdNeedDate>{$dueDate}</rqdNeedDate>
                        <desc1>{$desc}</desc1>
                        <rqdLotRcpt>true</rqdLotRcpt>
                        <rqdUmConv></rqdUmConv>
                        <rqdStatus></rqdStatus>
                        </lineDetail>
XML;
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
