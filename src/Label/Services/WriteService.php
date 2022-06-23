<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

class WriteService implements WriteServiceInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function create(
        LegacyLabelName $name,
        Visibility $visibility,
        Privacy $privacy
    ): UUID {
        $uuid = new UUID($this->uuidGenerator->generate());

        $command = new Create(
            $uuid,
            new LabelName($name->toNative()),
            $visibility,
            $privacy
        );

        $this->commandBus->dispatch($command);

        return $uuid;
    }

    public function makeVisible(UUID $uuid): void
    {
        $this->commandBus->dispatch(new MakeVisible($uuid));
    }

    public function makeInvisible(UUID $uuid): void
    {
        $this->commandBus->dispatch(new MakeInvisible($uuid));
    }

    public function makePublic(UUID $uuid): void
    {
        $this->commandBus->dispatch(new MakePublic($uuid));
    }

    public function makePrivate(UUID $uuid): void
    {
        $this->commandBus->dispatch(new MakePrivate($uuid));
    }
}
