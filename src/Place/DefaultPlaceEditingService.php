<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class DefaultPlaceEditingService extends DefaultOfferEditingService implements PlaceEditingServiceInterface
{
    /**
     * @var Repository
     */
    protected $writeRepository;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        Repository $writeRepository
    ) {
        parent::__construct(
            $commandBus,
            $uuidGenerator,
            $readRepository,
            $commandFactory
        );

        $this->writeRepository = $writeRepository;
    }

    /**
     * @inheritdoc
     */
    public function updateAddress($id, Address $address, Language $language)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            new UpdateAddress($id, $address, $language)
        );
    }
}
