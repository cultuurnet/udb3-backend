<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AddLabel as OfferAddLabel;
use CultuurNet\UDB3\Organizer\Commands\AddLabel as OrganizerAddLabel;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Generator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class AddLabelToItems extends AbstractCommand
{
    private SearchServiceInterface $eventsSearchService;

    private SearchServiceInterface $placesSearchService;

    private SearchServiceInterface $organizersSearchService;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $eventsSearchService,
        SearchServiceInterface $placesSearchService,
        SearchServiceInterface $organizersSearchService
    ) {
        $this->eventsSearchService = $eventsSearchService;
        $this->placesSearchService = $placesSearchService;
        $this->organizersSearchService = $organizersSearchService;

        parent::__construct($commandBus);
    }

    protected function configure(): void
    {
        $this->setName('label:add-label-to-items')
            ->setDescription('Add labels on items from a search query.')
            ->addArgument(
                'itemType',
                InputOption::VALUE_REQUIRED,
                'The itemType for which you wish to search.'
            )
            ->addArgument(
                'query',
                InputOption::VALUE_REQUIRED,
                'The query for which you wish to add the label'
            )
            ->addArgument(
                'label',
                InputOption::VALUE_REQUIRED,
                'The label that you wish to add.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $itemType = new ItemType($input->getArgument('itemType'));
        $query = $input->getArgument('query');
        $newLabel = new Label(
            new LabelName($input->getArgument('label'))
        );
        $searchGenerator = $this->getSearchGenerator($itemType);

        $count = $searchGenerator->count($query);
        if ($this->askConfirmation($input, $output, $newLabel, $count, $itemType)) {
            $results = $searchGenerator->search($query);
            $this->addLabelToItems(
                $output,
                $count,
                $results,
                $itemType,
                $newLabel
            );
        }

        return self::SUCCESS;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, Label $label, int $count, ItemType $itemType): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    sprintf('This action will add the label %s to %u %ss, continue? [y/N] ', $label->getName()->toString(), $count, $itemType->toString()),
                    true
                )
            );
    }

    private function getSearchGenerator(ItemType $itemType): ResultsGenerator
    {
        $searchService = $this->eventsSearchService;
        if ($itemType->sameAs(ItemType::place())) {
            $searchService = $this->placesSearchService;
        }
        if ($itemType->sameAs(ItemType::organizer())) {
            $searchService = $this->organizersSearchService;
        }

        return new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
    }

    private function addLabelToItems(OutputInterface $output, int $count, Generator $results, ItemType $itemType, Label $label): void
    {
        $progressBar = new ProgressBar($output, $count);
        foreach ($results as $id => $result) {
            try {
                if ($itemType->sameAs(ItemType::organizer())) {
                    $labelCommand = new OrganizerAddLabel($id, $label);
                } else {
                    $labelCommand = new OfferAddLabel($id, $label);
                }
                $this->commandBus->dispatch(
                    $labelCommand
                );
            } catch (Exception $exception) {
               $output->writeln('Item with id: ' . $id . ' caused an exception: ' . $exception->getMessage());
            }
            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
