<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Offer\ThemeResolverInterface;
use CultuurNet\UDB3\Theme;
use ValueObjects\StringLiteral\StringLiteral;

class EventThemeResolver implements ThemeResolverInterface
{
    /**
     * @var Theme[]
     */
    private $themes;

    public function __construct()
    {
        $themePerType = [
            [
                "label" => "Begeleide rondleiding",
                "id" => "0.7.0.0.0",
                "primary" => true,
                "themes" => [
                    [
                        "id" => "1.2.1.0.0",
                        "label" => "Architectuur",
                    ],
                    [
                        "id" => "1.11.0.0.0",
                        "label" => "Geschiedenis",
                    ],
                    [
                        "id" => "1.0.9.0.0",
                        "label" => "Meerdere kunstvormen",
                    ],
                    [
                        "id" => "1.64.0.0.0",
                        "label" => "Milieu en natuur",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.44.0.0.0",
                        "label" => "Zingeving, filosofie en religie",
                    ],
                ],
            ],
            [
                "label" => "Concert",
                "id" => "0.50.4.0.0",
                "primary" => true,
                "themes" => [
                    [
                        "id" => "1.8.1.0.0",
                        "label" => "Klassieke muziek",
                    ],
                    [
                        "id" => "1.8.2.0.0",
                        "label" => "Jazz en blues",
                    ],
                    [
                        "id" => "1.8.3.1.0",
                        "label" => "Pop en rock",
                    ],
                    [
                        "id" => "1.8.3.2.0",
                        "label" => "Hip hop, rnb en rap",
                    ],
                    [
                        "id" => "1.8.3.3.0",
                        "label" => "Dance",
                    ],
                    [
                        "id" => "1.8.4.0.0",
                        "label" => "Folk en wereldmuziek",
                    ],
                    [
                        "id" => "1.8.3.5.0",
                        "label" => "Amusementsmuziek",
                    ],
                ],
            ],
            [
                "label" => "Cursus of workshop",
                "id" => "0.3.1.0.0",
                "primary" => true,
                "themes" => [
                    [
                        "id" => "1.42.0.0.0",
                        "label" => "Creativiteit",
                    ],
                    [
                        "id" => "1.21.0.0.0",
                        "label" => "Computer en techniek",
                    ],
                    [
                        "id" => "1.37.0.0.0",
                        "label" => "Opvoeding",
                    ],
                    [
                        "id" => "1.61.0.0.0",
                        "label" => "Persoon en relaties",
                    ],
                    [
                        "id" => "1.43.0.0.0",
                        "label" => "Interculturele vorming",
                    ],
                    [
                        "id" => "1.41.0.0.0",
                        "label" => "Kunst en kunsteducatie",
                    ],
                    [
                        "id" => "1.37.1.0.0",
                        "label" => "Gezondheid en zorg",
                    ],
                    [
                        "id" => "1.65.0.0.0",
                        "label" => "Voeding",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.64.0.0.0",
                        "label" => "Milieu en natuur",
                    ],
                    [
                        "id" => "1.25.0.0.0",
                        "label" => "Wetenschap",
                    ],
                    [
                        "id" => "1.44.0.0.0",
                        "label" => "Zingeving, filosofie en religie",
                    ],
                ],
            ],
            [
                "label" => "Route",
                "id" => "0.17.0.0.0",
                "primary" => true,
            ],
            [
                "label" => "Film",
                "id" => "0.50.6.0.0",
                "primary" => true,
                "themes" => [
                    [
                        "id" => "1.7.2.0.0",
                        "label" => "Actie en avontuur",
                    ],
                    [
                        "id" => "1.7.12.0.0",
                        "label" => "Animatie en kinderfilms",
                    ],
                    [
                        "id" => "1.7.1.0.0",
                        "label" => "Documentaires en reportages",
                    ],
                    [
                        "id" => "1.7.6.0.0",
                        "label" => "Griezelfilm of horror",
                    ],
                    [
                        "id" => "1.7.8.0.0",
                        "label" => "Historische film",
                    ],
                    [
                        "id" => "1.7.3.0.0",
                        "label" => "Komedie",
                    ],
                    [
                        "id" => "1.7.13.0.0",
                        "label" => "Kortfilm",
                    ],
                    [
                        "id" => "1.7.10.0.0",
                        "label" => "Filmmusical",
                    ],
                    [
                        "id" => "1.7.4.0.0",
                        "label" => "Drama",
                    ],
                    [
                        "id" => "1.7.7.0.0",
                        "label" => "Science fiction",
                    ],
                    [
                        "id" => "1.7.11.0.0",
                        "label" => "Cinefiel",
                    ],
                    [
                        "id" => "1.7.15.0.0",
                        "label" => "Thriller",
                    ],
                ],
            ],
            [
                "label" => "Lezing of congres",
                "id" => "0.3.2.0.0",
                "primary" => "false",
                "themes" => [
                    [
                        "id" => "1.21.0.0.0",
                        "label" => "Computer en techniek",
                    ],
                    [
                        "id" => "1.42.0.0.0",
                        "label" => "Creativiteit",
                    ],
                    [
                        "id" => "1.66.0.0.0",
                        "label" => "Economie",
                    ],
                    [
                        "id" => "1.40.0.0.0",
                        "label" => "Erfgoed",
                    ],
                    [
                        "id" => "1.10.11.0.0",
                        "label" => "Fictie",
                    ],
                    [
                        "id" => "1.11.0.0.0",
                        "label" => "Geschiedenis",
                    ],
                    [
                        "id" => "1.37.1.0.0",
                        "label" => "Gezondheid en zorg",
                    ],
                    [
                        "id" => "1.41.0.0.0",
                        "label" => "Kunst en kunsteducatie",
                    ],
                    [
                        "id" => "1.63.0.0.0",
                        "label" => "Landbouw en platteland",
                    ],
                    [
                        "id" => "1.10.0.0.0",
                        "label" => "Literatuur",
                    ],
                    [
                        "id" => "1.64.0.0.0",
                        "label" => "Milieu en natuur",
                    ],
                    [
                        "id" => "1.10.12.0.0",
                        "label" => "Non fictie",
                    ],
                    [
                        "id" => "1.37.0.0.0",
                        "label" => "Opvoeding",
                    ],
                    [
                        "id" => "1.61.0.0.0",
                        "label" => "Persoon en relaties",
                    ],
                    [
                        "id" => "1.10.5.0.0",
                        "label" => "Poëzie",
                    ],
                    [
                        "id" => "1.52.0.0.0",
                        "label" => "Politiek en maatschappij",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.10.8.0.0",
                        "label" => "Strips",
                    ],
                    [
                        "id" => "1.58.0.0.0",
                        "label" => "Thema onbepaald",
                    ],
                    [
                        "id" => "1.25.0.0.0",
                        "label" => "Wetenschap",
                    ],
                    [
                        "id" => "1.44.0.0.0",
                        "label" => "Zingeving, filosofie en religie",
                    ],
                ],
            ],
            [
                "label" => "Opendeurdag",
                "id" => "0.12.0.0.0",
                "primary" => true,
            ],
            [
                "label" => "Tentoonstelling",
                "id" => "0.0.0.0.0",
                "primary" => true,
                "themes" => [
                    [
                        "id" => "1.1.0.0.0",
                        "label" => "Audiovisuele kunst",
                    ],
                    [
                        "id" => "1.0.2.0.0",
                        "label" => "Beeldhouwkunst",
                    ],
                    [
                        "id" => "1.0.5.0.0",
                        "label" => "Decoratieve kunst",
                    ],
                    [
                        "id" => "1.2.2.0.0",
                        "label" => "Design",
                    ],
                    [
                        "id" => "1.0.6.0.0",
                        "label" => "Fotografie",
                    ],
                    [
                        "id" => "1.11.0.0.0",
                        "label" => "Geschiedenis",
                    ],
                    [
                        "id" => "1.0.4.0.0",
                        "label" => "Grafiek",
                    ],
                    [
                        "id" => "1.0.3.0.0",
                        "label" => "Installatiekunst",
                    ],
                    [
                        "id" => "1.0.9.0.0",
                        "label" => "Meerdere kunstvormen",
                    ],
                    [
                        "id" => "1.49.0.0.0",
                        "label" => "Mode",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.0.1.0.0",
                        "label" => "Schilderkunst",
                    ],
                ],
            ],
            [
                "label" => "Beurs",
                "id" => "0.6.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.17.0.0.0",
                        "label" => "Antiek en brocante",
                    ],
                    [
                        "id" => "1.62.0.0.0",
                        "label" => "Gezondheid en wellness",
                    ],
                    [
                        "id" => "1.10.0.0.0",
                        "label" => "Literatuur",
                    ],
                    [
                        "id" => "1.0.9.0.0",
                        "label" => "Meerdere kunstvormen",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.25.0.0.0",
                        "label" => "Wetenschap",
                    ],
                ],
            ],
            [
                "label" => "Dansvoorstelling",
                "id" => "0.54.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.9.1.0.0",
                        "label" => "Ballet en klassieke dans",
                    ],
                    [
                        "id" => "1.9.3.0.0",
                        "label" => "Volksdans en werelddans",
                    ],
                    [
                        "id" => "1.9.5.0.0",
                        "label" => "Stijl en salondansen",
                    ],
                    [
                        "id" => "1.9.2.0.0",
                        "label" => "Moderne dans",
                    ],
                ],
            ],
            [
                "label" => "Eten en drinken",
                "id" => "1.50.0.0.0",
                "primary" => false,
            ],
            [
                "label" => "Festival",
                "id" => "0.5.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.8.3.5.0",
                        "label" => "Amusementsmuziek",
                    ],
                    [
                        "id" => "0.52.0.0.0",
                        "label" => "Circus",
                    ],
                    [
                        "id" => "1.8.3.3.0",
                        "label" => "Dance",
                    ],
                    [
                        "id" => "1.8.4.0.0",
                        "label" => "Folk en wereldmuziek",
                    ],
                    [
                        "id" => "1.3.10.0.0",
                        "label" => "Humor en comedy",
                    ],
                    [
                        "id" => "1.8.2.0.0",
                        "label" => "Jazz en blues",
                    ],
                    [
                        "id" => "1.8.1.0.0",
                        "label" => "Klassieke muziek",
                    ],
                    [
                        "id" => "1.10.0.0.0",
                        "label" => "Literatuur",
                    ],
                    [
                        "id" => "1.7.14.0.0",
                        "label" => "Meerdere filmgenres",
                    ],
                    [
                        "id" => "1.0.9.0.0",
                        "label" => "Meerdere kunstvormen",
                    ],
                    [
                        "id" => "1.8.3.1.0",
                        "label" => "Pop en rock",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                    [
                        "id" => "1.3.1.0.0",
                        "label" => "Tekst- en muziektheater",
                    ],
                ],
            ],
            [
                "label" => "Kamp of vakantie",
                "id" => "0.57.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.11.2.0.0",
                        "label" => "Themakamp",
                    ],
                    [
                        "id" => "1.11.1.0.0",
                        "label" => "Taal en communicatie",
                    ],
                ],
            ],
            [
                "label" => "Kermis of feestelijkheid",
                "id" => "0.28.0.0.0",
                "primary" => false,
            ],
            [
                "label" => "Markt of braderie",
                "id" => "0.37.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.17.0.0.0",
                        "label" => "Antiek en brocante",
                    ],
                    [
                        "id" => "1.62.0.0.0",
                        "label" => "Gezondheid en wellness",
                    ],
                    [
                        "id" => "1.10.0.0.0",
                        "label" => "Literatuur",
                    ],
                    [
                        "id" => "1.0.9.0.0",
                        "label" => "Meerdere kunstvormen",
                    ],
                    [
                        "id" => "1.64.0.0.0",
                        "label" => "Milieu en natuur",
                    ],
                    [
                        "id" => "1.37.2.0.0",
                        "label" => "Samenleving",
                    ],
                ],
            ],
            [
                "label" => "Party of fuif",
                "id" => "0.49.0.0.0",
                "primary" => false,
            ],
            [
                "label" => "Spel of quiz",
                "id" => "0.50.21.0.0",
                "primary" => false,
            ],
            [
                "label" => "Sport en beweging",
                "id" => "0.59.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.51.13.0.0",
                        "label" => "Bal en racketsport",
                    ],
                    [
                        "id" => "1.51.14.0.0",
                        "label" => "Atletiek, wandelen en fietsen",
                    ],
                    [
                        "id" => "1.51.3.0.0",
                        "label" => "Zwemmen en watersport",
                    ],
                    [
                        "id" => "1.51.6.0.0",
                        "label" => "Fitness, gymnastiek, dans en vechtsport",
                    ],
                    [
                        "id" => "1.51.11.0.0",
                        "label" => "Outdoor en adventure",
                    ],
                    [
                        "id" => "1.58.8.0.0",
                        "label" => "Lucht en motorsport",
                    ],
                    [
                        "id" => "1.51.10.0.0",
                        "label" => "Volkssporten",
                    ],
                    [
                        "id" => "1.51.12.0.0",
                        "label" => "Omnisport en andere",
                    ],
                ],
            ],
            [
                "label" => "Sportwedstrijd bekijken",
                "id" => "0.19.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "1.51.14.0.0",
                        "label" => "Atletiek, wandelen en fietsen",
                    ],
                    [
                        "id" => "1.51.13.0.0",
                        "label" => "Bal en racketsport",
                    ],
                    [
                        "id" => "1.51.6.0.0",
                        "label" => "Fitness, gymnastiek, dans en vechtsport",
                    ],
                    [
                        "id" => "1.58.8.0.0",
                        "label" => "Lucht en motorsport",
                    ],
                    [
                        "id" => "1.51.12.0.0",
                        "label" => "Omnisport en andere",
                    ],
                    [
                        "id" => "1.51.11.0.0",
                        "label" => "Outdoor en adventure",
                    ],
                    [
                        "id" => "1.51.10.0.0",
                        "label" => "Volkssporten",
                    ],
                    [
                        "id" => "1.51.3.0.0",
                        "label" => "Zwemmen en watersport",
                    ],
                ],
            ],
            [
                "label" => "Theatervoorstelling",
                "id" => "0.55.0.0.0",
                "primary" => false,
                "themes" => [
                    [
                        "id" => "0.52.0.0.0",
                        "label" => "Circus",
                    ],
                    [
                        "id" => "1.3.1.0.0",
                        "label" => "Tekst en muziektheater",
                    ],
                    [
                        "id" => "1.3.10.0.0",
                        "label" => "Humor comedy",
                    ],
                    [
                        "id" => "1.4.0.0.0",
                        "label" => "Musical",
                    ],
                    [
                        "id" => "1.3.5.0.0",
                        "label" => "Figuren en poppentheater",
                    ],
                    [
                        "id" => "1.5.0.0.0",
                        "label" => "Opera en operette",
                    ],
                    [
                        "id" => "1.3.4.0.0",
                        "label" => "Mime en bewegingstheater",
                    ],
                ],
            ],
        ];

        $this->themes = array_reduce(
            $themePerType,
            function ($themes, array $type) {
                if (array_key_exists('themes', $type)) {
                    foreach ($type['themes'] as $themeData) {
                        $themes[$themeData['id']] = new Theme($themeData['id'], $themeData['label']);
                    }
                }

                return $themes;
            },
            []
        );
    }

    /**
     * @inheritdoc
     */
    public function byId(StringLiteral $themeId)
    {
        if (!array_key_exists((string) $themeId, $this->themes)) {
            throw new \Exception("Unknown event theme id: " . $themeId);
        }
        return $this->themes[(string) $themeId];
    }
}
