<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Label;
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
     * @var ExternalOfferEditingServiceInterface
     */
    private $externalOfferEditingService;

    public function __construct(
        ResultsGeneratorInterface $resultsGenerator,
        ExternalOfferEditingServiceInterface $externalOfferEditingService
    ) {
        $this->resultsGenerator = $resultsGenerator;
        $this->externalOfferEditingService = $externalOfferEditingService;

        $this->setLogger(new NullLogger());
    }

    /**
     * @param AddLabelToQuery $addLabelToQuery
     */
    public function handleAddLabelToQuery(AddLabelToQuery $addLabelToQuery)
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

    /**
     * @param AddLabelToMultiple $addLabelToMultiple
     */
    public function handleAddLabelToMultiple(AddLabelToMultiple $addLabelToMultiple)
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

    /**
     * @param IriOfferIdentifier $offerIdentifier
     * @param Label $label
     * @param string|null $originalCommandName
     *   Original command name, for logging purposes if an entity is not found.
     */
    private function label(
        IriOfferIdentifier $offerIdentifier,
        Label $label,
        $originalCommandName = null
    ) {
        try {
            $this->externalOfferEditingService->addLabel($offerIdentifier, $label);
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
