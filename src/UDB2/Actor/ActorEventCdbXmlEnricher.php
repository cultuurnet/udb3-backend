<?php

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\DomainEvents\ActorCreated;
use CultuurNet\UDB3\UDB2\DomainEvents\ActorUpdated;
use CultuurNet\UDB3\UDB2\UrlTransformingTrait;
use CultuurNet\UDB3\UDB2\XML\XMLValidationException;
use CultuurNet\UDB3\UDB2\XML\XMLValidationServiceInterface;
use DOMDocument;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;
use XMLReader;

/**
 * Creates new event messages based on incoming UDB2 events, enriching them with
 * cdb xml so other components do not need to take care of that themselves.
 */
class ActorEventCdbXmlEnricher implements EventListener, LoggerAwareInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;
    use LoggerAwareTrait;
    use UrlTransformingTrait;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @var array
     */
    protected $logContext;

    /**
     * @var XMLValidationServiceInterface|null
     */
    protected $xmlValidationService;

    public function __construct(
        EventBus $eventBus,
        HttpClient $httpClient,
        XMLValidationServiceInterface $xmlValidationService = null
    ) {
        $this->eventBus = $eventBus;
        $this->httpClient = $httpClient;
        $this->xmlValidationService = $xmlValidationService;
        $this->logger = new NullLogger();
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function setLogContextFromDomainMessage(
        DomainMessage $domainMessage
    ) {
        $this->logContext = [];

        $metadata = $domainMessage->getMetadata()->serialize();
        if (isset($metadata['correlation_id'])) {
            $this->logContext['correlation_id'] = $metadata['correlation_id'];
        }
    }

    /**
     * @param ActorCreated $actorCreated
     * @param DomainMessage $message
     */
    private function applyActorCreated(
        ActorCreated $actorCreated,
        DomainMessage $message
    ) {
        $this->setLogContextFromDomainMessage($message);

        $xml = $this->getActorXml($actorCreated->getUrl());

        $enrichedActorCreated = ActorCreatedEnrichedWithCdbXml::fromActorCreated(
            $actorCreated,
            $xml,
            new StringLiteral(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
            )
        );

        $this->publish(
            $enrichedActorCreated,
            $message->getMetadata()
        );
    }

    /**
     * @param ActorUpdated $actorUpdated
     * @param DomainMessage $message
     */
    private function applyActorUpdated(
        ActorUpdated $actorUpdated,
        DomainMessage $message
    ) {
        $this->setLogContextFromDomainMessage($message);

        $xml = $this->getActorXml($actorUpdated->getUrl());

        $enrichedActorUpdated = ActorUpdatedEnrichedWithCdbXml::fromActorUpdated(
            $actorUpdated,
            $xml,
            new StringLiteral(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
            )
        );

        $this->publish(
            $enrichedActorUpdated,
            $message->getMetadata()
        );
    }

    /**
     * @param object $payload
     * @param Metadata $metadata
     */
    private function publish($payload, Metadata $metadata)
    {
        $message = new DomainMessage(
            UUID::generateAsString(),
            1,
            $metadata,
            $payload,
            DateTime::now()
        );

        $domainEventStream = new DomainEventStream([$message]);
        $this->eventBus->publish($domainEventStream);
    }

    /**
     * @param Url $url
     * @return StringLiteral
     * @throws ActorNotFoundException
     * @throws XMLValidationException
     */
    private function getActorXml(Url $url)
    {
        $originalUrl = $url;
        $url = $this->transformUrl($url);

        try {
            $response = $this->internalSendRequest($url);
        } catch (ActorNotFoundException $exception) {
            if ($originalUrl != $url) {
                // Fallback when url was replaced.
                $response = $this->internalSendRequest($originalUrl);
            } else {
                // No fallback just throw it.
                throw $exception;
            }
        }

        $xml = $response->getBody()->getContents();

        $this->guardValidXml($xml);

        $eventXml = $this->extractActorElement($xml);

        return new StringLiteral($eventXml);
    }

    /**
     * @param Url $url
     * @return \Psr\Http\Message\ResponseInterface
     * @throws ActorNotFoundException
     */
    private function internalSendRequest(Url $url)
    {
        $this->logger->debug('retrieving cdbxml from ' . (string) $url);

        $request = new Request(
            'GET',
            (string) $url,
            [
                'Accept' => 'application/xml',
            ]
        );

        $startTime = microtime(true);

        $response = $this->httpClient->sendRequest($request);

        $delta = round(microtime(true) - $startTime, 3) * 1000;
        $this->logger->debug('sendRequest took ' . $delta . ' ms.');

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                'unable to retrieve cdbxml, server responded with ' .
                $response->getStatusCode() . ' ' . $response->getReasonPhrase()
            );

            throw new ActorNotFoundException(
                'Unable to retrieve actor from ' . (string) $url
            );
        }

        $this->logger->debug('retrieved cdbxml');

        return $response;
    }

    /**
     * @param string $cdbXml
     * @return string
     * @throws \RuntimeException
     */
    private function extractActorElement($cdbXml)
    {
        $reader = new XMLReader();
        $reader->xml($cdbXml);

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case ($reader::ELEMENT):
                    if ($reader->localName === 'actor') {
                        $this->logger->debug('found actor in cdbxml');

                        $node = $reader->expand();
                        $dom = new DomDocument('1.0');
                        $n = $dom->importNode($node, true);
                        $dom->appendChild($n);
                        return $dom->saveXML();
                    }
            }
        }

        $this->logger->error('no actor found in cdbxml!');

        throw new \RuntimeException(
            "Actor could not be found in the Entry API response body."
        );
    }

    /**
     * @param string $xml
     */
    private function guardValidXml($xml)
    {
        if ($this->xmlValidationService) {
            $xmlErrors = $this->xmlValidationService->validate($xml);
            if (!empty($xmlErrors)) {
                $exception = XMLValidationException::fromXMLValidationErrors($xmlErrors);
                $this->logger->error(
                    'cdbxml is invalid!',
                    ['errors' => $exception->getMessage()]
                );
                throw $exception;
            }

            $this->logger->debug('cdbxml is valid');
        }
    }
}
