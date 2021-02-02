<?php

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    /**
     * @test
     * @dataProvider validEmailAddressDataProvider
     *
     * @param string $email
     */
    public function it_should_accept_a_valid_email_address($email)
    {
        $emailAddress = new EmailAddress($email);
        $this->assertEquals($email, $emailAddress->toString());
    }

    /**
     * @return array
     */
    public function validEmailAddressDataProvider()
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
     *
     * @param string $email
     */
    public function it_should_reject_an_invalid_email_address($email)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string is not a valid e-mail address.');

        new EmailAddress($email);
    }

    /**
     * @return array
     */
    public function invalidEmailAddressDataProvider()
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
