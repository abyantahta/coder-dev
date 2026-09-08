<?php

namespace App\Services\Qad;

use App\Models\Qxwsas;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class WsaService
{
    /**
     * Call a WSA SOAP operation (e.g. "SDI_getFixedAsset") and return the
     * parsed `tempRow` rows plus the `outOK` flag.
     *
     * @return array{0: SimpleXMLElement[], 1: string}
     */
    public function call(string $operation): array
    {
        $wsa = Qxwsas::firstOrFail();

        $request = <<<XML
            <Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <{$operation} xmlns="{$wsa->qxwsa_wsa_path}"/>
                </Body>
            </Envelope>
            XML;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $wsa->qxwsa_wsa_url,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => $this->httpHeader($request),
            CURLOPT_POSTFIELDS => preg_replace('/\s+/', ' ', $request),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($errno !== 0 || $httpCode >= 400 || $response === '' || $response === false) {
            Log::error('WSA sync: request to WSA endpoint did not succeed', [
                'operation' => $operation,
                'url' => $wsa->qxwsa_wsa_url,
                'curl_errno' => $errno,
                'curl_error' => $error,
                'http_code' => $httpCode,
            ]);

            throw new RuntimeException("WSA request failed for {$operation}: ".($error ?: "HTTP {$httpCode}"));
        }

        $parsed = $this->parseResponse((string) $response, $wsa->qxwsa_wsa_path);

        if ($parsed === false) {
            Log::error('WSA sync: could not parse WSA response as XML', [
                'operation' => $operation,
                'url' => $wsa->qxwsa_wsa_url,
                'response_snippet' => substr((string) $response, 0, 500),
            ]);

            throw new RuntimeException("WSA response for {$operation} was not valid XML.");
        }

        return $parsed;
    }

    /**
     * @return array{0: SimpleXMLElement[], 1: string}|false
     */
    public function parseResponse(string $xml, string $namespace): array|false
    {
        $xmlResp = @simplexml_load_string($xml);

        if ($xmlResp === false) {
            return false;
        }

        try {
            $xmlResp->registerXPathNamespace('ns1', $namespace);
            $rows = $xmlResp->xpath('//ns1:tempRow');
            $outOk = $xmlResp->xpath('//ns1:outOK');
        } catch (Throwable) {
            return false;
        }

        return [$rows ?: [], (string) ($outOk[0] ?? '')];
    }

    private function httpHeader(string $request): array
    {
        return [
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ""',
            'Content-length: '.strlen(preg_replace('/\s+/', ' ', $request)),
        ];
    }
}
