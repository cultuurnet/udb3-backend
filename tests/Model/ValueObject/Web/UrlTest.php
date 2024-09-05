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
        $this->expectException(InvalidUrl::class);
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
     */
    public function it_should_retrieve_a_domain(): void
    {
        $url = new Url('https://www.publiq.be/');

        $this->assertEquals('www.publiq.be', $url->getDomain());
    }

    /**
     * @test
     */
    public function it_should_retrieve_a_fragment_identifier(): void
    {
        $withFragmentIdentifier = new Url('https://www.publiq.be/articles#intro');
        $withoutFragmentIdentifier = new Url('https://www.publiq.be/');

        $this->assertEquals('intro', $withFragmentIdentifier->getFragmentIdentifier());
        $this->assertNull($withoutFragmentIdentifier->getFragmentIdentifier());
    }

    /**
     * @test
     */
    public function it_should_retrieve_a_path(): void
    {
        $withPath = new Url('https://www.publiq.be/articles#intro');
        $withoutPath = new Url('https://www.publiq.be');

        $this->assertEquals('/articles', $withPath->getPath());
        $this->assertNull($withoutPath->getPath());
    }

    /**
     * @test
     */
    public function it_should_retrieve_an_extending_path(): void
    {
        $url = new Url('https://www.publiq.be/articles/winter/februari#intro');

        $this->assertEquals('/articles/winter/februari', $url->getPath());
    }

    /**
     * @test
     */
    public function it_should_retrieve_a_port(): void
    {
        $withPort = new Url('https://www.publiq.be:4430');
        $withoutPort = new Url('https://www.publiq.be');

        $this->assertEquals(new PortNumber(4430), $withPort->getPort());
        $this->assertNull($withoutPort->getPort());
    }

    /**
     * @test
     */
    public function it_should_retrieve_a_query_string(): void
    {
        $withQueryString = new Url('https://www.publiq.be?article=15&style=light');
        $withoutQueryString = new Url('https://www.publiq.be');

        $this->assertEquals('article=15&style=light', $withQueryString->getQueryString());
        $this->assertNull($withoutQueryString->getQueryString());
    }
}
