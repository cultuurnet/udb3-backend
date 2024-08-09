<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use PHPUnit\Framework\TestCase;

final class SerializableSerializableSimpleXmlElementTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_a_complex_xml(): void
    {
        $cdbXml = new SerializableSimpleXmlElement(
            SampleFiles::read(__DIR__ . '/Place/actor.xml'),
            0,
            false,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                'actor' => [
                    'actordetails' => [
                        0 => [
                            'actordetail' => [
                                0 => [
                                    'calendarsummary' => [
                                        0 => [
                                            '_text' => 'Bij voorstellingen is de balie van CC Palethe één uur voor' . PHP_EOL . '                aanvang geopend.',
                                        ],
                                    ],
                                    'media' => [
                                        0 => [
                                            'file' => [
                                                0 => [
                                                    'copyright' => [
                                                        0 => [
                                                            '_text' => '\'Bekend met Gent\' - quiz',
                                                        ],
                                                    ],
                                                    'filename' => [
                                                        0 => [
                                                            '_text' => 'ed466c72-451f-4079-94d3-4ab2e0be7b15.jpg',
                                                        ],
                                                    ],
                                                    'filetype' => [
                                                        0 => [
                                                            '_text' => 'jpeg',
                                                        ],
                                                    ],
                                                    'hlink' => [
                                                        0 => [
                                                            '_text' => '//media.uitdatabank.be/20141105/ed466c72-451f-4079-94d3-4ab2e0be7b15.jpg',
                                                        ],
                                                    ],
                                                    'mediatype' => [
                                                        0 => [
                                                            '_text' => 'photo',
                                                        ],
                                                    ],
                                                    '@attributes' => [
                                                        'creationdate' => '5/11/2014 13:26:54',
                                                        'main' => 'true',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'shortdescription' => [
                                        0 => [
                                            '_text' => 'Cultuurcentrum van de gemeente Overpelt.',
                                        ],
                                    ],
                                    'title' => [
                                        0 => [
                                            '_text' => 'CC Palethe',
                                        ],
                                    ],
                                    '@attributes' => [
                                        'lang' => 'nl',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'categories' => [
                        0 => [
                            'category' => [
                                0 => [
                                    '_text' => 'Locatie',
                                    '@attributes' => [
                                        'catid' => '8.15.0.0.0',
                                        'type' => 'actortype',
                                    ],
                                ],
                                1 => [
                                    '_text' => 'Organisator(en)',
                                    '@attributes' => [
                                        'catid' => '8.11.0.0.0',
                                        'type' => 'actortype',
                                    ],
                                ],
                                2 => [
                                    '_text' => 'Regionaal',
                                    '@attributes' => [
                                        'catid' => '6.2.0.0.0',
                                        'type' => 'publicscope',
                                    ],
                                ],
                                3 => [
                                    '_text' => 'Cultuur, gemeenschaps' . PHP_EOL . '            of ontmoetingscentrum',
                                    '@attributes' => [
                                        'catid' => '8.6.0.0.0',
                                        'type' => 'actortype',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'keywords' => [
                        0 => [
                            '_text' => 'Aanvaarden van SABAM-cultuurchèques;Toevlalocatie;toevlalocatie',
                        ],
                    ],
                    'contactinfo' => [
                        0 => [
                            'address' => [
                                0 => [
                                    'physical' => [
                                        0 => [
                                            'city' => [
                                                0 => [
                                                    '_text' => 'Overpelt',
                                                ],
                                            ],
                                            'country' => [
                                                0 => [
                                                    '_text' => 'BE',
                                                ],
                                            ],
                                            'gis' => [
                                                0 => [
                                                    'xcoordinate' => [
                                                        0 => [
                                                            '_text' => '5.427752',
                                                        ],
                                                    ],
                                                    'ycoordinate' => [
                                                        0 => [
                                                            '_text' => '51.211603',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'housenr' => [
                                                0 => [
                                                    '_text' => '2',
                                                ],
                                            ],
                                            'street' => [
                                                0 => [
                                                    '_text' => 'Jeugdlaan',
                                                ],
                                            ],
                                            'zipcode' => [
                                                0 => [
                                                    '_text' => '3900',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'mail' => [
                                0 => [
                                    '_text' => 'reservaties@palethe.be',
                                    '@attributes' => [
                                        'reservation' => 'true',
                                    ],
                                ],
                            ],
                            'phone' => [
                                0 => [
                                    '_text' => '+32 11 645952',
                                    '@attributes' => [
                                        'reservation' => 'true',
                                        'type' => 'phone',
                                    ],
                                ],
                                1 => [
                                    '_text' => '+32 11 644504',
                                    '@attributes' => [
                                        'type' => 'fax',
                                    ],
                                ],
                            ],
                            'url' => [
                                0 => [
                                    '_text' => 'http://toevla.vlaanderen.be/publiek/nl/register/detail/19',
                                ],
                                1 => [
                                    '_text' => 'http://www.palethe.be/',
                                    '@attributes' => [
                                        'main' => 'true',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $cdbXml->serialize()
        );
    }
}
