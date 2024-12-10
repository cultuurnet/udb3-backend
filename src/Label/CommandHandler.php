<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler as AbstractCommandHandler;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel;
use CultuurNet\UDB3\Label\Commands\IncludeLabel;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class CommandHandler extends AbstractCommandHandler
{
    private Repository $repository;

    public function __construct(
        Repository $repository
    ) {
        $this->repository = $repository;
    }

    public function handleCreate(Create $create): void
    {
        $label = Label::create(
            $create->getUuid(),
            $create->getName()->toString(),
            $create->getVisibility(),
            $create->getPrivacy()
        );

        $this->save($label);
    }

    public function handleMakeVisible(MakeVisible $makeVisible): void
    {
        $label = $this->load($makeVisible->getUuid());

        $label->makeVisible();

        $this->save($label);
    }

    public function handleMakeInvisible(MakeInvisible $makeInvisible): void
    {
        $label = $this->load($makeInvisible->getUuid());

        $label->makeInvisible();

        $this->save($label);
    }

    public function handleMakePublic(MakePublic $makePublic): void
    {
        $label = $this->load($makePublic->getUuid());

        $label->makePublic();

        $this->save($label);
    }

    public function handleMakePrivate(MakePrivate $makePrivate): void
    {
        $label = $this->load($makePrivate->getUuid());

        $label->makePrivate();

        $this->save($label);
    }

    public function handleIncludeLabel(IncludeLabel $includeLabel): void
    {
        $label = $this->load($includeLabel->getUuid());

        $label->include();

        $this->save($label);
    }

    public function handleExcludeLabel(ExcludeLabel $excludeLabel): void
    {
        $label = $this->load($excludeLabel->getUuid());

        $label->exclude();

        $this->save($label);
    }

    private function load(Uuid $uuid): Label
    {
        /** @var Label $label */
        $label =  $this->repository->load($uuid->toString());

        return $label;
    }

    private function save(Label $label): void
    {
        $this->repository->save($label);
    }
}
