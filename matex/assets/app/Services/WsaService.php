<?php

namespace App\Services;

use App\Models\Qxwsas;
use Illuminate\Support\Facades\Log;

class WsaService
{

    private function httpHeader($req)
    {
        return array(
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ""',        // jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> ""
            'Content-length: ' . strlen(preg_replace("/\s+/", " ", $req))
        );
    }

    //sync item master
    public function wsaasset()
    {
        $wsa = Qxwsas::firstOrFail();
        $qxUrl = $wsa->qxwsa_wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;
        $domain = $wsa->qxwsa_wsa_domain;

        $qdocRequest =
            '
            <Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <SDI_getFixedAsset xmlns="' . $wsa->qxwsa_wsa_path . '"/>
                </Body>
            </Envelope> 

            ';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) {
                        $getInfo .= ", ";
                    }

                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if ($curlErrno !== 0 || $httpCode >= 400 || $qdocResponse === '') {
            Log::error('WSA sync: request to WSA endpoint did not succeed', [
                'url' => $qxUrl,
                'curl_errno' => $curlErrno,
                'curl_error' => $curlError,
                'http_code' => $httpCode,
            ]);
        }

        $parsed = $this->parseResponse($qdocResponse, $wsa->qxwsa_wsa_path);

        if ($parsed === false) {
            Log::error('WSA sync: could not parse WSA response as XML', [
                'url' => $qxUrl,
                'response_snippet' => substr((string) $qdocResponse, 0, 500),
            ]);
        }

        return $parsed;
    }

    /**
     * Parse the raw SOAP XML response into `[itemRows, outOkFlag]`, or
     * `false` if the response isn't valid XML for the given namespace.
     *
     * @return array{0: \SimpleXMLElement[], 1: string}|false
     */
    public function parseResponse(string $xml, string $namespace): array|false
    {
        $xmlResp = @simplexml_load_string($xml);

        if ($xmlResp === false) {
            return false;
        }

        try {
            $xmlResp->registerXPathNamespace('ns1', $namespace);
            $itemdata = $xmlResp->xpath('//ns1:tempRow');
            $outOk = $xmlResp->xpath('//ns1:outOK');
        } catch (\Throwable $e) {
            return false;
        }

        return [
            $itemdata,
            (string) ($outOk[0] ?? ''),
        ];
    }
}