<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use PHPUnit\Framework\TestCase;

final class AiGeneratedImageUrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider addMarkerProvider
     */
    public function it_adds_the_ai_generated_marker(string $url, string $expected): void
    {
        $this->assertEquals($expected, AiGeneratedImageUrl::addMarker($url));
    }

    public function addMarkerProvider(): array
    {
        return [
            'jpeg' => [
                'https://images.uitdatabank.be/a1b2c3.jpeg',
                'https://images.uitdatabank.be/a1b2c3.ai.jpeg',
            ],
            'png' => [
                'https://images.uitdatabank.be/a1b2c3.png',
                'https://images.uitdatabank.be/a1b2c3.ai.png',
            ],
            'gif in a sub path' => [
                'https://images.uitdatabank.be/2024/01/01/a1b2c3.gif',
                'https://images.uitdatabank.be/2024/01/01/a1b2c3.ai.gif',
            ],
            'marker is not duplicated' => [
                'https://images.uitdatabank.be/a1b2c3.ai.jpeg',
                'https://images.uitdatabank.be/a1b2c3.ai.jpeg',
            ],
            'url without extension is not changed' => [
                'https://images.uitdatabank.be/a1b2c3/',
                'https://images.uitdatabank.be/a1b2c3/',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider removeMarkerProvider
     */
    public function it_removes_the_ai_generated_marker(string $url, string $expected): void
    {
        $this->assertEquals($expected, AiGeneratedImageUrl::removeMarker($url));
    }

    public function removeMarkerProvider(): array
    {
        return [
            'marked url' => [
                'https://images.uitdatabank.be/a1b2c3.ai.jpeg',
                'https://images.uitdatabank.be/a1b2c3.jpeg',
            ],
            'unmarked url is not changed' => [
                'https://images.uitdatabank.be/a1b2c3.jpeg',
                'https://images.uitdatabank.be/a1b2c3.jpeg',
            ],
        ];
    }
}
