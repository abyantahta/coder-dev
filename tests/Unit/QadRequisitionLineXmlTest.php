<?php

namespace Tests\Unit;

use App\Models\DepartmentQadConfig;
use App\Models\WoPartOrderLine;
use App\Services\Qad\QadRequisitionService;
use ReflectionMethod;
use Tests\TestCase;

class QadRequisitionLineXmlTest extends TestCase
{
    private function lineXml(WoPartOrderLine $line): string
    {
        $method = new ReflectionMethod(QadRequisitionService::class, 'buildLineXml');
        $config = new DepartmentQadConfig(['site_code' => 'SDI']);

        return $method->invoke(new QadRequisitionService(), 1, $line, '2026-09-15', $config, null);
    }

    public function test_catalog_line_uses_part_code(): void
    {
        $xml = $this->lineXml(new WoPartOrderLine([
            'part_code'   => 'BOLT-M6',
            'description' => 'Hex Bolt M6',
            'quantity'    => 4,
            'uom'         => 'EA',
        ]));

        $this->assertStringContainsString('<rqdPart>BOLT-M6</rqdPart>', $xml);
        $this->assertStringContainsString('<desc1>Hex Bolt M6</desc1>', $xml);
        $this->assertStringContainsString('<rqdReqQty>4</rqdReqQty>', $xml);
        $this->assertStringContainsString('<rqdUm>EA</rqdUm>', $xml);
        $this->assertStringContainsString('<rqdNeedDate>2026-09-15</rqdNeedDate>', $xml);
    }

    public function test_blank_part_code_falls_back_to_description(): void
    {
        $xml = $this->lineXml(new WoPartOrderLine([
            'part_code'   => null,
            'description' => 'Seal karet custom',
            'quantity'    => 1,
            'uom'         => 'EA',
        ]));

        $this->assertStringContainsString('<rqdPart>Seal karet custom</rqdPart>', $xml);
        $this->assertStringNotContainsString('<rqdPart></rqdPart>', $xml);
    }

    public function test_part_and_description_are_truncated_to_qad_limits(): void
    {
        $xml = $this->lineXml(new WoPartOrderLine([
            'part_code'   => str_repeat('P', 30),
            'description' => str_repeat('D', 40),
            'quantity'    => 1,
            'uom'         => 'EA',
        ]));

        $this->assertStringContainsString('<rqdPart>'.str_repeat('P', 18).'</rqdPart>', $xml);
        $this->assertStringContainsString('<desc1>'.str_repeat('D', 24).'</desc1>', $xml);
    }

    public function test_xml_special_characters_are_escaped(): void
    {
        $xml = $this->lineXml(new WoPartOrderLine([
            'part_code'   => 'A&B',
            'description' => 'Size < 10"',
            'quantity'    => 1,
            'uom'         => 'EA',
        ]));

        $this->assertStringContainsString('<rqdPart>A&amp;B</rqdPart>', $xml);
        $this->assertStringContainsString('Size &lt; 10', $xml);
    }

    public function test_is_configured_requires_url_and_username(): void
    {
        config(['services.qad_soap.url' => '', 'services.qad_soap.username' => '']);
        $this->assertFalse((new QadRequisitionService())->isConfigured());

        config(['services.qad_soap.url' => 'http://qad.test', 'services.qad_soap.username' => 'wsa']);
        $this->assertTrue((new QadRequisitionService())->isConfigured());
    }
}
