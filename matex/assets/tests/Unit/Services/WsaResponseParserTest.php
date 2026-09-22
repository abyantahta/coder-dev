<?php

namespace Tests\Unit\Services;

use App\Services\WsaService;
use PHPUnit\Framework\TestCase;

class WsaResponseParserTest extends TestCase
{
    private const NAMESPACE = 'http://example.com/wsa';

    private WsaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WsaService();
    }

    public function test_parses_item_rows_and_outok_flag(): void
    {
        $xml = '<?xml version="1.0"?>
            <Envelope xmlns="' . self::NAMESPACE . '">
                <Body>
                    <SDI_getFixedAssetResponse>
                        <tempRow><t_fa_id>ASSET-1</t_fa_id></tempRow>
                        <tempRow><t_fa_id>ASSET-2</t_fa_id></tempRow>
                        <outOK>true</outOK>
                    </SDI_getFixedAssetResponse>
                </Body>
            </Envelope>';

        $result = $this->service->parseResponse($xml, self::NAMESPACE);

        $this->assertNotFalse($result);
        [$itemRows, $outOk] = $result;
        $this->assertCount(2, $itemRows);
        $this->assertSame('true', $outOk);
    }

    public function test_returns_empty_string_when_outok_is_missing(): void
    {
        $xml = '<?xml version="1.0"?>
            <Envelope xmlns="' . self::NAMESPACE . '">
                <Body>
                    <SDI_getFixedAssetResponse></SDI_getFixedAssetResponse>
                </Body>
            </Envelope>';

        [$itemRows, $outOk] = $this->service->parseResponse($xml, self::NAMESPACE);

        $this->assertCount(0, $itemRows);
        $this->assertSame('', $outOk);
    }

    public function test_malformed_xml_returns_false_instead_of_crashing(): void
    {
        $this->assertFalse($this->service->parseResponse('not xml at all', self::NAMESPACE));
    }

    public function test_empty_response_returns_false(): void
    {
        $this->assertFalse($this->service->parseResponse('', self::NAMESPACE));
    }
}
