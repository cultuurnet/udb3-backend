<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaManagerInterface;
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
use CultuurNet\UDB3\Offer\Item\ItemCommandHandler;
use CultuurNet\UDB3\Offer\Item\ItemRepository;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;

final class OfferCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    protected string $id;
    protected Language $language;
    protected Title $title;
    protected string $description;
    protected PriceInfo $priceInfo;
    protected ItemCreated $itemCreated;

    /**
     * @var Repository&MockObject
     */
    protected $organizerRepository;

    /**
     * @var MediaManager|MockObject
     */
    protected $mediaManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->id = '123';
        $this->language = new Language('en');
        $this->title = new Title('English title');
        $this->description = 'English description';

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
        $this->mediaManager = $this->createMock(MediaManagerInterface::class);

        return new ItemCommandHandler(
            new ItemRepository($eventStore, $eventBus),
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    /**
     * @test
     */
    public function it_handles_approve_command_on_ready_for_validation_item(): void
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
    public function it_handles_flag_as_duplicate_command_on_ready_for_validation_item(): void
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
    public function it_handles_flag_as_inappropriate_command_on_ready_for_validation_item(): void
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
    public function it_handles_reject_command_on_ready_for_validation_item(): void
    {
        $reason = 'reject reason';

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
}
