<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class AddLabelToMultipleJSONDeserializerTest extends TestCase
{
    private AddLabelToMultipleJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $offerIdentifierDeserializer = $this->createMock(DeserializerInterface::class);

        $offerIdentifierDeserializer->expects($this->any())
            ->method('deserialize')
            ->willReturnCallback(
                function (StringLiteral $id) {
                    return new IriOfferIdentifier(
                        new Url("http://du.de/event/{$id}"),
                        $id->toNative(),
                        OfferType::event()
                    );
                }
            );

        $this->deserializer = new AddLabelToMultipleJSONDeserializer(
            $offerIdentifierDeserializer
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_add_label_to_multiple_command(): void
    {
        $json = new StringLiteral('{"label":"foo", "offers": [1, 2, 3]}');

        $expected = new AddLabelToMultiple(
            (new OfferIdentifierCollection())
                ->with(
                    new IriOfferIdentifier(
                        new Url('http://du.de/event/1'),
                        '1',
                        OfferType::event()
                    )
                )
                ->with(
                    new IriOfferIdentifier(
                        new Url('http://du.de/event/2'),
                        '2',
                        OfferType::event()
                    )
                )
                ->with(
                    new IriOfferIdentifier(
                        new Url('http://du.de/event/3'),
                        '3',
                        OfferType::event()
                    )
                ),
            new Label('foo')
        );

        $actual = $this->deserializer->deserialize($json);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_label_is_missing(): void
    {
        $json = new StringLiteral('{"offers":[]}');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "label".');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_offers_are_missing(): void
    {
        $json = new StringLiteral('{"label":"foo"}');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "offers".');

        $this->deserializer->deserialize($json);
    }
}
