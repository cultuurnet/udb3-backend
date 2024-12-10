<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\CommandHandling\CommandHandler as BroadwayCommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel;
use CultuurNet\UDB3\Label\Commands\IncludeLabel;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\Excluded;
use CultuurNet\UDB3\Label\Events\Included;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

final class CommandHandlerTest extends CommandHandlerScenarioTestCase
{
    private UUID $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private Created $created;

    public function setUp(): void
    {
        $this->uuid = new UUID('0f4c288e-dec9-4a2e-bddd-94250acfcfd2');
        $this->name = 'labelName';
        $this->visibility = Visibility::INVISIBLE();
        $this->privacy = Privacy::PRIVACY_PRIVATE();

        $this->created = new Created(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );

        // Ensure all members are created before createCommandHandler is called.
        parent::setUp();
    }

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): BroadwayCommandHandler {
        return new CommandHandler(
            new LabelRepository($eventStore, $eventBus)
        );
    }

    /**
     * @test
     */
    public function it_handles_create(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([])
            ->when(new Create(
                $this->uuid,
                new LabelName($this->name),
                $this->visibility,
                $this->privacy
            ))
            ->then([$this->created]);
    }

    /**
     * @test
     */
    public function it_handles_make_visible_when_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new MakeVisible($this->uuid))
            ->then([new MadeVisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_make_visible_when_already_visible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(new MakeVisible($this->uuid))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_make_invisible_when_visible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(new MakeInvisible($this->uuid))
            ->then([new MadeInvisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_make_invisible_when_already_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new MakeInvisible($this->uuid))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_make_public_when_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new MakePublic($this->uuid))
            ->then([new MadePublic($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_make_public_when_already_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(new MakePublic($this->uuid))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_make_private_when_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(new MakePrivate($this->uuid))
            ->then([new MadePrivate($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_make_private_when_already_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new MakePrivate($this->uuid))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_excluding_when_included(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new ExcludeLabel($this->uuid))
            ->then([new Excluded($this->uuid)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_excluding_when_already_excluded(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new Excluded($this->uuid)])
            ->when(new ExcludeLabel($this->uuid))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_including_when_excluded(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new Excluded($this->uuid)])
            ->when(new IncludeLabel($this->uuid))
            ->then([new Included($this->uuid)]);
    }

    /**
     * @test
     */
    public function it_does_not_handle_including_when_already_included(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(new IncludeLabel($this->uuid))
            ->then([]);
    }
}
