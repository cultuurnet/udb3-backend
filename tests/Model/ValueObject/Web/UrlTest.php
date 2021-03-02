<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUrlDataProvider
     *
     * @param string $url
     */
    public function it_should_accept_a_valid_url($url)
    {
        $valueObject = new Url($url);
        $this->assertEquals($url, $valueObject->toString());
    }

    /**
     * @return array
     */
    public function validUrlDataProvider()
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
     *
     * @param string $url
     */
    public function it_should_reject_an_invalid_url($url)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Url($url);
    }

    /**
     * @return array
     */
    public function invalidUrlDataProvider()
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
}
