<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
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
}
