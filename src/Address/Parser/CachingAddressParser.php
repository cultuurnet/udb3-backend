<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

use CultuurNet\UDB3\Json;
use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class CachingAddressParser implements AddressParser, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private AddressParser $addressParser;
    private Cache $cache;

    public function __construct(AddressParser $addressParser, Cache $cache)
    {
        $this->addressParser = $addressParser;
        $this->cache = $cache;
        $this->logger = new NullLogger();
    }

    public function parse(string $formattedAddress): ?ParsedAddress
    {
        $hit = $this->cache->fetch($formattedAddress);
        if ($hit !== false) {
            $this->logger->info('Cache hit for ' . $formattedAddress . ': ' . $hit);
            $data = Json::decodeAssociatively($hit);
            return new ParsedAddress(
                $data['thoroughfare'],
                $data['houseNumber'],
                $data['postalCode'],
                $data['municipality']
            );
        }

        $parsed = $this->addressParser->parse($formattedAddress);
        if ($parsed !== null) {
            $encoded = Json::encode([
                'thoroughfare' => $parsed->getThoroughfare(),
                'houseNumber' => $parsed->getHouseNumber(),
                'postalCode' => $parsed->getPostalCode(),
                'municipality' => $parsed->getMunicipality(),
            ]);
            $this->logger->info('Caching result for ' . $formattedAddress . ': ' . $encoded);
            $this->cache->save($formattedAddress, $encoded);
        }
        return $parsed;
    }
}
