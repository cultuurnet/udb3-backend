<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Label\Events\CopyCreated;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class LabelTest extends AggregateRootScenarioTestCase
{
    private UUID $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private UUID $parentUuid;

    private Created $created;

    private CopyCreated $copyCreated;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new UUID('b36ec769-2ec1-4a13-96cd-27d7a1f1e963');
        $this->name = 'labelName';
        $this->visibility = Visibility::INVISIBLE();
        $this->privacy = Privacy::PRIVACY_PRIVATE();
        $this->parentUuid = new UUID('efaddd1d-837c-49ea-81d0-f4882fdf4123');

        $this->created = new Created(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );

        $this->copyCreated = new CopyCreated(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy,
            $this->parentUuid
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
            ->when(function () {
                return Label::create(
                    $this->uuid,
                    $this->name,
                    $this->visibility,
                    $this->privacy
                );
            })
            ->then([$this->created]);
    }

    /**
     * @test
     */
    public function it_can_create_a_copied_label(): void
    {
        $this->scenario
            ->when(function () {
                return Label::createCopy(
                    $this->uuid,
                    $this->name,
                    $this->visibility,
                    $this->privacy,
                    $this->parentUuid
                );
            })
            ->then([$this->copyCreated]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_visible_when_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makeVisible();
            })
            ->then([new MadeVisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_visible_after_a_make_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
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
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makeVisible();
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_invisible_when_visible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadeVisible($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makeInvisible();
            })
            ->then([new MadeInvisible($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_invisible_when_already_invisible(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makeInvisible();
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_public_when_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makePublic();
            })
            ->then([new MadePublic($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_public_after_a_make_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
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
    public function it_does_not_make_a_label_public_when_already_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makePublic();
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_make_a_label_private_when_public(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created, new MadePublic($this->uuid, $this->name)])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makePrivate();
            })
            ->then([new MadePrivate($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_does_not_make_a_label_private_when_already_private(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([$this->created])
            ->when(function ($label) {
                /** @var Label $label */
                $label->makePrivate();
            })
            ->then([]);
    }
}
