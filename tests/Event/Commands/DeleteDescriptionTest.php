<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;

class DeleteDescriptionTest extends TestCase
{
    private const ID = 'a-random-id';

    protected DeleteDescription $deleteDescription;

    public function setUp(): void
    {
        $this->deleteDescription = new DeleteDescription(
            self::ID,
            OfferType::event(),
            new Language('nl')
        );
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $this->assertEquals(self::ID, $this->deleteDescription->getItemId());
        $this->assertEquals(OfferType::event(), $this->deleteDescription->getOfferType());
        $this->assertEquals(new Language('nl'), $this->deleteDescription->getLanguage());
    }
}
