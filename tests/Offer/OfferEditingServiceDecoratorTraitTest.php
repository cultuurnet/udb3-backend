<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\Web\Url;

class OfferEditingServiceDecoratorTraitTest extends TestCase
{
    /**
     * @var OfferEditingServiceInterface|MockObject
     */
    private $decoratee;

    /**
     * @var OfferEditingServiceInterface|MockObject
     */
    private $trait;

    public function setUp()
    {
        $this->decoratee = $this->createMock(OfferEditingServiceInterface::class);

        // Only the abstract methods of the trait are mocked. All other methods
        // are available as actual implementations.
        $this->trait = $this->getMockForTrait(OfferEditingServiceDecoratorTrait::class);

        $this->trait->expects($this->any())
            ->method('getDecoratedEditingService')
            ->willReturn($this->decoratee);
    }

    /**
     * @test
     * @dataProvider editingServiceMethodDataProvider
     * @param string $method
     * @param array $arguments
     */
    public function it_delegates_each_method_to_the_decoratee($method, $arguments)
    {
        $this->decoratee->expects($this->once())
            ->method($method)
            ->willReturn(
                // The with() method doesn't accept an array of arguments, so
                // we have to manually verify that the correct arguments were
                // passed in the willReturn() method.
                function () use ($arguments) {
                    $actualArguments = func_get_args();
                    if ($actualArguments !== $arguments) {
                        $this->fail("Provided arguments don't match expected arguments.");
                    }
                    return null;
                }
            );

        // Call the decorated method on the trait.
        call_user_func_array(array($this->trait, $method), $arguments);
    }

    public function editingServiceMethodDataProvider()
    {
        return [
            [
                'addLabel',
                [
                    'offer-id-1',
                    new Label('foo'),
                ],
            ],
            [
                'removeLabel',
                [
                    'offer-id-2',
                    new Label('bar'),
                ],
            ],
            [
                'selectMainImage',
                [
                    'offer-id',
                    new Image(
                        new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
                        new MIMEType('image/jpg'),
                        new Description('my pic'),
                        new CopyrightHolder('Dirk Dirkington'),
                        Url::fromNative('http://foo.bar/media/my_pic.jpg'),
                        new Language('en')
                    ),
                ],
            ],
            [
                'updatePriceInfo',
                [
                    'offer-id',
                    $priceInfo = new PriceInfo(
                        new BasePrice(
                            Price::fromFloat(10.5),
                            Currency::fromNative('EUR')
                        )
                    ),
                ],
            ],
        ];
    }
}
