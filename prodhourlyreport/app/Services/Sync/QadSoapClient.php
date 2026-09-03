<?php

namespace App\Services\Sync;

use SimpleXMLElement;

class QadSoapClient
{
    /**
     * POST a SOAP envelope to QAD and return a nested array of the response.
     *
     * @return array{is_error: bool, message?: string, data?: array}
     */
    public function call(string $xmlRequest): array
    {
        $url = config('qad.url');

        if (blank($url)) {
            return [
                'is_error' => true,
                'message' => 'QAD sync URL is not configured (QAD_SYNC_URL).',
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
            ];
        }

        try {
            $array = $this->xmlToArray($response);
        } catch (\Throwable $e) {
            return [
                'is_error' => true,
                'message' => 'Failed to parse QAD SOAP response: '.$e->getMessage(),
            ];
        }

        return ['is_error' => false, 'data' => $array];
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

    /**
     * Convert SOAP XML into the same nested-array shape midnite81/xml2array
     * produced in webprod (element names as keys, including SOAP-ENV:Body).
     */
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

        // Leaf with only text stored under _text — unwrap to scalar for
        // parity with webprod's Xml2Array leaf handling.
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
