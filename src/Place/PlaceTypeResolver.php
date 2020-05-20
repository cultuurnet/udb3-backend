<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceTypeResolver implements TypeResolverInterface
{
    /**
     * @var EventType[]
     */
    private $types;

    public function __construct()
    {
        $this->types = [
            "0.14.0.0.0" => new EventType("0.14.0.0.0", "Monument"),
            "0.15.0.0.0" => new EventType("0.15.0.0.0", "Natuur, park of tuin"),
            "3CuHvenJ+EGkcvhXLg9Ykg" => new EventType("3CuHvenJ+EGkcvhXLg9Ykg", "Archeologische Site"),
            "GnPFp9uvOUyqhOckIFMKmg" => new EventType("GnPFp9uvOUyqhOckIFMKmg", "Museum of galerij"),
            "kI7uAyn2uUu9VV6Z3uWZTA" => new EventType("kI7uAyn2uUu9VV6Z3uWZTA", "Bibliotheek of documentatiecentrum"),
            "0.53.0.0.0" => new EventType("0.53.0.0.0", "Recreatiedomein of centrum"),
            "0.41.0.0.0" => new EventType("0.41.0.0.0", "Thema of pretpark"),
            "rJRFUqmd6EiqTD4c7HS90w" => new EventType("rJRFUqmd6EiqTD4c7HS90w", "School of onderwijscentrum"),
            "eBwaUAAhw0ur0Z02i5ttnw" => new EventType("eBwaUAAhw0ur0Z02i5ttnw", "Sportcentrum"),
            "VRC6HX0Wa063sq98G5ciqw" => new EventType("VRC6HX0Wa063sq98G5ciqw", "Winkel"),
            "JCjA0i5COUmdjMwcyjNAFA" => new EventType("JCjA0i5COUmdjMwcyjNAFA", "Jeugdhuis of jeugdcentrum"),
            "Yf4aZBfsUEu2NsQqsprngw" => new EventType("Yf4aZBfsUEu2NsQqsprngw", "Cultuur- of ontmoetingscentrum"),
            "YVBc8KVdrU6XfTNvhMYUpg" => new EventType("YVBc8KVdrU6XfTNvhMYUpg", "Discotheek"),
            "BtVNd33sR0WntjALVbyp3w" => new EventType("BtVNd33sR0WntjALVbyp3w", "Bioscoop"),
            "ekdc4ATGoUitCa0e6me6xA" => new EventType("ekdc4ATGoUitCa0e6me6xA", "Horeca"),
            "OyaPaf64AEmEAYXHeLMAtA" => new EventType("OyaPaf64AEmEAYXHeLMAtA", "Zaal of expohal"),
            "0.8.0.0.0" => new EventType("0.8.0.0.0", "Openbare ruimte"),
            "8.70.0.0.0" => new EventType("8.70.0.0.0", "Theater"),
        ];
    }

    public function byId(StringLiteral $typeId): EventType
    {
        if (!array_key_exists((string) $typeId, $this->types)) {
            throw new Exception("Unknown place type id: " . $typeId);
        }
        return $this->types[(string) $typeId];
    }
}
