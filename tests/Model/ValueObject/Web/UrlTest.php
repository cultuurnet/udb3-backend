<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUrlDataProvider
     */
    public function it_should_accept_a_valid_url(string $url): void
    {
        $valueObject = new Url($url);
        $this->assertEquals($url, $valueObject->toString());
    }

    public function validUrlDataProvider(): array
    {
        return [
            'with_ssl' => [
                'url' => 'https://www.google.com',
            ],
            'without_ssl' => [
                'url' => 'http://www.google.com',
            ],
            'with_www' => [
                'url' => 'https://www.google.com',
            ],
            'without_www' => [
                'url' => 'https://google.com',
            ],
            'with_port' => [
                'url' => 'https://www.google.com:80',
            ],
            'with_ip' => [
                'url' => 'https://127.0.0.1',
            ],
            'without_domain_extension' => [
                'url' => 'https://localhost',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidUrlDataProvider
     */
    public function it_should_reject_an_invalid_url(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Url($url);
    }

    public function invalidUrlDataProvider(): array
    {
        return [
            'without_protocol' => [
                'url' => 'foo.com',
            ],
            'without_domain' => [
                'url' => 'http://',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider encodeUrlDataProvider
     */
    public function it_does_encode(string $url, string $expectedUrl): void
    {
        $this->assertEquals(
            $expectedUrl,
            (new Url($url))->toString()
        );
    }

    public function encodeUrlDataProvider(): array
    {
        return [
            [
                'https://www.domain.fr/école',
                'https://www.domain.fr/%C3%A9cole',
            ],
            [
                'https://www.domain.fr/école?q=ôpérà',
                'https://www.domain.fr/%C3%A9cole?q=%C3%B4p%C3%A9r%C3%A0',
            ],
            [
                'https://www.domain.fr/%C3%A9cole',
                'https://www.domain.fr/%C3%A9cole',
            ],
            [
                'https://www.domain.fr/hélène',
                'https://www.domain.fr/h%C3%A9l%C3%A8ne',
            ],
            [
                'http://www.domain.es/dónde-está-la-biblioteca',
                'http://www.domain.es/d%C3%B3nde-est%C3%A1-la-biblioteca',
            ],
            [
                'http://www.query.com/?a[]=[]&a[]=\'2\'',
                'http://www.query.com/?a[]=[]&a[]=\'2\'',
            ],
            [
                'http://www.query.com/#123',
                'http://www.query.com/#123',
            ]
        ];
    }
}
