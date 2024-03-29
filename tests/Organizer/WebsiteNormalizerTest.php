<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class WebsiteNormalizerTest extends TestCase
{
    /**
     * @dataProvider organizerWebsiteUrlProvider
     * @test
     */
    public function it_normalizes_website_urls(
        string $given,
        string $expected
    ): void {
        $websiteNormalizer = new WebsiteNormalizer();

        $this->assertEquals($expected, $websiteNormalizer->normalizeUrl(new Url($given)));
    }

    public function organizerWebsiteUrlProvider(): array
    {
        return [
            'http://decorridor.be' => [
                'http://decorridor.be',
                'decorridor.be',
            ],
            'https://decorridor.be' => [
                'https://decorridor.be',
                'decorridor.be',
            ],
            'http://decorridor.be/' => [
                'http://decorridor.be/',
                'decorridor.be',
            ],
            'https://decorridor.be/' => [
                'https://decorridor.be/',
                'decorridor.be',
            ],
            'http://www.decorridor.be' => [
                'http://www.decorridor.be',
                'decorridor.be',
            ],
            'https://www.decorridor.be' => [
                'https://www.decorridor.be',
                'decorridor.be',
            ],
            'http://www.decorridor.be/' => [
                'http://www.decorridor.be/',
                'decorridor.be',
            ],
            'https://www.decorridor.be/' => [
                'https://www.decorridor.be/',
                'decorridor.be',
            ],
            'HTtps://www.decorridor.be/' => [
                'HTtps://www.decorridor.be/',
                'decorridor.be',
            ],
            'https://www.decorridor.be/path' => [
                'https://www.decorridor.be/path',
                'decorridor.be/path',
            ],
            'https://www.decorridor.be/trailing-slash/' => [
                'https://www.decorridor.be/trailing-slash/',
                'decorridor.be/trailing-slash',
            ],
            'https://www.decorridor.be/?query=true' => [
                'https://www.decorridor.be/?query=true',
                'decorridor.be/?query=true',
            ],
            'https://www.decorridor.be/#fragment' => [
                'https://www.decorridor.be/#fragment',
                'decorridor.be/#fragment',
            ],
        ];
    }
}
