<?php

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\History\Log;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\StringLiteral\StringLiteral;

abstract class OfferHistoryProjector
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleUnknownEvents;
    }

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();

        $eventName = get_class($event);
        $eventHandlers = $this->getEventHandlers();

        if (isset($eventHandlers[$eventName])) {
            $handler = $eventHandlers[$eventName];
            call_user_func(array($this, $handler), $event, $domainMessage);
        } else {
            $this->handleUnknownEvents($domainMessage);
        }
    }

    /**
     * @return string[]
     *   An associative array of commands and their handler methods.
     */
    protected function getEventHandlers()
    {
        $events = [];

        foreach (get_class_methods($this) as $method) {
            $matches = [];

            if (preg_match('/^apply(.+)$/', $method, $matches)) {
                $event = $matches[1];
                $classNameMethod = 'get' . $event . 'ClassName';

                if (method_exists($this, $classNameMethod)) {
                    $eventFullClassName = call_user_func(array($this, $classNameMethod));
                    $events[$eventFullClassName] = $method;
                }
            }
        }

        return $events;
    }

    /**
     * @return string
     */
    abstract protected function getLabelAddedClassName();

    /**
     * @return string
     */
    abstract protected function getLabelRemovedClassName();

    /**
     * @return string
     */
    abstract protected function getTitleTranslatedClassName();

    /**
     * @return string
     */
    abstract protected function getDescriptionTranslatedClassName();

    /**
     * @param AbstractLabelAdded $labelAdded
     * @param DomainMessage $domainMessage
     */
    protected function applyLabelAdded(
        AbstractLabelAdded $labelAdded,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $labelAdded->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                new StringLiteral("Label '{$labelAdded->getLabel()}' toegepast"),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    /**
     * @param AbstractLabelRemoved $labelRemoved
     * @param DomainMessage $domainMessage
     */
    protected function applyLabelRemoved(
        AbstractLabelRemoved $labelRemoved,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $labelRemoved->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                new StringLiteral("Label '{$labelRemoved->getLabel()}' verwijderd"),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    protected function applyTitleTranslated(
        AbstractTitleTranslated $titleTranslated,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $titleTranslated->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                new StringLiteral("Titel vertaald ({$titleTranslated->getLanguage()})"),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    protected function applyDescriptionTranslated(
        AbstractDescriptionTranslated $descriptionTranslated,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $descriptionTranslated->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                new StringLiteral("Beschrijving vertaald ({$descriptionTranslated->getLanguage()})"),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    /**
     * @param DateTime $date
     * @return \DateTime
     */
    protected function domainMessageDateToNativeDate(DateTime $date)
    {
        $dateString = $date->toString();
        return \DateTime::createFromFormat(
            DateTime::FORMAT_STRING,
            $dateString
        );
    }

    /**
     * @param $dateString
     * @return \DateTime
     */
    protected function dateFromUdb2DateString($dateString)
    {
        return \DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $dateString,
            new \DateTimeZone('Europe/Brussels')
        );
    }

    /**
     * @param Metadata $metadata
     * @return String|null
     */
    protected function getAuthorFromMetadata(Metadata $metadata)
    {
        $properties = $metadata->serialize();

        if (isset($properties['user_nick'])) {
            return new StringLiteral($properties['user_nick']);
        }
    }

    /**
     * @param Metadata $metadata
     * @return String|null
     */
    protected function getConsumerFromMetadata(Metadata $metadata)
    {
        $properties = $metadata->serialize();

        if (isset($properties['consumer']['name'])) {
            return new StringLiteral($properties['consumer']['name']);
        }
    }

    /**
     * @param string $eventId
     * @return JsonDocument
     */
    protected function loadDocumentFromRepositoryByEventId($eventId)
    {
        $historyDocument = $this->documentRepository->get($eventId);

        if (!$historyDocument) {
            $historyDocument = new JsonDocument($eventId, '[]');
        }

        return $historyDocument;
    }

    /**
     * @param string $eventId
     * @param Log[]|Log $logs
     */
    protected function writeHistory($eventId, $logs)
    {
        $historyDocument = $this->loadDocumentFromRepositoryByEventId($eventId);

        $history = $historyDocument->getBody();

        if (!is_array($logs)) {
            $logs = [$logs];
        }

        // Append most recent one to the top.
        foreach ($logs as $log) {
            array_unshift($history, $log);
        }

        $this->documentRepository->save(
            $historyDocument->withBody($history)
        );
    }

    protected function getApiKeyFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['auth_api_key'])) {
            return $properties['auth_api_key'];
        }

        return null;
    }

    protected function getApiFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['api'])) {
            return $properties['api'];
        }

        return null;
    }
}
