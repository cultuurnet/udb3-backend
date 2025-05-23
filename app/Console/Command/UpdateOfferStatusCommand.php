<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class UpdateOfferStatusCommand extends AbstractCommand
{
    protected OfferType $offerType;

    private ResultsGenerator $searchResultsGenerator;

    public function __construct(
        OfferType $offerType,
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
        $this->offerType = $offerType;

        $this->setName($this->getSingularOfferType() . ':status:update');
        $this->setDescription("Batch update status of {$this->getPluralOfferType()} through SAPI 3 query.");

        parent::__construct($commandBus);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->askForQuery($input, $output);
        $count = $this->searchResultsGenerator->count($query);
        $output->writeln("This command will update $count {$this->getPluralOfferType()}");

        $statusType = $this->askForStatusType($input, $output);
        $reasons = $this->askForReasons($input, $output);

        $translatedReasons = null;
        foreach ($reasons as $reason) {
            if ($translatedReasons === null) {
                $translatedReasons = new TranslatedStatusReason(
                    $reason->getOriginalLanguage(),
                    $reason->getOriginalValue()
                );
            } else {
                $translatedReasons = $translatedReasons->withTranslation(
                    $reason->getOriginalLanguage(),
                    $reason->getOriginalValue()
                );
            }
        }

        $status = new Status($statusType, $translatedReasons);

        if ($count <= 0) {
            $output->writeln('Query found 0 ' . $this->getPluralOfferType() . ' to update');
            return 0;
        }

        $confirmation = $this->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will update the status of {$count} {$this->getPluralOfferType()} to {$statusType->toString()}, continue? [y/N] ",
                    false
                )
            );

        if (!$confirmation) {
            return 0;
        }

        $exceptions = [];
        $offers = $this->searchResultsGenerator->search($query);
        $progressBar = new ProgressBar($output, $count);

        foreach ($offers as $id => $offer) {
            try {
                $this->commandBus->dispatch(
                    new UpdateStatus($id, $status)
                );
            } catch (Exception $exception) {
                $exceptions[$id] = 'Offer with id: ' . $id . ' caused an exception: ' . $exception->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln('');
        foreach ($exceptions as $exception) {
            $output->writeln($exception);
        }

        return 0;
    }

    private function askForQuery(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question("Provide SAPI 3 query for {$this->getPluralOfferType()} to update\n");
        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForStatusType(InputInterface $input, OutputInterface $output): StatusType
    {
        $question =  new ChoiceQuestion(
            'What should be the new status?',
            [
                StatusType::Available()->toString(),
                StatusType::TemporarilyUnavailable()->toString(),
                StatusType::Unavailable()->toString(),
            ]
        );
        $question->setErrorMessage('Invalid status: %s');
        return new StatusType($this->getHelper('question')->ask($input, $output, $question));
    }

    /**
     * @return TranslatedStatusReason[]
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

    private function askForReason(InputInterface $input, OutputInterface $output): TranslatedStatusReason
    {
        $languageQuestion = new Question("Language code (e.g. nl, fr, en) \n");
        $languageQuestion->setValidator(fn ($answer) => new Language($answer));
        $languageQuestion->setMaxAttempts(2);

        /** @var Language $language */
        $language = $this->getHelper('question')->ask($input, $output, $languageQuestion);

        $reasonQuestion = new Question("Describe reason for language: {$language->getCode()}\n");
        $reason = $this->getHelper('question')->ask($input, $output, $reasonQuestion);

        return new TranslatedStatusReason($language, $reason);
    }

    private function getSingularOfferType(): string
    {
        return strtolower($this->offerType->toString());
    }

    private function getPluralOfferType(): string
    {
        return $this->getSingularOfferType() . 's';
    }
}
