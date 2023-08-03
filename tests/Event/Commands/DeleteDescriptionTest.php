<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use PHPUnit\Framework\TestCase;

class DeleteDescriptionTest extends TestCase
{
    private const ID = '5e36d2f2-b5de-4f5e-81b3-a129d996e9b6';

    private DeleteDescription $deleteDescription;

    public function setUp(): void
    {
        $this->deleteDescription = new DeleteDescription(
            self::ID,
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
        $this->assertEquals(new Language('nl'), $this->deleteDescription->getLanguage());
    }
}
