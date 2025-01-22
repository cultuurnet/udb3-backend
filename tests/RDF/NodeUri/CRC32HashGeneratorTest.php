<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

final class CRC32HashGeneratorTest extends TestCase
{
    private CRC32HashGenerator $hashGenerator;

    protected function setUp(): void
    {
        $this->hashGenerator = new CRC32HashGenerator();
    }

    /**
     * @test
     */
    public function it_should_generate_produces_consistent_hash(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => [
                'key2_1' => 'value2_1',
                'key2_2' => 'value2_2',
            ],
        ];

        $this->assertSame($this->hashGenerator->generate($data), $this->hashGenerator->generate($data));
    }

    /**
     * @test
     */
    public function it_should_generate_ignores_input_order(): void
    {
        $data1 = [
            'key1' => 'value1',
            'key2' => [
                'key2_1' => 'value2_1',
                'key2_2' => 'value2_2',
            ],
        ];

        $data2 = [
            'key2' => [
                'key2_2' => 'value2_2',
                'key2_1' => 'value2_1',
            ],
            'key1' => 'value1',
        ];

        $this->assertSame($this->hashGenerator->generate($data1), $this->hashGenerator->generate($data2));
    }
}
