<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

class ContactPointDenormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_rejects_an_invalid_email(): void
    {
        $this->expectException(InvalidEmailAddress::class);

        (new ContactPointDenormalizer())->denormalize(['email' => ['not an email address']], ContactPoint::class);
    }

    /**
     * @test
     */
    public function it_rejects_a_url_that_the_json_schema_accepts_but_url_does_not(): void
    {
        $this->expectException(InvalidUrl::class);

        (new ContactPointDenormalizer())->denormalize(
            ['url' => ['https://www.arboretumkalmthout.be%20']],
            ContactPoint::class
        );
    }

    /**
     * @test
     */
    public function it_leaves_out_an_invalid_email_and_url_for_stored_data(): void
    {
        $this->assertEquals(
            new ContactPoint(
                null,
                new EmailAddresses(new EmailAddress('foo@bar.com')),
                new Urls(new Url('http://foo.bar'))
            ),
            ContactPointDenormalizer::forStoredData()->denormalize(
                [
                    'email' => ['foo@bar.com', 'not an email address'],
                    'url' => ['http://foo.bar', 'https://www.arboretumkalmthout.be%20'],
                ],
                ContactPoint::class
            )
        );
    }
}
