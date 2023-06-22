<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

class ActorItemFactory implements ActorItemFactoryInterface
{
    private string $namespaceUri;

    public function __construct(string $namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function createFromCdbXml(string $cdbXml): \CultureFeed_Cdb_Item_Actor
    {
        return self::createActorFromCdbXml($this->namespaceUri, $cdbXml);
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    public static function createActorFromCdbXml(string $namespaceUri, string $cdbXml): \CultureFeed_Cdb_Item_Actor
    {
        $udb2SimpleXml = new \SimpleXMLElement(
            $cdbXml,
            0,
            false,
            $namespaceUri
        );

        // The actor might be wrapped in a <cdbxml> tag.
        if ($udb2SimpleXml->getName() == 'cdbxml' && isset($udb2SimpleXml->actor)) {
            $udb2SimpleXml = $udb2SimpleXml->actor;
        }

        return \CultureFeed_Cdb_Item_Actor::parseFromCdbXml(
            $udb2SimpleXml
        );
    }
}
