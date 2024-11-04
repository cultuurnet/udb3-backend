<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use PHPUnit\Framework\TestCase;

class AbstractContactPointUpdatedTest extends TestCase
{
    protected AbstractContactPointUpdated $contactPointUpdated;

    protected string $itemId;

    protected ContactPoint $contactPoint;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0123456789')),
            new EmailAddresses(new EmailAddress('foo@bar.com')),
            new Urls(new Url('http://foo.bar'))
        );
        $this->contactPointUpdated = new ContactPointUpdated($this->itemId, $this->contactPoint);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0123456789')),
            new EmailAddresses(new EmailAddress('foo@bar.com')),
            new Urls(new Url('http://foo.bar'))
        );
        $expectedContactPointUpdated = new ContactPointUpdated(
            $expectedItemId,
            $expectedContactPoint
        );

        $this->assertEquals($expectedContactPointUpdated, $this->contactPointUpdated);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0123456789')),
            new EmailAddresses(new EmailAddress('foo@bar.com')),
            new Urls(new Url('http://foo.bar'))
        );

        $itemId = $this->contactPointUpdated->getItemId();
        $contactPoint = $this->contactPointUpdated->getContactPoint();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedContactPoint, $contactPoint);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
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
    public function it_can_deserialize_an_array(
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
            'abstractContactPointUpdated' => [
                [
                    'item_id' => 'madId',
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
                    'madId',
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
