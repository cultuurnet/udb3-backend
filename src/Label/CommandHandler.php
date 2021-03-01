<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler as AbstractCommandHandler;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\CreateCopy;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use ValueObjects\Identity\UUID;

class CommandHandler extends AbstractCommandHandler
{
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(
        Repository $repository
    ) {
        $this->repository = $repository;
    }

    public function handleCreate(Create $create)
    {
        $label = Label::create(
            $create->getUuid(),
            $create->getName(),
            $create->getVisibility(),
            $create->getPrivacy()
        );

        $this->save($label);
    }

    public function handleCreateCopy(CreateCopy $createCopy)
    {
        $label = Label::createCopy(
            $createCopy->getUuid(),
            $createCopy->getName(),
            $createCopy->getVisibility(),
            $createCopy->getPrivacy(),
            $createCopy->getParentUuid()
        );

        $this->save($label);
    }

    public function handleMakeVisible(MakeVisible $makeVisible)
    {
        $label = $this->load($makeVisible->getUuid());

        $label->makeVisible();

        $this->save($label);
    }

    public function handleMakeInvisible(MakeInvisible $makeInvisible)
    {
        $label = $this->load($makeInvisible->getUuid());

        $label->makeInvisible();

        $this->save($label);
    }

    public function handleMakePublic(MakePublic $makePublic)
    {
        $label = $this->load($makePublic->getUuid());

        $label->makePublic();

        $this->save($label);
    }

    public function handleMakePrivate(MakePrivate $makePrivate)
    {
        $label = $this->load($makePrivate->getUuid());

        $label->makePrivate();

        $this->save($label);
    }

    private function load(UUID $uuid): Label
    {
        /** @var Label $label */
        $label =  $this->repository->load($uuid);

        return $label;
    }

    private function save(Label $label)
    {
        $this->repository->save($label);
    }
}
