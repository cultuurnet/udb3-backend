<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use DateTime;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;
    private AddressFormatterInterface $addressFormatter;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_IDENTIFICATOR = 'adms:Identifier';
    private const TYPE_ADRES = 'locn:Address';

    private const PROPERTY_LOCATIE_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_LOCATIE_LAATST_AANGEPAST = 'dcterms:modified';
    private const PROPERTY_LOCATIE_IDENTIFICATOR = 'adms:identifier';
    private const PROPERTY_LOCATIE_NAAM = 'locn:geographicName';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';

    private const PROPERTY_IDENTIFICATOR_NOTATION = 'skos:notation';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR = 'dcterms:creator';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT = 'https://fixme.com/example/dataprovider/publiq';
    private const PROPERTY_IDENTIFICATOR_NAAMRUIMTE = 'generiek:naamruimte';
    private const PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR = 'generiek:lokaleIdentificator';
    private const PROPERTY_IDENTIFICATOR_VERSIE_ID = 'generiek:versieIdentificator';

    private const PROPERTY_ADRES_POSTCODE = 'locn:postcode';
    private const PROPERTY_ADRES_GEMEENTENAAM = 'locn:postName';
    private const PROPERTY_ADRES_LAND = 'locn:adminUnitL1';
    private const PROPERTY_ADRES_VOLLEDIG_ADRES = 'locn:fullAddress';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator,
        ?AddressFormatterInterface $addressFormatter = null
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
        $this->addressFormatter = $addressFormatter ?? new DefaultAddressFormatter();
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $uri = $this->iriGenerator->iri($domainMessage->getId());
        $graph = $this->graphRepository->get($uri);
        $graph = $this->setGeneralProperties($graph, $uri, $domainMessage);

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri, $graph),
            AddressUpdated::class => fn ($e) => $this->handleAddressUpdated($e, $uri, $graph),
            AddressTranslated::class => fn ($e) => $this->handleAddressTranslated($e, $uri, $graph),
        ];

        foreach ($events as $event) {
            foreach ($eventClassToHandler as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function setGeneralProperties(Graph $graph, string $uri, DomainMessage $domainMessage): Graph
    {
        $recordedOn = $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM);
        $resource = $graph->resource($uri);

        // Set the rdf:type property, but only if it is not set before to avoid needlessly shifting it to the end of the
        // list of properties in the serialized Turtle data, since set() and setType() actually do a delete() followed
        // by add().
        if ($resource->type() !== self::TYPE_LOCATIE) {
            $resource->setType(self::TYPE_LOCATIE);
        }

        // Set the dcterms:created property if not set yet.
        // (Otherwise it would constantly update like dcterms:modified).
        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_AANGEMAAKT_OP)) {
            $resource->set(
                self::PROPERTY_LOCATIE_AANGEMAAKT_OP,
                new Literal($recordedOn, null, 'xsd:dateTime')
            );
        }

        // Always update the dcterms:modified property since it should change on every update to the resource.
        $resource->set(
            self::PROPERTY_LOCATIE_LAATST_AANGEPAST,
            new Literal($recordedOn, null, 'xsd:dateTime')
        );

        // Add an adms:Indentifier if not set yet. Like rdf:type we only do this once to avoid needlessly shifting it
        // to the end of the properties in the serialized Turtle data.
        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_IDENTIFICATOR)) {
            $identificator = $graph->newBNode();
            $identificator->setType(self::TYPE_IDENTIFICATOR);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NOTATION, $uri);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR, self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NAAMRUIMTE, new Literal($this->iriGenerator->iri(''), null, 'xsd:string'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR, new Literal($domainMessage->getId(), null, 'xsd:string'));
            $resource->add(self::PROPERTY_LOCATIE_IDENTIFICATOR, $identificator);
        }

        // Add/update the generiek:versieIdentificator inside the linked adms:Identifier on every change.
        $identificator = $resource->getResource(self::PROPERTY_LOCATIE_IDENTIFICATOR);
        $identificator->set(
            self::PROPERTY_IDENTIFICATOR_VERSIE_ID,
            new Literal($recordedOn, null, 'xsd:string')
        );

        return $graph;
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $uri): void
    {
        $this->mainLanguageRepository->save($uri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        $resource = $graph->resource($uri);

        $this->replaceLanguageValue(
            $resource,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);

        $this->replaceLanguageValue(
            $resource,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleAddressUpdated(AddressUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'))->toString();
        $this->updateAddress($graph->resource($uri), $event->getAddress(), $mainLanguage);
        $this->graphRepository->save($uri, $graph);
    }

    private function handleAddressTranslated(AddressTranslated $event, string $uri, Graph $graph): void
    {
        $this->updateAddress($graph->resource($uri), $event->getAddress(), $event->getLanguage()->getCode());
        $this->graphRepository->save($uri, $graph);
    }

    private function updateAddress(Resource $resource, Address $address, string $language): void
    {
        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_ADRES)) {
            $resource->add(self::PROPERTY_LOCATIE_ADRES, $resource->getGraph()->newBNode());
        }

        $addressResource = $resource->getResource(self::PROPERTY_LOCATIE_ADRES);
        if ($addressResource->type() !== self::TYPE_ADRES) {
            $addressResource->setType(self::TYPE_ADRES);
        }

        $countryCode = $address->getCountryCode()->toString();
        if ($addressResource->get(self::PROPERTY_ADRES_LAND) !== $countryCode) {
            $addressResource->set(self::PROPERTY_ADRES_LAND, $countryCode);
        }

        $postalCode = $address->getPostalCode()->toNative();
        if ($addressResource->get(self::PROPERTY_ADRES_POSTCODE) !== $postalCode) {
            $addressResource->set(self::PROPERTY_ADRES_POSTCODE, $postalCode);
        }

        $locality = $address->getLocality()->toNative();
        $this->replaceLanguageValue($addressResource, self::PROPERTY_ADRES_GEMEENTENAAM, $locality, $language);

        $formattedAddress = $this->addressFormatter->format($address);
        $this->replaceLanguageValue($addressResource, self::PROPERTY_ADRES_VOLLEDIG_ADRES, $formattedAddress, $language);
    }

    private function replaceLanguageValue(
        Resource $resource,
        string $property,
        string $value,
        string $language
    ): void {
        // Get all literal values for the property, and key them by their language tag.
        // This will be an empty list if no value(s) were set before for this property.
        $literalValues = $resource->allLiterals($property);
        $languages = array_map(fn (Literal $literal): string => $literal->getLang(), $literalValues);
        $literalValuePerLanguage = array_combine($languages, $literalValues);

        // Override or add the new or updated value for the language.
        // If the language was set before, it will keep its original position in the list. If the language was not set
        // before it will be appended at the end of the list.
        $literalValuePerLanguage[$language] = new Literal($value, $language);

        // Remove all existing values of the property, then (re)add them in the intended order.
        $resource->delete($property);
        $resource->addLiteral($property, array_values($literalValuePerLanguage));
    }
}
