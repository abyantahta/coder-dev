<?php

namespace App\Services\Qad;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QadSoapClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $domain,
        private readonly string $username,
        private readonly string $password,
        private readonly string $version,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('qad.qxi');

        return new self(
            baseUrl: $config['base_url'],
            domain: $config['domain'],
            username: $config['username'],
            password: $config['password'],
            version: $config['version'],
        );
    }

    /**
     * Read-only reachability check: fetches the service WSDL.
     * Does not authenticate and has no side effects on QAD data.
     */
    public function ping(): array
    {
        $response = Http::timeout(10)->get($this->baseUrl, ['wsdl' => null]);

        return [
            'reachable' => $response->successful(),
            'status' => $response->status(),
        ];
    }

    /**
     * Send a QXtend SOAP request.
     *
     * @param  string  $action  WS-Addressing action, e.g. "maintainPurchaseOrder"
     * @param  string  $to  WS-Addressing destination, e.g. "urn:services-qad-com:SDI_BuatPO"
     * @param  string  $bodyXml  Inner <qsvc:{action}>...</qsvc:{action}> XML, without envelope/header.
     *                           Use sessionContextXml() to build the qcom:dsSessionContext block inside it.
     */
    public function call(string $action, string $to, string $bodyXml): array
    {
        return $this->sendRaw($this->buildEnvelope($action, $to, $bodyXml));
    }

    /**
     * Send a fully custom envelope as-is — for operations whose shape
     * doesn't match the qsvc:-prefixed maintain* convention buildEnvelope()
     * assumes (e.g. receivePurchaseOrder uses a default, unprefixed
     * namespace and a different session-context field set).
     */
    public function sendRaw(string $envelope): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/xml',
            'SOAPAction' => '',
        ])->withBody($envelope, 'application/xml')
            ->timeout(30)
            ->post($this->baseUrl);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'request' => $envelope,
            'response' => $response->body(),
        ];
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    /**
     * Builds the qcom:dsSessionContext block (domain/username/password/version)
     * that QXtend expects inside every operation body.
     */
    public function sessionContextXml(): string
    {
        return <<<XML
            <qcom:dsSessionContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>domain</qcom:propertyName>
            <qcom:propertyValue>{$this->domain}</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>username</qcom:propertyName>
            <qcom:propertyValue>{$this->username}</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>password</qcom:propertyName>
            <qcom:propertyValue>{$this->password}</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>scopeTransaction</qcom:propertyName>
            <qcom:propertyValue>true</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>version</qcom:propertyName>
            <qcom:propertyValue>{$this->version}</qcom:propertyValue>
            </qcom:ttContext>
            </qcom:dsSessionContext>
            XML;
    }

    private function buildEnvelope(string $action, string $to, string $bodyXml): string
    {
        $messageId = 'urn:uuid:'.(string) Str::uuid();

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:qsvc="urn:schemas-qad-com:xml-services">
            <soapenv:Header>
            <wsa:Action>{$action}</wsa:Action>
            <wsa:To>{$to}</wsa:To>
            <wsa:MessageID>{$messageId}</wsa:MessageID>
            <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
            </wsa:ReferenceParameters>
            <wsa:ReplyTo>
            <wsa:Address>http://www.w3.org/2005/08/addressing/anonymous</wsa:Address>
            </wsa:ReplyTo>
            </soapenv:Header>
            <soapenv:Body>
            {$bodyXml}
            </soapenv:Body>
            </soapenv:Envelope>
            XML;
    }
}
