<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use EasyRdf\Graph;
use EasyRdf\Resource;

final class AddressEditor
{
    private const TYPE_ADRES = 'locn:Address';

    private const PROPERTY_ADRES_STRAATNAAM = 'locn:thoroughfare';
    private const PROPERTY_ADRES_HUISNUMMER = 'locn:locatorDesignator';
    private const PROPERTY_ADRES_POSTCODE = 'locn:postcode';
    private const PROPERTY_ADRES_GEMEENTENAAM = 'locn:postName';
    private const PROPERTY_ADRES_LAND = 'locn:adminUnitL1';
    private const PROPERTY_ADRES_VOLLEDIG_ADRES = 'locn:fullAddress';

    private Graph $graph;
    private MainLanguageRepository $mainLanguageRepository;
    private AddressParser $addressParser;
    private AddressFormatter $addressFormatter;

    private function __construct(
        Graph $graph,
        MainLanguageRepository $mainLanguageRepository,
        AddressParser $addressParser
    ) {
        $this->graph = $graph;
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->addressParser = $addressParser;
        $this->addressFormatter = new FullAddressFormatter();
    }

    public static function for(
        Graph $graph,
        MainLanguageRepository $mainLanguageRepository,
        AddressParser $addressParser
    ): self {
        return new self($graph, $mainLanguageRepository, $addressParser);
    }

    public function addAddress(
        string $resourceUri,
        Address $address,
        string $property
    ): void {
        $resource = $this->graph->resource($resourceUri);

        if (!$resource->hasProperty($property)) {
            $resource->add($property, $resource->getGraph()->newBNode());
        }

        $addressResource = $resource->getResource($property);
        if ($addressResource->type() !== self::TYPE_ADRES) {
            $addressResource->setType(self::TYPE_ADRES);
        }

        $countryCode = $address->getCountryCode()->toString();
        if ($addressResource->get(self::PROPERTY_ADRES_LAND) !== $countryCode) {
            $addressResource->set(self::PROPERTY_ADRES_LAND, $countryCode);
        }

        $postalCode = $address->getPostalCode()->toString();
        if ($addressResource->get(self::PROPERTY_ADRES_POSTCODE) !== $postalCode) {
            $addressResource->set(self::PROPERTY_ADRES_POSTCODE, $postalCode);
        }

        $parsedAddress = $this->addressParser->parse(
            $this->addressFormatter->format(LegacyAddress::fromUdb3ModelAddress($address))
        );
        $houseNumber = $parsedAddress ? $parsedAddress->getHouseNumber() : null;
        if ($houseNumber !== null) {
            $addressResource->set(self::PROPERTY_ADRES_HUISNUMMER, $houseNumber);
        }

        $mainLanguage = $this->mainLanguageRepository->get($resourceUri, new Language('nl'))->toString();
        $this->updateTranslatableAddress($resourceUri, $address, $mainLanguage, $property);
    }

    public function updateTranslatableAddress(
        string $resourceUri,
        Address $address,
        string $language,
        string $property
    ): void {
        $resource = $this->graph->resource($resourceUri);

        /** @var Resource|null $addressResource */
        $addressResource = $resource->getResource($property);
        if ($addressResource === null) {
            // This is a case that should not happen in reality, since every new place should get a locn:Address via
            // handleAddressUpdated().
            return;
        }

        // The locn:fullAddress predicate is set per language since it contains language-specific info like the street
        // name and municipality name. It is included because not all addresses can be parsed into the expected
        // thoroughfare and house number, so in those cases at least the full address is completed and consumers can
        // always try to parse it themselves if wanted.
        $graphEditor = GraphEditor::for($addressResource->getGraph())->replaceLanguageValue(
            $addressResource->getUri(),
            self::PROPERTY_ADRES_VOLLEDIG_ADRES,
            $this->addressFormatter->format(LegacyAddress::fromUdb3ModelAddress($address)),
            $language
        );

        // Always set the locn:postName predicate based on the Address, not the ParsedAddress, because in some cases an
        // address cannot be parsed (e.g. it's outside of Belgium, or the street address could not be parsed/found), but
        // the original address always contains the right municipality in any case.
        $graphEditor->replaceLanguageValue(
            $addressResource->getUri(),
            self::PROPERTY_ADRES_GEMEENTENAAM,
            $address->getLocality()->toString(),
            $language
        );

        $parsedAddress = $this->addressParser->parse(
            $this->addressFormatter->format(LegacyAddress::fromUdb3ModelAddress($address))
        );
        // Only set the locn:thoroughfare predicate based on the ParsedAddress (if given), not the street in the
        // original Address, because locn:thoroughfare MUST NOT contain a house number. If there is no ParsedAddress
        // remove the value for the given language instead since it will probably be outdated (if set previously).
        // Keep in mind that locn:thoroughfare is optional.
        if ($parsedAddress && $parsedAddress->getThoroughfare() !== null) {
            $graphEditor->replaceLanguageValue(
                $addressResource->getUri(),
                self::PROPERTY_ADRES_STRAATNAAM,
                $parsedAddress->getThoroughfare(),
                $language
            );
        } else {
            $graphEditor->deleteLanguageValue(
                $addressResource->getUri(),
                self::PROPERTY_ADRES_STRAATNAAM,
                $language
            );
        }
    }

    public static function fromLegacyAddress(LegacyAddress $legacyAddress): Address
    {
        return new Address(
            new Street($legacyAddress->getStreetAddress()->toNative()),
            new PostalCode($legacyAddress->getPostalCode()->toNative()),
            new Locality($legacyAddress->getLocality()->toNative()),
            $legacyAddress->getCountryCode()
        );
    }
}
