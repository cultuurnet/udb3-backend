<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\RDF\NodeUri\CRC32HashGenerator;
use CultuurNet\UDB3\RDF\NodeUri\NodeUriGenerator;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class AddressEditor
{
    private AddressParser $addressParser;

    private const PROPERTY_ADRES_STRAATNAAM = 'locn:thoroughfare';
    private const PROPERTY_ADRES_HUISNUMMER = 'locn:locatorDesignator';
    private const PROPERTY_ADRES_POSTCODE = 'locn:postCode';
    private const PROPERTY_ADRES_GEMEENTENAAM = 'locn:postName';
    private const PROPERTY_ADRES_LAND = 'locn:adminUnitL1';
    private const PROPERTY_ADRES_VOLLEDIG_ADRES = 'locn:fullAddress';
    private AddressNormalizer $addressNormalizer;

    public function __construct(AddressParser $addressParser, AddressNormalizer $addressNormalizer = null)
    {
        $this->addressParser = $addressParser;
        $this->addressNormalizer = $addressNormalizer ?? new AddressNormalizer();
    }

    public function setAddress(Resource $resource, string $property, TranslatedAddress $translatedAddress): Resource
    {
        //start
        $nodeUriGenerator = new NodeUriGenerator(new CRC32HashGenerator());

        $addressJson = [];
        foreach ($translatedAddress->getLanguages() as $language) {
            $addressJson[] = $this->addressNormalizer->normalize($translatedAddress->getTranslation($language));
        }

        $addressResource = $resource->getGraph()->resource($nodeUriGenerator->generate(
            'address',
            $addressJson
        ));
        $resource->add($property, $addressResource);
        //end

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
