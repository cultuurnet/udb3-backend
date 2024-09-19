<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

class ContactPointUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        ContactPointUpdated $contactPointUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $contactPointUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        ContactPointUpdated $expectedContactPointUpdated
    ): void {
        $this->assertEquals(
            $expectedContactPointUpdated,
            ContactPointUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'contactPointUpdated' => [
                [
                    'item_id' => 'foo',
                    'contactPoint' => [
                        'phone' => [
                            '0123456789',
                            ],
                        'email' => [
                            'foo@bar.com',
                            ],
                        'url' => [
                            'http://foo.bar',
                            ],
                    ],
                ],
                new ContactPointUpdated(
                    'foo',
                    new ContactPoint(
                        new TelephoneNumbers(new TelephoneNumber('0123456789')),
                        new EmailAddresses(new EmailAddress('foo@bar.com')),
                        new Urls(new Url('http://foo.bar'))
                    )
                ),
            ],
        ];
    }
}
