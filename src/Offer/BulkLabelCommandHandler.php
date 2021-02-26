<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultiple;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQuery;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BulkLabelCommandHandler extends Udb3CommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResultsGeneratorInterface
     */
    private $resultsGenerator;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        ResultsGeneratorInterface $resultsGenerator,
        CommandBus $commandBus
    ) {
        $this->resultsGenerator = $resultsGenerator;
        $this->commandBus = $commandBus;

        $this->setLogger(new NullLogger());
    }

    public function handleAddLabelToQuery(AddLabelToQuery $addLabelToQuery): void
    {
        $label = $addLabelToQuery->getLabel();
        $query = $addLabelToQuery->getQuery();

        foreach ($this->resultsGenerator->search($query) as $result) {
            /* @var IriOfferIdentifier $result */
            $this->label(
                $result,
                $label,
                AddLabelToQuery::class
            );
        }
    }

    public function handleAddLabelToMultiple(AddLabelToMultiple $addLabelToMultiple): void
    {
        $label = $addLabelToMultiple->getLabel();

        $offerIdentifiers = $addLabelToMultiple->getOfferIdentifiers()
            ->toArray();

        foreach ($offerIdentifiers as $offerIdentifier) {
            $this->label(
                $offerIdentifier,
                $label,
                AddLabelToMultiple::class
            );
        }
    }

    private function label(
        IriOfferIdentifier $offerIdentifier,
        Label $label,
        string $originalCommandName = null
    ): void {
        try {
            $this->commandBus->dispatch(
                new AddLabel($offerIdentifier->getId(), $label)
            );
        } catch (\Exception $e) {
            $logContext = [
                'iri' => (string) $offerIdentifier->getIri(),
                'command' => $originalCommandName,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];

            $this->logger->error('bulk_label_command_exception', $logContext);
        }
    }
}
