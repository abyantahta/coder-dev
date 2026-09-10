<?php

namespace App\Services\Qad;

use App\Models\DepartmentQadConfig;
use App\Models\WoPartOrder;
use App\Models\WoPartOrderLine;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Creates a real Purchase Requisition in QAD (SOAP action SDI_CreatePR),
 * ported from a separate warehouse project's QadSoapService — trimmed to
 * just the "create/update requisition" path (approve/receive/PO-lookup
 * were dropped, not needed here).
 */
class QadRequisitionService
{
    private string $url;

    private string $username;

    private string $password;

    private int $timeout;

    public function __construct()
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

        $config = DepartmentQadConfig::where('department_id', $order->workOrder->target_department_id)->first();

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
        $purpose = $this->esc($order->warehouse_note ?: $order->request_note ?: 'Kebutuhan '.$order->workOrder->wo_number);

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
        $part = $this->esc($line->part_code ?? '');
        $desc = $this->esc($line->description ?? '');
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
