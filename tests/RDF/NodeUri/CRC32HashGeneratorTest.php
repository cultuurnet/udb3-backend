<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

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
            'phone' => '+31 123 456 789',
            'email' => 'test@public.be',
            'availabilityStarts' => '2025-01-22 09:00:00',
            'availabilityEnds' => '2025-01-22 17:00:00',
            'urlLabel' => 'http://www.publiq.be',
            'extraInfo' => [
                'b' => 'b',
                'c' => 'c',
                'a' => 'a',
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
            'phone' => '+31 123 456 789',
            'email' => 'test@public.be',
            'availabilityStarts' => '2025-01-22 09:00:00',
            'availabilityEnds' => '2025-01-22 17:00:00',
            'urlLabel' => 'http://www.publiq.be',
            'extraInfo' => [
                'b' => 'b',
                'c' => 'c',
                'a' => 'a',
            ],
        ];

        $data2 = [
            'availabilityEnds' => '2025-01-22 17:00:00',
            'availabilityStarts' => '2025-01-22 09:00:00',
            'email' => 'test@public.be',
            'urlLabel' => 'http://www.publiq.be',
            'extraInfo' => [
                'a' => 'a',
                'b' => 'b',
                'c' => 'c',
            ],
            'phone' => '+31 123 456 789',
        ];

        $this->assertSame($this->hashGenerator->generate($data1), $this->hashGenerator->generate($data2));
    }
}
