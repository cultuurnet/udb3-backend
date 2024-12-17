<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\Excluded;
use CultuurNet\UDB3\Label\Events\Included;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class LabelTest extends AggregateRootScenarioTestCase
{
    private Uuid $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private Created $created;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new Uuid('b36ec769-2ec1-4a13-96cd-27d7a1f1e963');
        $this->name = 'labelName';
        $this->visibility = Visibility::INVISIBLE();
        $this->privacy = Privacy::private();

        $this->created = new Created(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );
    }

    protected function getAggregateRootClass(): string
    {
        return Label::class;
    }

    /**
     * @test
     */
    public function it_can_create_a_new_label(): void
    {
        $this->scenario
            ->when(
                fn () => Label::create(
                    $this->uuid,
                    $this->name,
                    $this->visibility,
                    $this->privacy
                )
            )
            ->then([$this->created]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_visible_when_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn (Label $label) => $label->makeVisible())
            ->then([new MadeVisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_visible_after_a_make_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(function (Label $label): void {
                $label->makeInvisible();
                $label->makeVisible();
            })
            ->then([
                new MadeInvisible($this->uuid, $this->name),
                new MadeVisible($this->uuid, $this->name),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_invisible_when_already_visible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(fn (Label $label) => $label->makeVisible())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_invisible_when_visible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(fn (Label $label) => $label->makeInvisible())
            ->then([new MadeInvisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_invisible_when_already_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn (Label $label) => $label->makeInvisible())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_public_when_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn ($label) => $label->makePublic())
            ->then([new MadePublic($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_public_after_a_make_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(function (Label $label): void {
                $label->makePrivate();
                $label->makePublic();
            })
            ->then([
                new MadePrivate($this->uuid, $this->name),
                new MadePublic($this->uuid, $this->name),
            ]);
    }

    /**
     * @test
     */
    public function it_creates_an_invalid_label_as_excluded(): void
    {
        $uuid = new Uuid('0f893a61-fb77-47b6-9c8d-e4e631afa9b3');

        $this->scenario
            ->when(
                fn () => Label::create(
                    $uuid,
                    'labelName#$',
                    Visibility::VISIBLE(),
                    Privacy::public()
                )
            )
            ->then([
                new Created(
                    $uuid,
                    'labelName#$',
                    Visibility::VISIBLE(),
                    Privacy::public()
                ),
                new Excluded($uuid),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_public_when_already_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(fn ($label) => $label->makePublic())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_private_when_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(fn ($label) => $label->makePrivate())
            ->then([new MadePrivate($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_private_when_already_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn ($label) => $label->makePrivate())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_exclude_an_included_label(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn (Label $label) => $label->exclude())
            ->then([new Excluded($this->uuid)]);
    }

    /**
     * @test
     */
    public function it_does_not_exclude_an_already_excluded_label(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new Excluded($this->uuid)])
            ->when(fn (Label $label) => $label->exclude())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_exclude_after_including_it(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new Excluded($this->uuid)])
            ->when(function (Label $label): void {
                $label->include();
                $label->exclude();
            })
            ->then(
                [
                    new Included($this->uuid),
                    new Excluded($this->uuid),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_include_an_excluded_label(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created, new Excluded($this->uuid)])
            ->when(fn (Label $label) => $label->include())
            ->then([new Included($this->uuid)]);
    }

    /**
     * @test
     */
    public function it_does_not_include_an_already_included_label(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(fn (Label $label) => $label->include())
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_include_after_excluding_it(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->created])
            ->when(function (Label $label): void {
                $label->exclude();
                $label->include();
            })
            ->then(
                [
                    new Excluded($this->uuid),
                    new Included($this->uuid),
                ]
            );
    }
}
