<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class AddressEditor
{
    private AddressParser $addressParser;
    private RdfResourceFactory $resourceFactory;

    private const TYPE_ADRES = 'locn:Address';

    private const PROPERTY_ADRES_STRAATNAAM = 'locn:thoroughfare';
    private const PROPERTY_ADRES_HUISNUMMER = 'locn:locatorDesignator';
    private const PROPERTY_ADRES_POSTCODE = 'locn:postCode';
    private const PROPERTY_ADRES_GEMEENTENAAM = 'locn:postName';
    private const PROPERTY_ADRES_LAND = 'locn:adminUnitL1';
    private const PROPERTY_ADRES_VOLLEDIG_ADRES = 'locn:fullAddress';

    public function __construct(AddressParser $addressParser, RdfResourceFactory $resourceFactory)
    {
        $this->addressParser = $addressParser;
        $this->resourceFactory = $resourceFactory;
    }

    public function setAddress(Resource $resource, string $property, TranslatedAddress $translatedAddress): Resource
    {
        $addressResource = $this->resourceFactory->create(
            $resource,
            self::TYPE_ADRES,
            (new TranslatedAddressNormalizer())->normalize($translatedAddress)
        );

        $resource->add($property, $addressResource);

        foreach ($translatedAddress->getLanguages() as $language) {
            $address = $translatedAddress->getTranslation($language);

            $countryCode = $address->getCountryCode()->toString();
            if ($addressResource->get(self::PROPERTY_ADRES_LAND) !== $countryCode) {
                $addressResource->set(self::PROPERTY_ADRES_LAND, $countryCode);
            }

            $postalCode = $address->getPostalCode()->toString();
            if ($addressResource->get(self::PROPERTY_ADRES_POSTCODE) !== $postalCode) {
                $addressResource->set(self::PROPERTY_ADRES_POSTCODE, $postalCode);
            }

            $addressFormatter = new FullAddressFormatter();
            $formattedAddress = $addressFormatter->format($address);
            $parsedAddress = $this->addressParser->parse($formattedAddress);

            $houseNumber = $parsedAddress ? $parsedAddress->getHouseNumber() : null;
            if ($houseNumber !== null) {
                $addressResource->set(self::PROPERTY_ADRES_HUISNUMMER, $houseNumber);
            }

            $addressResource->addLiteral(
                self::PROPERTY_ADRES_VOLLEDIG_ADRES,
                new Literal($formattedAddress, $language->toString())
            );

            $addressResource->addLiteral(
                self::PROPERTY_ADRES_GEMEENTENAAM,
                new Literal($address->getLocality()->toString(), $language->toString())
            );

            if ($parsedAddress && $parsedAddress->getThoroughfare() !== null) {
                $addressResource->addLiteral(
                    self::PROPERTY_ADRES_STRAATNAAM,
                    new Literal($parsedAddress->getThoroughfare(), $language->toString())
                );
            }
        }

        return $addressResource;
    }
}
