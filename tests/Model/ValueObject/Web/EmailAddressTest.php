<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    /**
     * @test
     * @dataProvider validEmailAddressDataProvider
     */
    public function it_should_accept_a_valid_email_address(string $email): void
    {
        $emailAddress = new EmailAddress($email);
        $this->assertEquals($email, $emailAddress->toString());
    }

    public function validEmailAddressDataProvider(): array
    {
        return [
            'regular' => [
                'email' => 'test@foo.com',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidEmailAddressDataProvider
     */
    public function it_should_reject_an_invalid_email_address(string $email): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string is not a valid e-mail address.');

        new EmailAddress($email);
    }

    public function invalidEmailAddressDataProvider(): array
    {
        return [
            'without_at' => [
                'email' => 'foo.com',
            ],
            'without_domain' => [
                'email' => 'test@',
            ],
            'without_domain_extension' => [
                'email' => 'test@localhost',
            ],
            'with_ip' => [
                'email' => 'test@127.0.0.1',
            ],
        ];
    }
}
