<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Label\Events\CopyCreated;
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

class Label extends EventSourcedAggregateRoot
{
    private UUID $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private bool $excluded;

    public function getAggregateRootId(): string
    {
        return $this->uuid->toString();
    }

    public static function create(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy,
        bool $excluded = false
    ): Label {
        $label = new Label();

        $label->apply(new Created(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $excluded
        ));

        return $label;
    }

    public static function createCopy(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid
    ): Label {
        $label = new Label();

        $label->apply(new CopyCreated(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $parentUuid
        ));

        return $label;
    }

    public function makeVisible(): void
    {
        if (!$this->visibility->sameAs(Visibility::VISIBLE())) {
            $this->apply(new MadeVisible($this->uuid, $this->name));
        }
    }

    public function makeInvisible(): void
    {
        if (!$this->visibility->sameAs(Visibility::INVISIBLE())) {
            $this->apply(new MadeInvisible($this->uuid, $this->name));
        }
    }

    public function makePublic(): void
    {
        if (!$this->privacy->sameAs(Privacy::PRIVACY_PUBLIC())) {
            $this->apply(new MadePublic($this->uuid, $this->name));
        }
    }

    public function makePrivate(): void
    {
        if (!$this->privacy->sameAs(Privacy::PRIVACY_PRIVATE())) {
            $this->apply(new MadePrivate($this->uuid, $this->name));
        }
    }

    public function include(): void
    {
        if ($this->excluded) {
            $this->apply(new Included($this->uuid, $this->name));
        }
    }

    public function exclude(): void
    {
        if ($this->excluded === false) {
            $this->apply(new Excluded($this->uuid, $this->name));
        }
    }

    public function applyCreated(Created $created): void
    {
        $this->uuid = $created->getUuid();
        $this->name = $created->getName();
        $this->visibility = $created->getVisibility();
        $this->privacy = $created->getPrivacy();
        $this->excluded = $created->isExcluded();
    }

    public function applyCopyCreated(CopyCreated $copyCreated): void
    {
        $this->applyCreated($copyCreated);
    }

    public function applyMadeVisible(MadeVisible $madeVisible): void
    {
        $this->visibility = Visibility::VISIBLE();
    }

    public function applyMadeInvisible(MadeInvisible $madeInvisible): void
    {
        $this->visibility = Visibility::INVISIBLE();
    }

    public function applyMadePublic(MadePublic $madePublic): void
    {
        $this->privacy = Privacy::PRIVACY_PUBLIC();
    }

    public function applyMadePrivate(MadePrivate $madePrivate): void
    {
        $this->privacy = Privacy::PRIVACY_PRIVATE();
    }
}
