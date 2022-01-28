<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class HostnameTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUrlDataProvider
     */
    public function it_should_accept_a_valid_hostnames(string $hostname): void
    {
        $valueObject = new Hostname($hostname);
        $this->assertEquals($hostname, $valueObject->toString());
    }

    /**
     * @test
     * @dataProvider invalidUrlDataProvider
     */
    public function it_should_reject_an_invalid_url(string $hostname): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Hostname($hostname);
    }

    public function validUrlDataProvider(): array
    {
        return [
            'with_subdomain' => [
                'hostname' => 'www.google.com',
            ],
            'without_subdomain' => [
                'hostname' => 'google.com',
            ],
            'local_domain' => [
                'hostname' => 'localhost',
            ],
        ];
    }

    public function invalidUrlDataProvider(): array
    {
        return [
            'with_portnumber' => [
                'hostname' => 'google.com:443',
            ],
            'url' => [
                'hostname' => 'https://www.publiq.be',
            ],
        ];
    }
}
