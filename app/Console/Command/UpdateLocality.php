<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress as UpdateOrganizerAddress;
use CultuurNet\UDB3\Place\Commands\UpdateAddress as UpdatePlaceAddress;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class UpdateLocality extends AbstractCommand
{
    private const ITEM_TYPE = 'item-type';

    private const QUERY = 'query';

    private const NEW_LOCALITY = 'new-locality';

    private const DRY_RUN = 'dry-run';

    private ResultsGeneratorInterface $searchPlaceResultsGenerator;

    private DocumentRepository $documentPlaceRepository;

    private ResultsGeneratorInterface $searchOrganizerResultsGenerator;

    private DocumentRepository $documentOrganizerRepository;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchPlaceService,
        DocumentRepository $documentPlaceRepository,
        SearchServiceInterface $searchOrganizerService,
        DocumentRepository $documentOrganizerRepository
    ) {
        parent::__construct($commandBus);
        $this->searchPlaceResultsGenerator = new ResultsGenerator(
            $searchPlaceService,
            new Sorting('created', 'asc'),
            100
        );
        $this->documentPlaceRepository = $documentPlaceRepository;

        $this->searchOrganizerResultsGenerator = new ResultsGenerator(
            $searchOrganizerService,
            new Sorting('created', 'asc'),
            100
        );
        $this->documentOrganizerRepository = $documentOrganizerRepository;
    }

    public function configure(): void
    {
        $this
            ->setName('update-locality')
            ->setDescription('Update the locality of the places or organizer found by the sapi3 query')
            ->addArgument(
                self::ITEM_TYPE,
                null,
                'The ItemType which you wish to update. Only place or Organizer are accepted.'
            )
            ->addArgument(
                self::QUERY,
                null,
                'SAPI3 query for which places or organizers to update.'
            )
            ->addArgument(
                self::NEW_LOCALITY,
                null,
                'The new locality in Dutch.'
            )
            ->addOption(
                self::DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Execute the script as a dry run.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $itemType = new ItemType($input->getArgument(self::ITEM_TYPE));
        if ($itemType->sameAs($itemType::event())) {
            $output->writeln('<error>Only itemType place or organizer are accepted.</error>');
            return self::FAILURE;
        }
        $query = $input->getArgument(self::QUERY);
        $newLocality = $input->getArgument(self::NEW_LOCALITY);

        if ($query === null || $newLocality === null) {
            $output->writeln('<error>Missing argument, the correct syntax is: update-locality "item_type" "sapi3_query" "new_locality"</error>');
            return self::FAILURE;
        }

        $query = str_replace('q=', '', $query);

        $count = $this->searchPlaceResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln(sprintf('<error>No %ss found</error>', $itemType->toString()));
            return self::SUCCESS;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return self::SUCCESS;
        }

        foreach ($this->getSearchResultsGenerator($itemType)->search($query) as $item) {
            try {
                $itemId = $item->getId();

                $document = $this->getDocumentRepository($itemType)->fetch($itemId);
                $jsonLd = Json::decodeAssociatively($document->getRawBody());

                if (!isset(
                    $jsonLd['address']['nl']['streetAddress'],
                    $jsonLd['address']['nl']['postalCode'],
                    $jsonLd['address']['nl']['addressCountry'])
                ) {
                    throw new Exception('Address is incomplete in Json');
                }

                $address = new Address(
                    new Street($jsonLd['address']['nl']['streetAddress']),
                    new PostalCode($jsonLd['address']['nl']['postalCode']),
                    new Locality($newLocality),
                    new CountryCode($jsonLd['address']['nl']['addressCountry'])
                );

                $command = $this->getUpdateAddress($itemType, $itemId, $address);
                $output->writeln('Dispatching UpdateAddress for ' . $itemType->toString() . ' with id ' . $command->getItemId());

                if (!$input->getOption(self::DRY_RUN)) {
                    $this->commandBus->dispatch($command);
                }
            } catch (DocumentDoesNotExist $e) {
                $output->writeln("Skipping {$itemId}. (Could not find JSON-LD in local repository.)");
                return null;
            } catch (Exception $exception) {
                $output->writeln(sprintf('<error>%s with id: %s caused an exception: %s</error>', $itemType->toString(), $item->getId(), $exception->getMessage()));
            }
        }

        return self::SUCCESS;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    sprintf('This action will update the locality of %d items, continue? [y/N] ', $count),
                    true
                )
            );
    }

    private function getSearchResultsGenerator(ItemType $itemType): ResultsGeneratorInterface
    {
        return $itemType->sameAs(ItemType::place()) ? $this->searchPlaceResultsGenerator : $this->searchOrganizerResultsGenerator;
    }

    private function getDocumentRepository(ItemType $itemType): DocumentRepository
    {
        return $itemType->sameAs(ItemType::place()) ? $this->documentPlaceRepository : $this->documentOrganizerRepository;
    }

    /**
     * @return UpdatePlaceAddress|UpdateOrganizerAddress
     */
    private function getUpdateAddress(ItemType $itemType, string $itemId, Address $address)
    {
        return $itemType->sameAs(ItemType::place()) ? new UpdatePlaceAddress(
            $itemId,
            $address,
            new Language('nl')
        ) :
            new UpdateOrganizerAddress(
                $itemId,
                $address,
                new Language('nl')
            )
        ;
    }
}
