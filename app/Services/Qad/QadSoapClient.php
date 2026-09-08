<?php

namespace App\Services\Qad;

use SimpleXMLElement;

class QadSoapClient
{
    /**
     * POST a SOAP envelope to QAD and return a nested array of the response.
     *
     * `raw`/`http_code` are always included (even on success) so callers can
     * surface the exact wire response for diagnosing an unverified endpoint.
     *
     * @return array{is_error: bool, message?: string, data?: array, raw?: string, http_code?: int}
     */
    public function call(string $xmlRequest): array
    {
        $url = config('qad.url');

        if (blank($url)) {
            return [
                'is_error' => true,
                'message' => 'QAD URL is not configured (QAD_URL).',
            ];
        }

        $sslVerify = (bool) config('qad.ssl_verify', false);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: ""',
            ],
            CURLOPT_POSTFIELDS => $xmlRequest,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_TIMEOUT => config('qad.timeout', 120),
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $message = curl_error($curl);
            curl_close($curl);

            return ['is_error' => true, 'message' => $message];
        }

        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $response === '') {
            return [
                'is_error' => true,
                'message' => "Empty response from QAD (HTTP {$httpCode}).",
                'http_code' => $httpCode,
            ];
        }

        try {
            $array = $this->xmlToArray($response);
        } catch (\Throwable $e) {
            return [
                'is_error' => true,
                'message' => 'Failed to parse QAD SOAP response: '.$e->getMessage(),
                'raw' => $response,
                'http_code' => $httpCode,
            ];
        }

        return ['is_error' => false, 'data' => $array, 'raw' => $response, 'http_code' => $httpCode];
    }

    /**
     * QAD sometimes returns empty nodes as arrays; normalize to null/string.
     */
    public function sanitizeValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return empty($value) ? null : json_encode($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function xmlToArray(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($element === false) {
            throw new \RuntimeException('Invalid XML');
        }

        return $this->elementToArray($element);
    }

    private function elementToArray(SimpleXMLElement $element): array
    {
        $result = [];

        foreach ($element->attributes() as $name => $value) {
            $result['@'.$name] = (string) $value;
        }

        $namespaces = $element->getDocNamespaces(true);
        $namespaces[''] = null;

        foreach ($namespaces as $prefix => $ns) {
            foreach ($element->children($ns) as $childName => $child) {
                $key = $prefix ? "{$prefix}:{$childName}" : $childName;
                $value = $this->nodeValue($child);

                if (array_key_exists($key, $result)) {
                    if (! $this->isList($result[$key])) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key][] = $value;
                } else {
                    $result[$key] = $value;
                }
            }
        }

        $text = trim((string) $element);
        if ($text !== '' && $result === []) {
            return ['_text' => $text];
        }

        if ($text !== '' && $result !== []) {
            $result['_text'] = $text;
        }

        return $result;
    }

    private function nodeValue(SimpleXMLElement $node): mixed
    {
        $children = $this->elementToArray($node);
        $text = trim((string) $node);

        if ($children === []) {
            return $text;
        }

        if (count($children) === 1 && array_key_exists('_text', $children)) {
            return $children['_text'];
        }

        return $children;
    }

    private function isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }
}
