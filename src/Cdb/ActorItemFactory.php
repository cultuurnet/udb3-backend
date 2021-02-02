<?php

namespace CultuurNet\UDB3\Cdb;

class ActorItemFactory implements ActorItemFactoryInterface
{
    /**
     * @var string
     */
    private $namespaceUri;

    /**
     * @param string $namespaceUri
     */
    public function __construct($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    /**
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     * @return \CultureFeed_Cdb_Item_Actor
     */
    public function createFromCdbXml($cdbXml)
    {
        return self::createActorFromCdbXml($this->namespaceUri, $cdbXml);
    }

    /**
     * @param string $namespaceUri
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     * @return \CultureFeed_Cdb_Item_Actor
     */
    public static function createActorFromCdbXml($namespaceUri, $cdbXml)
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
