<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class GoogleMapsAddressParser implements AddressParser, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Geocoder $geocoder;

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    public function parse(string $formattedAddress): ?ParsedAddress
    {
        try {
            $addresses = $this->geocoder->geocode($formattedAddress);

            $address = $addresses->first();
            if ($address->getStreetNumber() === null) {
                $this->logger->warning('No street number found for address: "' . $formattedAddress . '"');
                return null;
            }

            return new ParsedAddress(
                $address->getStreetName(),
                $address->getStreetNumber(),
                $address->getPostalCode(),
                $address->getLocality()
            );
        } catch (Exception $exception) {
            $this->logger->warning(
                'No results for address: "' . $formattedAddress . '". Exception message: ' . $exception->getMessage()
            );
            return null;
        }
    }
}
