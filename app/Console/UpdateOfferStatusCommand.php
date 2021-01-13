<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class UpdateOfferStatusCommand extends AbstractCommand
{
    /**
     * @var OfferType
     */
    protected $offerType;

    /**
     * @var ResultsGenerator
     */
    private $searchResultsGenerator;

    public function __construct(
        OfferType $offerType,
        CommandBusInterface $commandBus,
        SearchServiceInterface $searchService
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            ['created' => 'asc'],
            100
        );
        $this->offerType = $offerType;

        $this->setName($this->getSingularOfferType() . ':status:update');
        $this->setDescription("Batch update status of {$this->getPluralOfferType()} through SAPI 3 query.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->askForQuery($input, $output);
        $count = $this->searchResultsGenerator->count($query);
        $output->writeln("This command will update $count {$this->getPluralOfferType()}");

        $statusType = $this->askForStatusType($input, $output);
        $reasons = $this->askForReasons($input, $output);

        $status = new Status($statusType, $reasons);

        $confirmation = $this->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will update the status of {$count} {$this->getPluralOfferType()} to {$statusType->toNative()}, continue? [y/N] ",
                    true
                )
            );

        if (!$confirmation) {
            return 0;
        }

        $offers = $this->searchResultsGenerator->search($query);
        $progressBar = new ProgressBar($output, $count);

        foreach ($offers as $id => $offer) {
            $this->commandBus->dispatch(
                new UpdateStatus($id, $status)
            );
            $progressBar->advance();
        }

        $progressBar->finish();

        return 0;
    }

    private function askForQuery($input, $output): string
    {
        $question = new Question("Provide SAPI 3 query for {$this->getPluralOfferType()} to update\n");
        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForStatusType(InputInterface $input, OutputInterface $output): StatusType
    {
        $question =  new ChoiceQuestion(
            'What should be the new status?',
            [
                StatusType::available()->toNative(),
                StatusType::temporarilyUnavailable()->toNative(),
                StatusType::unavailable()->toNative(),
            ]
        );
        $question->setErrorMessage('Invalid status: %s');
        return StatusType::fromNative($this->getHelper('question')->ask($input, $output, $question));
    }

    /**
     * @return StatusReason[]
     */
    private function askForReasons(InputInterface $input, OutputInterface $output): array
    {
        $reasons = [];

        $addReasonQuestion = new ConfirmationQuestion('Do you want to add a reason? [y/N]', false);
        $addReason = $this->getHelper('question')->ask($input, $output, $addReasonQuestion);

        while ($addReason) {
            $reasons[] = $this->askForReason($input, $output);
            $addReason = $this->getHelper('question')->ask($input, $output, $addReasonQuestion);
        }

        return $reasons;
    }

    private function askForReason(InputInterface $input, OutputInterface $output): StatusReason
    {
        $languageQuestion = new Question("Language code (e.g. nl, fr, en) \n");
        $languageQuestion->setValidator(function ($answer) {
            return new Language($answer);
        });
        $languageQuestion->setMaxAttempts(2);

        /** @var Language $language */
        $language = $this->getHelper('question')->ask($input, $output, $languageQuestion);

        $reasonQuestion = new Question("Describe reason for language: {$language->getCode()}\n");
        $reason = $this->getHelper('question')->ask($input, $output, $reasonQuestion);

        return new StatusReason($language, $reason);
    }

    private function getSingularOfferType(): string
    {
        return strtolower($this->offerType->toNative());
    }

    private function getPluralOfferType(): string
    {
        return $this->getSingularOfferType() . 's';
    }
}
