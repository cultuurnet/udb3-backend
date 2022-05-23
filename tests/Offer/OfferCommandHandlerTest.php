<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Approve;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Reject;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\ItemCommandHandler;
use CultuurNet\UDB3\Offer\Item\ItemRepository;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Title;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use CultuurNet\UDB3\StringLiteral;

class OfferCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Title
     */
    protected $title;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var PriceInfo
     */
    protected $priceInfo;

    /**
     * @var ItemCreated
     */
    protected $itemCreated;

    /**
     * @var Repository|MockObject
     */
    protected $organizerRepository;

    /**
     * @var MediaManager|MockObject
     */
    protected $mediaManager;

    public function setUp()
    {
        parent::setUp();

        $this->id = '123';
        $this->language = new Language('en');
        $this->title = new Title('English title');
        $this->description = new StringLiteral('English description');

        $this->itemCreated = new ItemCreated(
            $this->id,
            new Language('nl')
        );

        $this->priceInfo = new PriceInfo(
            new BasePrice(
                new Money(1050, new Currency('EUR'))
            )
        );
    }

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): ItemCommandHandler {
        $this->organizerRepository = $this->createMock(Repository::class);
        $this->mediaManager = $this->createMock(MediaManager::class);

        return new ItemCommandHandler(
            new ItemRepository($eventStore, $eventBus),
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    /**
     * @test
     */
    public function it_handles_approve_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Approve($this->id))
            ->then([
                new Approved($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_duplicate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsDuplicate($this->id))
            ->then([
                new FlaggedAsDuplicate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_inappropriate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsInappropriate($this->id))
            ->then([
                new FlaggedAsInappropriate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_reject_command_on_ready_for_validation_item()
    {
        $reason = new StringLiteral('reject reason');

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Reject($this->id, $reason))
            ->then([
                new Rejected($this->id, $reason),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_delete_current_organizer_commands()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new OrganizerUpdated($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            )
            ->when(
                new DeleteCurrentOrganizer($this->id)
            )
            ->then(
                [
                    new OrganizerDeleted($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            );
    }
}
