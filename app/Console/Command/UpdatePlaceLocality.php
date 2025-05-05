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
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
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

final class UpdatePlaceLocality extends AbstractCommand
{
    private const QUERY = 'query';

    private const NEW_LOCALITY = 'new-locality';

    private const DRY_RUN = 'dry-run';

    private ResultsGeneratorInterface $searchResultsGenerator;

    private DocumentRepository $documentRepository;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
        $this->documentRepository = $documentRepository;
    }

    public function configure(): void
    {
        $this
            ->setName('place:update-locality')
            ->setDescription('Update the locality of the places found by the sapi3 query')
            ->addArgument(
                self::QUERY,
                null,
                'SAPI3 query for which places to update.'
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
        $query = $input->getArgument(self::QUERY);
        $newLocality = $input->getArgument(self::NEW_LOCALITY);

        if ($query === null || $newLocality === null) {
            $output->writeln('<error>Missing argument, the correct syntax is: place:update-locality "sapi3_query" "new_locality"</error>');
            return self::FAILURE;
        }

        $query = str_replace('q=', '', $query);

        $count = $this->searchResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln('<error>No places found</error>');
            return self::SUCCESS;
        }


        if (!$this->askConfirmation($input, $output, $count)) {
            return self::SUCCESS;
        }

        foreach ($this->searchResultsGenerator->search($query) as $place) {
            try {
                $placeId = $place->getId();

                $document = $this->documentRepository->fetch($placeId);
                $jsonLd = Json::decodeAssociatively($document->getRawBody());

                if (!isset(
                    $jsonLd['address']['nl']['streetAddress'],
                    $jsonLd['address']['nl']['postalCode'],
                    $jsonLd['address']['nl']['addressCountry'])
                ) {
                    throw new Exception('Address is incomplete in Json');
                }

                $command = new UpdateAddress(
                    $place->getId(),
                    new Address(
                        new Street($jsonLd['address']['nl']['streetAddress']),
                        new PostalCode($jsonLd['address']['nl']['postalCode']),
                        new Locality($newLocality),
                        new CountryCode($jsonLd['address']['nl']['addressCountry'])
                    ),
                    new Language('nl')
                );
                $output->writeln('Dispatching UpdateAddress for place with id ' . $command->getItemId());

                if (!$input->getOption(self::DRY_RUN)) {
                    $this->commandBus->dispatch($command);
                }
            } catch (DocumentDoesNotExist $e) {
                $output->writeln("Skipping {$placeId}. (Could not find JSON-LD in local repository.)");
                return null;
            } catch (Exception $exception) {
                $output->writeln(sprintf('<error>Place with id: %s caused an exception: %s</error>', $place->getId(), $exception->getMessage()));
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
                    sprintf('This action will update the locality of %d places, continue? [y/N] ', $count),
                    true
                )
            );
    }
}
