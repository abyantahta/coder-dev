<?php

namespace App\Services\Qad;

use App\Models\DepartmentQadConfig;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WoPartOrderLine;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages a WoPartOrder's QAD lifecycle, ported from a separate warehouse
 * project's QadSoapService — trimmed to just what this app needs:
 * creating/updating the requisition (SDI_CreatePR, over the QXtend broker)
 * and checking whether it has been approved and converted to a PO
 * (SDI_getPRtoPO_, over the WSA broker — same connection as item master
 * sync in config/qad.php). approve/receive were dropped, not needed here.
 */
class QadRequisitionService
{
    private string $url;

    private string $username;

    private string $password;

    private int $timeout;

    /** Connection/transport error from the last read lookup, if any — lets callers tell "QAD unreachable" apart from "no data yet". */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function __construct(private readonly QadSoapClient $wsa)
    {
        $this->url = config('services.qad_soap.url', '');
        $this->username = config('services.qad_soap.username', '');
        $this->password = config('services.qad_soap.password', '');
        $this->timeout = (int) config('services.qad_soap.timeout', 30);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->url) && ! empty($this->username);
    }

    /**
     * Send a WoPartOrder (with its lines) as a requisition to QAD.
     *
     * @return array{success: bool, message: string, qad_req_no: ?string, raw_response?: string}
     */
    public function createRequisition(WoPartOrder $order): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'QAD SOAP belum dikonfigurasi', 'qad_req_no' => null];
        }

        $order->loadMissing('workOrder', 'lines.qadItem', 'requestedBy');

        $config = DepartmentQadConfig::where('department_id', $order->targetDepartmentId())->first();

        if (! $config || ! $config->site_code) {
            return [
                'success' => false,
                'message' => 'Konfigurasi QAD (site/buyer/approver) untuk departemen ini belum diisi.',
                'qad_req_no' => null,
            ];
        }

        if ($order->lines->isEmpty()) {
            return ['success' => false, 'message' => 'Tambahkan minimal 1 item sebelum membuat PR.', 'qad_req_no' => null];
        }

        $isUpdate = ! empty($order->pr_number);
        $xml = $this->buildRequisitionXml($order, $config);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => ''])
                ->timeout($this->timeout)
                ->post($this->url);

            $body = $response->body();

            if (! $response->successful()) {
                Log::error('QAD SOAP createRequisition HTTP error', ['status' => $response->status(), 'body' => $body]);

                return ['success' => false, 'message' => "QAD HTTP error: {$response->status()}", 'qad_req_no' => null, 'raw_response' => $body];
            }

            if (stripos($body, '<soapenv:Fault') !== false || stripos($body, ':Fault>') !== false) {
                Log::error('QAD SOAP createRequisition Fault', ['body' => $body]);

                return ['success' => false, 'message' => 'QAD mengembalikan SOAP Fault — cek raw response.', 'qad_req_no' => null, 'raw_response' => $body];
            }

            $result = $this->extractResult($body);
            $reqNo = $this->extractPrNumber($body);
            $warnings = $this->extractWarnings($body);

            // QAD: result 'success' or 'warning' means the requisition was
            // still created (number exists) — 'warning' is just a note
            // (e.g. unknown item gets converted to a Memo line). 'error'
            // means it failed outright.
            if ($result === 'error') {
                Log::error('QAD SOAP createRequisition error result', ['body' => $body]);

                return ['success' => false, 'message' => $warnings ?: 'QAD mengembalikan status error.', 'qad_req_no' => $reqNo, 'raw_response' => $body];
            }

            $verb = $isUpdate ? 'diperbarui' : 'dibuat';
            $message = $result === 'warning' && $warnings
                ? "Requisition {$verb} dengan catatan: {$warnings}"
                : "Requisition berhasil {$verb} di QAD.";

            return [
                'success' => true,
                'message' => $message,
                'qad_req_no' => $reqNo,
                'raw_response' => $body,
            ];
        } catch (Exception $e) {
            Log::error('QAD SOAP createRequisition exception: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'qad_req_no' => null];
        }
    }

    /**
     * Check a requisition's approval status and, once it exists, its PO
     * number(s) (SDI_getPRtoPO_, over the WSA broker). ApprovalStatus is
     * QAD's own field (confirmed live: '2' = approved) and is the direct
     * signal — PONbr can still be useful on its own (and briefly echoes
     * the requisition number itself while merely routed for purchasing,
     * before a real PO exists, so that case is filtered out).
     *
     * QAD can split one PR's lines across more than one PO (e.g. by
     * vendor) — each row is one PR line with its own independent PONbr, so
     * this returns every line's own po_no/po_status/qty (keyed by ReqLine)
     * rather than assuming a single PO for the whole requisition. `po_no`/
     * `po_status` at the top level stay as a convenience for the simple
     * (non-split) case: null when lines disagree, so callers don't
     * silently treat a split PR as having one PO.
     *
     * Returns null when nothing is known yet (or on any lookup failure —
     * this is a best-effort check, never blocks the PR itself).
     *
     * @return array{po_no: ?string, po_status: ?string, approval_status: ?string, is_split: bool, lines: array<int, array{po_no: ?string, po_status: ?string}>}|null
     */
    public function findPurchaseOrder(string $rqmNbr): ?array
    {
        $namespace = config('qad.ws_namespace');

        if (blank($namespace)) {
            return null;
        }

        $this->lastError = null;
        $xml = $this->buildPrToPoXml($rqmNbr, $namespace);
        $response = $this->wsa->call($xml);

        if ($response['is_error']) {
            Log::warning('QAD WSA findPurchaseOrder failed', ['rqmNbr' => $rqmNbr, 'message' => $response['message'] ?? null]);
            $this->lastError = $response['message'] ?? 'QAD tidak merespons';

            return null;
        }

        $body = $response['raw'] ?? '';

        if (! preg_match_all('/<ttPRtoPORow>(.*?)<\/ttPRtoPORow>/is', $body, $rows) || empty($rows[1])) {
            return null;
        }

        // ApprovalStatus is per-requisition (same across all its lines) —
        // the first row carries it regardless of whether a PO exists yet.
        $approvalStatus = preg_match('/<ApprovalStatus>([^<]*)<\/ApprovalStatus>/i', $rows[1][0], $am)
            ? trim($am[1])
            : null;

        $lines = [];

        foreach ($rows[1] as $row) {
            $reqLine = preg_match('/<ReqLine>([^<]+)<\/ReqLine>/i', $row, $lm) ? (int) trim($lm[1]) : null;

            if ($reqLine === null) {
                continue;
            }

            $poNo = null;
            $poStatus = null;

            if (preg_match('/<PONbr>([^<]+)<\/PONbr>/i', $row, $m)) {
                $candidate = trim($m[1]);

                if ($candidate !== '' && stripos($candidate, 'PO') === 0 && strcasecmp($candidate, $rqmNbr) !== 0) {
                    $poNo = $candidate;
                    $poStatus = preg_match('/<POStatus>([^<]*)<\/POStatus>/i', $row, $sm) ? trim($sm[1]) : null;
                }
            }

            $lines[$reqLine] = ['po_no' => $poNo, 'po_status' => $poStatus];
        }

        $distinctPoNumbers = collect($lines)->pluck('po_no')->filter()->unique()->values();
        $isSplit = $distinctPoNumbers->count() > 1;

        // Only surface a single top-level po_no/po_status when every line
        // agrees (or only one line even has one yet) — a split PR has no
        // one "the" PO, so leave it null and make callers look at `lines`.
        $primaryPoNo = $isSplit ? null : $distinctPoNumbers->first();
        $primaryPoStatus = $primaryPoNo
            ? collect($lines)->firstWhere('po_no', $primaryPoNo)['po_status'] ?? null
            : null;

        if ($distinctPoNumbers->isEmpty() && $approvalStatus === null) {
            return null;
        }

        return [
            'po_no' => $primaryPoNo,
            'po_status' => $primaryPoStatus,
            'approval_status' => $approvalStatus,
            'is_split' => $isSplit,
            'lines' => $lines,
        ];
    }

    /**
     * Cumulative received quantity per requisition line, straight from
     * QAD (same SDI_getPRtoPO_ call as findPurchaseOrder, different
     * field) — used to validate a receipt actually posted, since QAD can
     * reply success on receivePurchaseOrder() without the quantity
     * actually moving. Returns [] on any lookup failure.
     *
     * @return array<int, float> requisition line number => qty received
     */
    public function getReceivedQtyByLine(string $rqmNbr): array
    {
        $namespace = config('qad.ws_namespace');

        if (blank($namespace)) {
            return [];
        }

        $this->lastError = null;
        $xml = $this->buildPrToPoXml($rqmNbr, $namespace);
        $response = $this->wsa->call($xml);

        if ($response['is_error']) {
            Log::warning('QAD WSA getReceivedQtyByLine failed', ['rqmNbr' => $rqmNbr, 'message' => $response['message'] ?? null]);
            $this->lastError = $response['message'] ?? 'QAD tidak merespons';

            return [];
        }

        $body = $response['raw'] ?? '';

        if (! preg_match_all('/<ttPRtoPORow>(.*?)<\/ttPRtoPORow>/is', $body, $rows)) {
            return [];
        }

        $result = [];
        foreach ($rows[1] as $row) {
            if (! preg_match('/<ReqLine>([^<]+)<\/ReqLine>/i', $row, $lineM)) {
                continue;
            }
            $line = (int) trim($lineM[1]);
            $qty = preg_match('/<POQtyReceived>([^<]*)<\/POQtyReceived>/i', $row, $qtyM)
                ? (float) trim($qtyM[1])
                : 0.0;
            $result[$line] = $qty;
        }

        return $result;
    }

    /**
     * Look up a PO's own line items directly by PO number (SDI_getActivePO2,
     * WSA broker) — QAD has no operation keyed on PO number itself, so this
     * browses "active" PO lines for a given month/year and filters for the
     * one we want. Only lines still open show up this way; a line QAD
     * considers fully received drops out of the "active" list entirely, so
     * this is informational (what's really open in QAD right now) rather
     * than a complete per-line history — getReceivedQtyByLine() (PR-keyed)
     * remains the source of truth for validating our own receipts.
     *
     * @return array<int, array{po_no: string, line: int, part: string, qty_ord: float, qty_rcvd: float, um: string, due_date: ?string, status: string, site: string, vendor: string}>
     */
    public function findPurchaseOrderLines(string $poNumber, ?\DateTimeInterface $referenceDate = null): array
    {
        $namespace = config('qad.ws_namespace');

        if (blank($namespace) || blank($poNumber)) {
            return [];
        }

        $months = [$referenceDate ?? now()];
        if (! $referenceDate || $referenceDate->format('Y-m') !== now()->format('Y-m')) {
            $months[] = now();
        }

        foreach ($months as $when) {
            $rows = $this->fetchActivePoLines($when);
            $lines = [];

            foreach ($rows as $row) {
                if ($row['po_no'] === $poNumber) {
                    $lines[$row['line']] = $row;
                }
            }

            if (! empty($lines)) {
                ksort($lines);

                return $lines;
            }
        }

        return [];
    }

    /**
     * Every active PO line QAD knows about for a given month (SDI_getActivePO2,
     * WSA broker), unfiltered — every site/vendor, not just our own PRs'.
     * findPurchaseOrderLines() filters this down to one PO. Returns [] on
     * any lookup failure — best-effort, like the other WSA reads here.
     *
     * @return array<int, array{po_no: string, line: int, part: string, qty_ord: float, qty_rcvd: float, um: string, due_date: ?string, status: string, site: string, vendor: string}>
     */
    public function fetchActivePoLines(\DateTimeInterface $when): array
    {
        $namespace = config('qad.ws_namespace');

        if (blank($namespace)) {
            return [];
        }

        $xml = $this->buildActivePO2Xml($when, $namespace);
        $response = $this->wsa->call($xml);

        if ($response['is_error']) {
            Log::warning('QAD WSA fetchActivePoLines failed', ['when' => $when->format('Y-m'), 'message' => $response['message'] ?? null]);

            return [];
        }

        return $this->parseActivePO2Rows($response['raw'] ?? '');
    }

    private function buildActivePO2Xml(\DateTimeInterface $when, string $namespace): string
    {
        $nsEsc = $this->esc($namespace);

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="{$nsEsc}">
    <soapenv:Header/>
    <soapenv:Body>
        <wsa:SDI_getActivePO2>
            <wsa:inpdomain>{$this->esc(config('qad.domain'))}</wsa:inpdomain>
            <wsa:inpmonth>{$when->format('n')}</wsa:inpmonth>
            <wsa:inpyear>{$when->format('Y')}</wsa:inpyear>
        </wsa:SDI_getActivePO2>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function parseActivePO2Rows(string $body): array
    {
        if (! preg_match_all('/<tempRow>(.*?)<\/tempRow>/is', $body, $rows)) {
            return [];
        }

        $result = [];

        foreach ($rows[1] as $row) {
            if (! preg_match('/<t_pod_nbr>([^<]*)<\/t_pod_nbr>/i', $row, $nbrM)) {
                continue;
            }

            $result[] = [
                'po_no' => trim($nbrM[1]),
                'line' => preg_match('/<t_pod_line>([^<]*)<\/t_pod_line>/i', $row, $m) ? (int) trim($m[1]) : 0,
                'part' => preg_match('/<t_pod_part>([^<]*)<\/t_pod_part>/i', $row, $m) ? trim($m[1]) : '',
                'qty_ord' => preg_match('/<t_pod_qty_ord>([^<]*)<\/t_pod_qty_ord>/i', $row, $m) ? (float) trim($m[1]) : 0.0,
                'qty_rcvd' => preg_match('/<t_pod_qty_rcvd>([^<]*)<\/t_pod_qty_rcvd>/i', $row, $m) ? (float) trim($m[1]) : 0.0,
                'um' => preg_match('/<t_pod_um>([^<]*)<\/t_pod_um>/i', $row, $m) ? trim($m[1]) : '',
                'due_date' => preg_match('/<t_pod_due_date>([^<]*)<\/t_pod_due_date>/i', $row, $m) ? trim($m[1]) : null,
                'status' => preg_match('/<t_pod_status>([^<]*)<\/t_pod_status>/i', $row, $m) ? trim($m[1]) : '',
                'site' => preg_match('/<t_pt_site>([^<]*)<\/t_pt_site>/i', $row, $m) ? trim($m[1]) : '',
                'vendor' => preg_match('/<t_po_vend>([^<]*)<\/t_po_vend>/i', $row, $m) ? trim($m[1]) : '',
            ];
        }

        return $result;
    }

    /**
     * Record a goods receipt against a QAD PO (SDI_eKanbanGR), over the
     * same QXtend connection as createRequisition() — but authenticated as
     * the receiving user themselves (QAD requires a real named/authorized
     * person for this, unlike PR creation's shared service account), so
     * $actor must have their own qad_username/qad_password on file (see
     * User::canReceiveInQad()). $lines: list of ['line' => int, 'qty' => float]
     * — every line here must belong to $poNumber; a PR split across
     * multiple POs means one call per PO, since QAD's ordernum is one PO
     * per call (see WarehouseController::receive(), which groups lines by
     * their own WoPartOrderLine::qad_po_no before calling this).
     *
     * @return array{success: bool, message: string, raw_response?: string, qad_result?: ?string, qad_warning?: ?string}
     */
    public function receivePurchaseOrder(string $poNumber, WoPartOrder $order, array $lines, User $actor): array
    {
        if (blank($this->url)) {
            return ['success' => false, 'message' => 'QAD SOAP belum dikonfigurasi'];
        }
        if (! $actor->canReceiveInQad()) {
            return ['success' => false, 'message' => 'Kamu belum punya login QAD sendiri — hubungi admin untuk didaftarkan sebagai penerima barang.'];
        }
        if (blank($poNumber)) {
            return ['success' => false, 'message' => 'Nomor PO QAD belum diisi.'];
        }
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Tidak ada baris item untuk diterima.'];
        }

        $order->loadMissing('workOrder');
        $config = DepartmentQadConfig::where('department_id', $order->targetDepartmentId())->first();

        if (! $config || ! $config->site_code || ! $config->location) {
            return ['success' => false, 'message' => 'Site Code / Location (Receiving) untuk departemen ini belum diisi. Minta Section Head departemen mengisinya di Dept Admin → Konfigurasi QAD.'];
        }

        $xml = $this->buildReceivePurchaseOrderXml($poNumber, $lines, $config, $actor);

        try {
            $response = Http::withBody($xml, 'text/xml; charset=utf-8')
                ->withHeaders(['SOAPAction' => ''])
                ->timeout($this->timeout)
                ->post($this->url);

            $body = $response->body();

            if (! $response->successful()) {
                Log::error('QAD SOAP receivePurchaseOrder HTTP error', ['status' => $response->status(), 'body' => $body]);

                return ['success' => false, 'message' => "QAD HTTP error: {$response->status()}", 'raw_response' => $body];
            }

            if (stripos($body, '<soapenv:Fault') !== false || stripos($body, ':Fault>') !== false) {
                Log::error('QAD SOAP receivePurchaseOrder Fault', ['body' => $body]);

                return ['success' => false, 'message' => 'QAD mengembalikan SOAP Fault — cek raw response.', 'raw_response' => $body];
            }

            $result = $this->extractResult($body);
            $warnings = $this->extractWarnings($body);

            // QAD has been seen to report result=error here for a GL/costing
            // posting sub-failure (missing cost fields etc.) even though the
            // actual goods movement/qty posted fine — so this alone is not
            // trustworthy as a pass/fail signal. Don't hard-fail on it; pass
            // it through as a warning and let the caller verify against the
            // real received qty (getReceivedQtyByLine) to decide what
            // actually happened.
            if ($result === 'error' || $result === 'warning') {
                Log::warning('QAD SOAP receivePurchaseOrder non-success result', ['po_no' => $poNumber, 'result' => $result, 'warnings' => $warnings]);
            }

            Log::info('QAD SOAP receivePurchaseOrder response', [
                'po_no' => $poNumber, 'actor_id' => $actor->id, 'lines' => $lines, 'result' => $result, 'warnings' => $warnings,
            ]);

            // Keep the user-facing message plain — $warnings is QAD's raw
            // Progress ABL field-error text (e.g. "tx2d_totamt is mandatory,
            // ..."), not something a warehouse user should have to read.
            // It's still returned as qad_warning for whoever needs to chase
            // it up on the QAD side (e.g. via qad_response on the order).
            return [
                'success' => true,
                'message' => 'Penerimaan PO berhasil dicatat di QAD.',
                'raw_response' => $body,
                'qad_result' => $result,
                'qad_warning' => $warnings ?: null,
            ];
        } catch (Exception $e) {
            Log::error('QAD SOAP receivePurchaseOrder exception: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildReceivePurchaseOrderXml(string $poNumber, array $lines, DepartmentQadConfig $config, User $actor): string
    {
        $poEsc = $this->esc($poNumber);
        $today = now()->format('Y-m-d');
        $siteEsc = $this->esc($config->site_code);
        $locationEsc = $this->esc($config->location);

        $lineXml = '';
        foreach ($lines as $line) {
            $lineNo = (int) $line['line'];
            $qtyEsc = $this->esc((string) $line['qty']);
            $lineXml .= <<<XML

                        <lineDetail>
                            <line>{$lineNo}</line>
                            <lotserialQty>{$qtyEsc}</lotserialQty>
                            <site>{$siteEsc}</site>
                            <location>{$locationEsc}</location>
                            <multiEntry>false</multiEntry>
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
                    <qcom:propertyValue>{$this->esc($actor->qad_username)}</qcom:propertyValue>
                </qcom:ttContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>password</qcom:propertyName>
                    <qcom:propertyValue>{$this->esc($actor->qad_password)}</qcom:propertyValue>
                </qcom:ttContext>
            </qcom:dsSessionContext>
            <dsPurchaseOrderReceive>
                <purchaseOrderReceive>
                    <ordernum>{$poEsc}</ordernum>
                    <effDate>{$today}</effDate>
                    <fillAll>false</fillAll>
                    <move>true</move>{$lineXml}
                    <yn>true</yn>
                    <yn1>true</yn1>
                </purchaseOrderReceive>
            </dsPurchaseOrderReceive>
        </receivePurchaseOrder>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function buildPrToPoXml(string $rqmNbr, string $namespace): string
    {
        $nsEsc = $this->esc($namespace);
        $reqEsc = $this->esc($rqmNbr);

        // Date filter is required by this WSA operation and is AND'd
        // against ipReqNbr (not OR'd) — send a wide range so the
        // requisition is found regardless of its actual need_date.
        $from = now()->subYears(2);
        $to = now()->addYear();

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="{$nsEsc}">
    <soapenv:Header/>
    <soapenv:Body>
        <wsa:SDI_getPRtoPO_>
            <wsa:ipDomain>{$this->esc(config('qad.domain'))}</wsa:ipDomain>
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

    /** QAD requisition number — in dsRequisitionResponse/requisition/rqmNbr when suppressResponseDetail=false. */
    private function extractPrNumber(string $body): ?string
    {
        if (preg_match('/<(?:\w+:)?rqmNbr>([^<]+)<\/(?:\w+:)?rqmNbr>/i', $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /** Combine tt_msg_desc + tt_msg_field (if present) into one concise string per message. */
    private function extractWarnings(string $body): ?string
    {
        if (! preg_match_all('/<(?:\w+:)?tt_msg_desc>([^<]*)<\/(?:\w+:)?tt_msg_desc>/i', $body, $descMatches)) {
            return null;
        }
        preg_match_all('/<(?:\w+:)?tt_msg_field>([^<]*)<\/(?:\w+:)?tt_msg_field>/i', $body, $fieldMatches);

        $messages = [];
        foreach ($descMatches[1] as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') {
                continue;
            }
            $field = trim($fieldMatches[1][$i] ?? '');
            $messages[] = $field !== '' ? "{$desc} (field: {$field})" : $desc;
        }

        return $messages ? implode('; ', array_unique($messages)) : null;
    }

    private function buildRequisitionXml(WoPartOrder $order, DepartmentQadConfig $config): string
    {
        $today = now()->format('Y-m-d');
        // One need date for the whole PR batch (not per line) — required
        // before a PR can be created, see WarehouseController::createPr().
        $needDate = $order->need_date?->format('Y-m-d') ?? $today;
        $purpose = $this->esc($order->warehouse_note ?: $order->request_note ?: 'Kebutuhan '.$order->displayReference());

        // rqmNbr filled means this order already has a QAD requisition
        // (retry after a previous send) — QAD updates that same
        // requisition instead of creating a new one.
        $rqmNbrTag = $order->pr_number ? '<rqmNbr>'.$this->esc($order->pr_number).'</rqmNbr>' : '';

        $lines = '';
        foreach ($order->lines as $i => $line) {
            $lines .= $this->buildLineXml($i + 1, $line, $needDate, $config, $order->pr_number);
        }

        $siteCode = $this->esc($config->site_code);
        $rqbyUserid = $this->esc($config->requester_userid ?: $this->username);
        $endUserid = $this->esc($config->end_user_id ?: $config->site_code);
        $routeToApr = $this->esc($config->approver_code ?: '');
        $routeToBuyer = $this->esc($config->buyer_code ?: '');

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
                    <qcom:propertyName>domain</qcom:propertyName>
                    <qcom:propertyValue>7000</qcom:propertyValue>
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
                    <rqmNeedDate>{$needDate}</rqmNeedDate>
                    <rqmDueDate>{$needDate}</rqmDueDate>
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

    private function buildLineXml(int $lineNo, WoPartOrderLine $line, string $needDate, DepartmentQadConfig $config, ?string $rqmNbr = null): string
    {
        // Custom/manual lines have no QAD master code — QAD rqdPart cannot
        // be blank ("Blank not allowed"), so reuse the item name.
        $partRaw = trim((string) ($line->part_code ?: $line->description ?: ''));
        $descRaw = trim((string) ($line->description ?: $line->part_code ?: ''));
        $part = $this->esc(mb_substr($partRaw, 0, 18));
        $desc = $this->esc(mb_substr($descRaw, 0, 24));
        $um = $this->esc($line->uom ?? '');
        $site = $this->esc($config->site_code);
        $rqmNbrTag = $rqmNbr ? '<rqmNbr>'.$this->esc($rqmNbr).'</rqmNbr>' : '';

        return <<<XML
                    <lineDetail>
                        {$rqmNbrTag}
                        <line>{$lineNo}</line>
                        <lYn>true</lYn>
                        <rqdSite>{$site}</rqdSite>
                        <rqdPart>{$part}</rqdPart>
                        <rqdVend></rqdVend>
                        <rqdReqQty>{$line->quantity}</rqdReqQty>
                        <rqdUm>{$um}</rqdUm>
                        <rqdDueDate>{$needDate}</rqdDueDate>
                        <rqdNeedDate>{$needDate}</rqdNeedDate>
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
