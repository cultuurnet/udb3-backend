<?php

namespace CultuurNet\UDB3\Model\Serializer\Organizer;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class OrganizerDenormalizerTest extends TestCase
{
    /**
     * @var OrganizerDenormalizer
     */
    private $denormalizer;

    public function setUp()
    {
        $this->denormalizer = new OrganizerDenormalizer();
    }

    /**
     * @test
     */
    public function it_should_denormalize_organizer_data_with_only_the_required_properties()
    {
        $organizerData = [
            '@id' => 'https://io.uitdatabank.be/organizer/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Organizer',
            '@context' => '/contexts/organizer',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
        ];

        $expected = new ImmutableOrganizer(
            new UUID('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            )
        );

        $actual = $this->denormalizer->denormalize($organizerData, ImmutableOrganizer::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_organizer_data_with_a_url()
    {
        $organizerData = [
            '@id' => 'https://io.uitdatabank.be/organizer/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Organizer',
            '@context' => '/contexts/organizer',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $expected = new ImmutableOrganizer(
            new UUID('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new Url('https://www.publiq.be')
        );

        $actual = $this->denormalizer->denormalize($organizerData, ImmutableOrganizer::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_organizer_data_with_title_translations()
    {
        $organizerData = [
            '@id' => 'https://io.uitdatabank.be/organizer/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Organizer',
            '@context' => '/contexts/organizer',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
                'en' => 'Example title',
                'fr' => 'Titre de l\'exemple',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $expected = new ImmutableOrganizer(
            new UUID('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            (new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ))
                ->withTranslation(new Language('en'), new Title('Example title'))
                ->withTranslation(new Language('fr'), new Title('Titre de l\'exemple')),
            new Url('https://www.publiq.be')
        );

        $actual = $this->denormalizer->denormalize($organizerData, ImmutableOrganizer::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_organizer_data_with_optional_properties()
    {
        $organizerData = [
            '@id' => 'https://io.uitdatabank.be/organizer/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Organizer',
            '@context' => '/contexts/organizer',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
                'en' => 'Example title',
                'fr' => 'Titre de l\'exemple',
            ],
            'url' => 'https://www.publiq.be',
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
                'fr' => [
                    'streetAddress' => 'Quai du Hainaut 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Bruxelles',
                    'addressCountry' => 'BE',
                ],
            ],
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
            'contactPoint' => [
                'phone' => [
                    '044/556677',
                    '011/223344',
                ],
                'email' => [
                    'foo@publiq.be',
                    'bar@publiq.be',
                ],
                'url' => [
                    'https://www.uitdatabank.be',
                    'https://www.uitpas.be',
                ],
            ],
            'geo' => [
                "latitude" => 50.8793916,
                "longitude" => 4.7019674,
            ],
        ];

        $expected = new ImmutableOrganizer(
            new UUID('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            (new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ))
                ->withTranslation(new Language('en'), new Title('Example title'))
                ->withTranslation(new Language('fr'), new Title('Titre de l\'exemple')),
            new Url('https://www.publiq.be')
        );

        $expected = $expected
            ->withAddress(
                (new TranslatedAddress(
                    new Language('nl'),
                    new Address(
                        new Street('Henegouwenkaai 41-43'),
                        new PostalCode('1080'),
                        new Locality('Brussel'),
                        new CountryCode('BE')
                    )
                ))->withTranslation(
                    new Language('fr'),
                    new Address(
                        new Street('Quai du Hainaut 41-43'),
                        new PostalCode('1080'),
                        new Locality('Bruxelles'),
                        new CountryCode('BE')
                    )
                )
            )
            ->withLabels(
                new Labels(
                    new Label(new LabelName('foo'), true),
                    new Label(new LabelName('bar'), true),
                    new Label(new LabelName('lorem'), false),
                    new Label(new LabelName('ipsum'), false)
                )
            )
            ->withContactPoint(
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('044/556677'),
                        new TelephoneNumber('011/223344')
                    ),
                    new EmailAddresses(
                        new EmailAddress('foo@publiq.be'),
                        new EmailAddress('bar@publiq.be')
                    ),
                    new Urls(
                        new Url('https://www.uitdatabank.be'),
                        new Url('https://www.uitpas.be')
                    )
                )
            )
            ->withGeoCoordinates(
                new Coordinates(
                    new Latitude(50.8793916),
                    new Longitude(4.7019674)
                )
            );

        $actual = $this->denormalizer->denormalize($organizerData, ImmutableOrganizer::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_denormalize_to_an_unsupported_class()
    {
        $this->expectException(UnsupportedException::class);
        $this->denormalizer->denormalize([], ImmutableEvent::class);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_denormalize_data_that_is_not_an_array()
    {
        $this->expectException(UnsupportedException::class);
        $this->denormalizer->denormalize(new \stdClass(), ImmutableOrganizer::class);
    }

    /**
     * @test
     */
    public function it_should_support_denormalization_to_immutable_organizer()
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], ImmutableOrganizer::class)
        );
    }
}
