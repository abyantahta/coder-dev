<?php

namespace Tests\Unit\Services;

use App\Services\WsaCategoryResolver;
use PHPUnit\Framework\TestCase;

class WsaCategoryResolverTest extends TestCase
{
    private WsaCategoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new WsaCategoryResolver();
    }

    /**
     * @dataProvider wsaCodes
     */
    public function test_resolves_known_wsa_codes(string $wsaCode, string $expectedCategory, ?int $expectedLifetimeOverride): void
    {
        $result = $this->resolver->resolve($wsaCode);

        $this->assertSame($expectedCategory, $result['categoryName']);
        $this->assertSame($expectedLifetimeOverride, $result['lifetimeOverride']);
    }

    public static function wsaCodes(): array
    {
        return [
            'TOOLING' => ['TOOLING', 'Tooling', null],
            'TOOLING2' => ['TOOLING2', 'Tooling', null],
            'TOOLING3 overrides lifetime to 36' => ['TOOLING3', 'Tooling', 36],
            'BUILDING' => ['BUILDING', 'Building', null],
            'VEHICLE' => ['VEHICLE', 'Vehicle', null],
            'OFC-EQP' => ['OFC-EQP', 'Office Equipment', null],
            'MACHINE' => ['MACHINE', 'Machine', null],
        ];
    }

    public function test_unknown_code_falls_back_to_vehicle(): void
    {
        $result = $this->resolver->resolve('SOME_UNKNOWN_CODE');

        $this->assertSame('Vehicle', $result['categoryName']);
        $this->assertNull($result['lifetimeOverride']);
    }
}
