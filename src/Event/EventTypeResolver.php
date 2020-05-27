<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;
use ValueObjects\StringLiteral\StringLiteral;

class EventTypeResolver implements TypeResolverInterface
{
    /**
     * @var EventType[]
     */
    private $types;

    public function __construct()
    {
        $this->types = [
            "0.7.0.0.0" => new EventType("0.7.0.0.0", "Begeleide rondleiding"),
            "0.6.0.0.0" => new EventType("0.6.0.0.0", "Beurs"),
            "0.50.4.0.0" => new EventType("0.50.4.0.0", "Concert"),
            "0.3.1.0.0" => new EventType("0.3.1.0.0", "Cursus of workshop"),
            "0.54.0.0.0" => new EventType("0.54.0.0.0", "Dansvoorstelling"),
            "1.50.0.0.0" => new EventType("1.50.0.0.0", "Eten en drinken"),
            "0.5.0.0.0" => new EventType("0.5.0.0.0", "Festival"),
            "0.50.6.0.0" => new EventType("0.50.6.0.0", "Film"),
            "0.57.0.0.0" => new EventType("0.57.0.0.0", "Kamp of vakantie"),
            "0.28.0.0.0" => new EventType("0.28.0.0.0", "Kermis of feestelijkheid"),
            "0.3.2.0.0" => new EventType("0.3.2.0.0", "Lezing of congres"),
            "0.37.0.0.0" => new EventType("0.37.0.0.0", "Markt of braderie"),
            "0.12.0.0.0" => new EventType("0.12.0.0.0", "Opendeurdag"),
            "0.49.0.0.0" => new EventType("0.49.0.0.0", "Party of fuif"),
            "0.17.0.0.0" => new EventType("0.17.0.0.0", "Route"),
            "0.50.21.0.0" => new EventType("0.50.21.0.0", "Spel of quiz"),
            "0.59.0.0.0" => new EventType("0.59.0.0.0", "Sport en beweging"),
            "0.19.0.0.0" => new EventType("0.19.0.0.0", "Sportwedstrijd bekijken"),
            "0.0.0.0.0" => new EventType("0.0.0.0.0", "Tentoonstelling"),
            "0.55.0.0.0" => new EventType("0.55.0.0.0", "Theatervoorstelling"),
            "0.51.0.0.0" => new EventType("0.51.0.0.0", "Type onbepaald"),
        ];
    }

    public function byId(StringLiteral $typeId): EventType
    {
        if (!array_key_exists((string) $typeId, $this->types)) {
            throw new Exception("Unknown event type id: " . $typeId);
        }
        return $this->types[(string) $typeId];
    }
}
