<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Organizer as OrganizerAggregate;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrganizerDocumentImporter implements DocumentImporterInterface
{
    private Repository $aggregateRepository;

    private DenormalizerInterface $organizerDenormalizer;

    private CommandBus $commandBus;

    private LockedLabelRepository $lockedLabelRepository;

    public function __construct(
        Repository $aggregateRepository,
        DenormalizerInterface $organizerDenormalizer,
        CommandBus $commandBus,
        LockedLabelRepository $lockedLabelRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->organizerDenormalizer = $organizerDenormalizer;
        $this->commandBus = $commandBus;
        $this->lockedLabelRepository = $lockedLabelRepository;
    }

    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null): ?string
    {
        $id = $decodedDocument->getId();

        try {
            $this->aggregateRepository->load($id);
            $exists = true;
        } catch (AggregateNotFoundException $e) {
            $exists = false;
        }

        /* @var Organizer $import */
        $importData = $decodedDocument->getBody();
        $import = $this->organizerDenormalizer->denormalize($importData, Organizer::class);

        $adapter = new Udb3ModelToLegacyOrganizerAdapter($import);

        $mainLanguage = $adapter->getMainLanguage();
        $title = $adapter->getTitle();
        $url = $adapter->getWebsite();

        $commands = [];
        if (!$exists) {
            $organizer = OrganizerAggregate::create(
                $id,
                $mainLanguage,
                $url,
                $title
            );
            $this->aggregateRepository->save($organizer);
        } else {
            $commands[] = new UpdateTitle(
                $id,
                new Title($title->toNative()),
                new Language($mainLanguage->getCode())
            );

            $commands[] = new UpdateWebsite($id, $import->getUrl());
        }

        $commands[] = new UpdateContactPoint($id, $import->getContactPoint());

        $address = $import->getAddress();
        if ($address) {
            foreach ($address->getLanguages() as $language) {
                $commands[] = new UpdateAddress(
                    $id,
                    $address->getTranslation($language),
                    $language
                );
            }
        } else {
            $commands[] = new RemoveAddress($id);
        }

        foreach ($adapter->getTitleTranslations() as $language => $title) {
            $commands[] = new UpdateTitle($id, new Title($title->toNative()), new Language($language));
        }

        $lockedLabels = $this->lockedLabelRepository->getLockedLabelsForItem($id);
        $commands[] = (new ImportLabels($id, $import->getLabels()))
            ->withLabelsToKeepIfAlreadyOnOrganizer($lockedLabels);

        $lastCommandId = null;
        foreach ($commands as $command) {
            /** @var string|null $commandId */
            $commandId = $this->commandBus->dispatch($command);
            if ($commandId) {
                $lastCommandId = $commandId;
            }
        }

        return $lastCommandId;
    }
}
