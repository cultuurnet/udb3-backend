<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

final class UrlsDenormalizerTest extends TestCase
{
    private UrlsDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new UrlsDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_an_array_of_url_strings(): void
    {
        $data = [
            'https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f',
            'https://io.uitdatabank.be/places/1b2c3d4e-5f6a-7b8c-9d0e-1f2a3b4c5d6e',
        ];

        $expected = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
            new Url('https://io.uitdatabank.be/places/1b2c3d4e-5f6a-7b8c-9d0e-1f2a3b4c5d6e'),
        );

        $this->assertEquals($expected, $this->denormalizer->denormalize($data, Urls::class));
    }

    /**
     * @test
     */
    public function it_denormalizes_an_empty_array(): void
    {
        $this->assertEquals(new Urls(), $this->denormalizer->denormalize([], Urls::class));
    }

    /**
     * @test
     */
    public function it_supports_urls_class(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], Urls::class));
        $this->assertFalse($this->denormalizer->supportsDenormalization([], Url::class));
    }
}
