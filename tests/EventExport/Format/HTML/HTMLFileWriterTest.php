<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class HTMLFileWriterTest extends TestCase
{
    protected string $filePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->filePath = $this->getFilePath();
    }

    /**
     * @test
     */
    public function it_writes_a_file(): void
    {
        $events = [];

        $this->assertFileDoesNotExist($this->filePath);

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'Lorem Ipsum.',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $this->assertFileExists($this->filePath);
    }

    /**
     * @test
     * @dataProvider twigCustomTemplateProvider
     */
    public function it_can_use_a_customized_twig_environment_and_template(
        string $template,
        array $variables,
        string $fileWithExpectedContent
    ): void {
        $events = [];

        $twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(__DIR__ . '/templates')
        );

        $fileWriter = new HTMLFileWriter(
            $template,
            $variables,
            $twig
        );

        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read($fileWithExpectedContent);
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    public function twigCustomTemplateProvider(): array
    {
        return [
            [
                'hello.html.twig',
                [
                    'name' => 'world',
                ],
                __DIR__ . '/results/hello-world.html',
            ],
            [
                'hello.html.twig',
                [
                    'name' => 'Belgium',
                ],
                __DIR__ . '/results/hello-belgium.html',
            ],
            [
                'goodbye.html.twig',
                [
                    'name' => 'world',
                ],
                __DIR__ . '/results/goodbye-world.html',
            ],
            [
                'goodbye.html.twig',
                [
                    'name' => 'Belgium',
                ],
                __DIR__ . '/results/goodbye-belgium.html',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_inserts_variables(): void
    {
        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'vlieg',
                'logo' => 'img/vlieg.svg',
                'title' => 'Lorem Ipsum.',
                'subtitle' => 'Dolor sit amet.',
                'footer' => 'Cursus mattis lorem ipsum.',
                'publisher' => 'Tellus quam porta nibh mattis.',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator([]));

        $expected = SampleFiles::read(__DIR__ . '/results/export_without_events.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_inserts_events(): void
    {
        $events = [
            [
                'image' => 'http://media.uitdatabank.be/20140715/p18qn74oth1uvnnpidhj1i6t1f9p1.png',
                'type' => 'Cursus of workshop',
                'title' => 'De muziek van de middeleeuwen // Een middeleeuwse muziekgeschiedenis in veertig toppers',
                'description' => 'Alhoewel de middeleeuwen zo’n duizend jaar duurden, ' . 'is het grootste deel van de ...',
                'dates' => '<time itemprop="startDate" datetime="2014-04-23T">' . '<span class="cf-weekday cf-meta">woensdag</span> ' . '<span class="cf-date">23 april 2014</span> ' . '<span class="cf-from cf-meta">om</span> ' . '<span class="cf-time"></span>' . '</time>',
                'address' => [
                    'name' => 'CC De Werf',
                    'street' => 'Molenstraat 51',
                    'postcode' => '9300',
                    'municipality' => 'Aalst',
                ],
                'price' => '119,0',
                'mediaObject' => [
                    '@id' =>  'https://io.uitdatabank.be/media/6121edec-7960-48a8-aab5-0ecba1cc48ef',
                    '@type' =>  'schema:ImageObject',
                    'contentUrl' =>  'http://media.uitdatabank.be/20140715/p18qn74oth1uvnnpidhj1i6t1f9p1.png',
                    'thumbnailUrl' =>  'http://media.uitdatabank.be/20140715/p18qn74oth1uvnnpidhj1i6t1f9p1.png',
                    'description' =>  'De Kortste Nacht',
                    'copyrightHolder' =>  'Rode Ridder',
                ],
            ],
            [
                'image' => 'http://media.uitdatabank.be/20130805/8d455579-2207-4643-bdaf-a514da64697b.JPG',
                'type' => 'Spel of quiz',
                'title' => 'Speurtocht Kapitein Massimiliaan en de vliegende Hollander',
                'description' => 'Een familiespel voor jong en oud! Worden jullie de nieuwe matrozen van de ...',
                'dates' => '<p><time itemprop="startDate" datetime="2014-04-23">' . '<span class="cf-date">23 april 2014</span>' . '</time>' . '<span class="cf-to cf-meta">tot</span>' . '<time itemprop="endDate" datetime="2014-04-30">' . '<span class="cf-date">30 april 2014</span>' . '</time></p>',
                'address' => [
                    'name' => 'Museum aan de Stroom (MAS)',
                    'street' => 'Hanzestedenplaats 1',
                    'postcode' => '2000',
                    'municipality' => 'Antwerpen',
                ],
                'price' => 'Gratis',
                'mediaObject' => [
                    '@id' =>  'https://io.uitdatabank.be/media/2f413100-0d9c-43a6-a91b-b8668f1aaad0',
                    '@type' =>  'schema:ImageObject',
                    'contentUrl' =>  'http://media.uitdatabank.be/20140715/p18qn74oth1uvnnpidhj1i6t1f9p1.png',
                    'thumbnailUrl' =>  'http://media.uitdatabank.be/20140715/p18qn74oth1uvnnpidhj1i6t1f9p1.png',
                    'description' =>  'familiespel',
                    'copyrightHolder' =>  'Vliegende Hollander',
                ],
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'Lorem Ipsum.',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_can_handle_events_without_an_image(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_without_image.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_shows_taaliconen(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 1,
                'taalicoonDescription' => 'Je begrijpt of spreekt nog niet veel Nederlands.',
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_taaliconen.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_can_handle_four_taaliconen(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 4,
                'taalicoonDescription' => 'Je begrijpt veel Nederlands en spreekt het goed.',
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_four_taaliconen.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_adds_event_brands_to_activities(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 1,
                'taalicoonDescription' => 'Je begrijpt of spreekt nog niet veel Nederlands.',
                'brands' => [
                    'uitpas',
                    'vlieg',
                ],
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_uitpas_brand.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_shows_the_starting_age(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 1,
                'taalicoonDescription' => 'Je begrijpt of spreekt nog niet veel Nederlands.',
                'brands' => [
                    'uitpas',
                ],
                'ageFrom' => '5',
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_age_range.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    private function getEventsWithUiTPASInfo(): array
    {
        return [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 1,
                'taalicoonDescription' => 'Je begrijpt of spreekt nog niet veel Nederlands.',
                'brands' => [
                ],
                'ageFrom' => '5',
                'uitpas' => [
                    'prices' => [
                        [
                            'price' => '1,5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                            'forOtherCardSystems' => false,
                        ],
                        [
                            'price' => '3',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                            'forOtherCardSystems' => true,
                        ],
                    ],
                    'advantages' => [
                        'Spaar punten',
                        'Korting voor kansentarief',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_shows_uitpas_info(): void
    {
        $events = $this->getEventsWithUiTPASInfo();

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'uit',
                'logo' => 'img/uit.svg',
                'title' => 'UiT',
            ]
        );
        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_uitpas_info.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_shows_paspartoe_branded_uitpas_info(): void
    {
        $events = $this->getEventsWithUiTPASInfo();

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'paspartoe',
                'logo' => 'img/paspartoe.svg',
                'title' => 'UiT',
            ]
        );


        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_uitpas_info_paspartoe_branded.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    /**
     * @test
     */
    public function it_shows_a_custom_logo(): void
    {
        $events = [
            [
                'type' => 'Cursus of workshop',
                'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
                'description' => 'De islam is niet meer weg te denken uit onze maatschappij. Aan de...',
                'address' => [
                    'name' => 'Cultuurcentrum De Kruisboog',
                    'street' => 'Sint-Jorisplein 20',
                    'postcode' => '3300',
                    'municipality' => 'Tienen',
                ],
                'price' => 'Niet ingevoerd',
                'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ' . 'ma 30/03/15 van 13:30 tot 16:30 ',
                'taalicoonCount' => 1,
                'taalicoonDescription' => 'Je begrijpt of spreekt nog niet veel Nederlands.',
            ],
        ];

        $fileWriter = $this->createHTMLFileWriter(
            [
                'brand' => 'custom',
                'logo' => 'img/custom.svg',
                'title' => 'customBrand',
                'partner' => true,
            ]
        );

        $fileWriter->write($this->filePath, new \ArrayIterator($events));

        $expected = SampleFiles::read(__DIR__ . '/results/export_event_with_custom_logo.html');
        $this->assertHTMLFileContents($expected, $this->filePath);
    }

    protected function createHTMLFileWriter(array $variables): HTMLFileWriter
    {
        return new HTMLFileWriter('export.tips.html.twig', $variables);
    }

    protected function getFilePath(): string
    {
        return tempnam(sys_get_temp_dir(), uniqid()) . '.html';
    }

    /**
     * @test
     */
    protected function assertHTMLFileContents(string $html, string $filePath): void
    {
        $this->assertEquals($html, SampleFiles::read($filePath));
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        if ($this->filePath && file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
