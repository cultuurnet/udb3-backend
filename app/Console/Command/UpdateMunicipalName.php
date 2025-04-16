<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Symfony\Component\Console\Input\InputOption;

final class UpdateMunicipalName extends AbstractCommand
{
    private const QUERY = 'query';

    private const OLD_MUNICIPAL_NAME = 'old-place-name';

    private const DRY_RUN = 'dry-run';

    private ResultsGeneratorInterface $searchResultsGenerator;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
    }

    public function configure(): void
    {
        $this
            ->setName('place:update-municipal-name')
            ->setDescription('Update the location of all events from the given SAPI3 query to the given new location')
            ->addArgument(
                self::QUERY,
                null,
                'SAPI3 query for which places to update.'
            )
            ->addArgument(
                self::OLD_MUNICIPAL_NAME,
                null,
                'The current name of the municipality in Dutch.'
            )
            ->addOption(
                self::DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Execute the script as a dry run.'
            );
    }
}
