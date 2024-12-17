<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
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
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

final class Label extends EventSourcedAggregateRoot
{
    private Uuid $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private bool $excluded;

    public function getAggregateRootId(): string
    {
        return $this->uuid->toString();
    }

    public static function create(
        Uuid $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): Label {
        $label = new Label();

        $label->apply(new Created(
            $uuid,
            $name,
            $visibility,
            $privacy
        ));

        if (!preg_match(LabelName::REGEX_SUGGESTIONS, $name)) {
            $label->exclude();
        }

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
        if (!$this->privacy->sameAs(Privacy::public())) {
            $this->apply(new MadePublic($this->uuid, $this->name));
        }
    }

    public function makePrivate(): void
    {
        if (!$this->privacy->sameAs(Privacy::private())) {
            $this->apply(new MadePrivate($this->uuid, $this->name));
        }
    }

    public function include(): void
    {
        if ($this->excluded) {
            $this->apply(new Included($this->uuid));
        }
    }

    public function exclude(): void
    {
        if ($this->excluded === false) {
            $this->apply(new Excluded($this->uuid));
        }
    }

    public function applyCreated(Created $created): void
    {
        $this->uuid = $created->getUuid();
        $this->name = $created->getName();
        $this->visibility = $created->getVisibility();
        $this->privacy = $created->getPrivacy();
        $this->excluded = false;
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
        $this->privacy = Privacy::public();
    }

    public function applyMadePrivate(MadePrivate $madePrivate): void
    {
        $this->privacy = Privacy::private();
    }

    public function applyIncluded(): void
    {
        $this->excluded = false;
    }

    public function applyExcluded(): void
    {
        $this->excluded = true;
    }
}
