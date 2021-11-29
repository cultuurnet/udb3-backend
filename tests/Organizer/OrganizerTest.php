<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Title as LegacyTitle;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url as LegacyUrl;

class OrganizerTest extends AggregateRootScenarioTestCase
{
    private string $id;

    private LegacyLanguage $mainLanguage;

    private LegacyUrl $website;

    private LegacyTitle $title;

    private OrganizerCreatedWithUniqueWebsite $organizerCreatedWithUniqueWebsite;

    public function setUp(): void
    {
        parent::setUp();

        $this->id = '18eab5bf-09bf-4521-a8b4-c0f4a585c096';
        $this->mainLanguage = new LegacyLanguage('en');
        $this->website = LegacyUrl::fromNative('http://www.stuk.be');
        $this->title = new LegacyTitle('STUK');

        $this->organizerCreatedWithUniqueWebsite = new OrganizerCreatedWithUniqueWebsite(
            $this->id,
            new LegacyLanguage('en'),
            $this->website,
            $this->title
        );
    }

    /**
     * @test
     */
    public function it_imports_from_udb2_actors_and_takes_labels_into_account(): void
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->when(
                function () use ($cdbXml) {
                    return Organizer::importFromUDB2(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    );
                }
            )
            ->then(
                [
                    new OrganizerImportedFromUDB2(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->addLabel(new Label('foo'));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_updates_from_udb2_actors_and_takes_labels_into_account(): void
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->withAggregateId('404EE8DE-E828-9C07-FE7D12DC4EB24480')
            ->given(
                [
                    new OrganizerCreated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new LegacyTitle('DE Studio'),
                        [
                            new LegacyAddress(
                                new LegacyStreet('Wetstraat 1'),
                                new LegacyPostalCode('1000'),
                                new LegacyLocality('Brussel'),
                                Country::fromNative('BE')
                            ),
                        ],
                        [],
                        [],
                        []
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) use ($cdbXml) {
                    $organizer->updateWithCdbXml(
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    );
                }
            )
            ->then(
                [
                    new OrganizerUpdatedFromUDB2(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->addLabel(new Label('foo'));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_import_labels(): void
    {
        $labels = new Labels(
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('new_label_1'),
                true
            ),
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('existing_label_1'),
                true
            )
        );

        $keepIfApplied = new Labels(
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('existing_label_3'),
                true
            ),
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('non_existing_label_1'),
                true
            )
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new LabelAdded($this->id, new Label('existing_label_1')),
                    new LabelAdded($this->id, new Label('existing_label_2')),
                    new LabelAdded($this->id, new Label('existing_label_3')),
                ]
            )
            ->when(
                function (Organizer $organizer) use ($labels, $keepIfApplied) {
                    $organizer->importLabels($labels, $keepIfApplied);
                    $organizer->importLabels($labels, $keepIfApplied);
                }
            )
            ->then(
                [
                    new LabelsImported(
                        $this->id,
                        new Labels(
                            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                                new LabelName('new_label_1'),
                                true
                            )
                        )
                    ),
                    new LabelAdded($this->id, new Label('new_label_1')),
                    new LabelRemoved($this->id, new Label('existing_label_2')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_create_new_organizers(): void
    {
        $this->scenario
            ->when(
                function () {
                    return Organizer::create(
                        $this->id,
                        $this->mainLanguage,
                        $this->website,
                        $this->title
                    );
                }
            )
            ->then(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_set_an_initial_address_and_update_it_later_if_changed(): void
    {
        $initialAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $updatedAddress = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $language = new Language($this->organizerCreatedWithUniqueWebsite->getMainLanguage()->getCode());

        $this->scenario
            ->given([$this->organizerCreatedWithUniqueWebsite])
            ->when(
                function (Organizer $organizer) use ($initialAddress, $updatedAddress, $language) {
                    $organizer->updateAddress($initialAddress, $language);

                    // Update the address twice with the same value so we can
                    // test it doesn't get recorded the second time.
                    $organizer->updateAddress($updatedAddress, $language);
                    $organizer->updateAddress($updatedAddress, $language);
                }
            )
            ->then(
                [
                    new AddressUpdated($this->id, LegacyAddress::fromUdb3ModelAddress($initialAddress)),
                    new AddressUpdated($this->id, LegacyAddress::fromUdb3ModelAddress($updatedAddress)),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_set_an_initial_address_and_remove_it_later(): void
    {
        $initialAddress = new LegacyAddress(
            new LegacyStreet('Wetstraat 1'),
            new LegacyPostalCode('1000'),
            new LegacyLocality('Brussel'),
            Country::fromNative('BE')
        );

        $this->scenario
            ->given([$this->organizerCreatedWithUniqueWebsite, new AddressUpdated($this->id, $initialAddress)])
            ->when(
                function (Organizer $organizer) {
                    // Remove the address twice with the same value so we can
                    // test it doesn't get recorded the second time.
                    $organizer->removeAddress();
                    $organizer->removeAddress();
                }
            )
            ->then(
                [
                    new AddressRemoved($this->id),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_set_an_initial_contact_point_if_not_empty_and_can_update_it_later_if_changed(): void
    {
        $emptyContactPoint = new ContactPoint();

        $initialContactPoint = new ContactPoint(['0444/444444']);
        $updatedContactPoint = new ContactPoint(['0455/454545'], ['foo@bar.com']);

        $this->scenario
            ->given([$this->organizerCreatedWithUniqueWebsite])
            ->when(
                function (Organizer $organizer) use ($emptyContactPoint, $initialContactPoint, $updatedContactPoint) {
                    // Should NOT record an event.
                    $organizer->updateContactPoint($emptyContactPoint);

                    // Update the contact point twice with the same value so we
                    // can test it doesn't get recorded the second time.
                    $organizer->updateContactPoint($initialContactPoint);
                    $organizer->updateContactPoint($initialContactPoint);

                    $organizer->updateContactPoint($updatedContactPoint);

                    // Should get recorded. It's empty but users should be able
                    // to remove contact point info.
                    $organizer->updateContactPoint($emptyContactPoint);
                }
            )
            ->then(
                [
                    new ContactPointUpdated($this->id, $initialContactPoint),
                    new ContactPointUpdated($this->id, $updatedContactPoint),
                    new ContactPointUpdated($this->id, $emptyContactPoint),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_website_when_different_from_the_current_website(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateWebsite(new Url('http://www.stuk.be'));
                    $organizer->updateWebsite(new Url('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with website 'http://www.stuk.be'.
                    new WebsiteUpdated(
                        $this->id,
                        LegacyUrl::fromNative('http://www.hetdepot.be')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_website_when_organizer_imported_from_udb2(): void
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->given(
                [
                    new OrganizerImportedFromUDB2(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateWebsite(new Url('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with an empty website.
                    new WebsiteUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        LegacyUrl::fromNative('http://www.hetdepot.be')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_title_when_different_from_same_language_title(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateTitle(
                        new Title('STUK'),
                        new Language('en')
                    );
                    $organizer->updateTitle(
                        new Title('Het Depot'),
                        new Language('en')
                    );
                    $organizer->updateTitle(
                        new Title('Het Depot'),
                        new Language('en')
                    );
                    $organizer->updateTitle(
                        new Title('Le Depot'),
                        new Language('fr')
                    );
                    $organizer->updateTitle(
                        new Title('STUK'),
                        new Language('fr')
                    );
                    $organizer->updateTitle(
                        new Title('STUK'),
                        new Language('fr')
                    );
                }
            )
            ->then(
                [
                    // Organizer was created with 'nl' title STUK.
                    new TitleUpdated(
                        $this->id,
                        'Het Depot'
                    ),
                    new TitleTranslated(
                        $this->id,
                        'Le Depot',
                        'fr'
                    ),
                    new TitleTranslated(
                        $this->id,
                        'STUK',
                        'fr'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_a_title(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateTitle(
                        new Title('Pièce'),
                        new Language('fr')
                    );
                }
            )
            ->then(
                [
                    new TitleTranslated(
                        $this->id,
                        'Pièce',
                        'fr'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_an_address(): void
    {
        $addressFr = new Address(
            new Street('Rue de la Loi 1'),
            new PostalCode('1000'),
            new Locality('Bruxelles'),
            new CountryCode('BE')
        );

        $addressEn = new Address(
            new Street('Gesetz Straße 1'),
            new PostalCode('1000'),
            new Locality('Brüssel'),
            new CountryCode('BE')
        );

        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new AddressUpdated(
                        $this->id,
                        new LegacyAddress(
                            new LegacyStreet('Wetstraat 1'),
                            new LegacyPostalCode('1000'),
                            new LegacyLocality('Brussel'),
                            Country::fromNative('BE')
                        )
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) use ($addressFr, $addressEn) {
                    $organizer->updateAddress(
                        $addressFr,
                        new Language('fr')
                    );
                    $organizer->updateAddress(
                        $addressEn,
                        new Language('de')
                    );
                }
            )
            ->then(
                [
                    new AddressTranslated(
                        $this->id,
                        LegacyAddress::fromUdb3ModelAddress($addressFr),
                        new LegacyLanguage('fr')
                    ),
                    new AddressTranslated(
                        $this->id,
                        LegacyAddress::fromUdb3ModelAddress($addressEn),
                        new LegacyLanguage('de')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_sets_the_title_on_organizer_imported_from_udb2(): void
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->given(
                [
                    new OrganizerImportedFromUDB2(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateTitle(
                        new Title('DE Studio'),
                        new Language('nl')
                    );
                    $organizer->updateTitle(
                        new Title('STUK'),
                        new Language('nl')
                    );
                }
            )
            ->then(
                [
                    // Organizer was imported with title 'DE Studio'.
                    new TitleUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        'STUK'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_sets_the_title_on_organizer_updated_from_udb2(): void
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) use ($cdbXml) {
                    $organizer->updateWithCdbXml(
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    );
                    $organizer->updateTitle(
                        new Title('DE Studio'),
                        new Language('en')
                    );
                    $organizer->updateTitle(
                        new Title('Het Depot'),
                        new Language('en')
                    );
                }
            )
            ->then(
                [
                    // Organizer was created with title 'STUK,
                    // but then imported with title 'DE Studio'.
                    new OrganizerUpdatedFromUDB2(
                        $this->id,
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                    new TitleUpdated(
                        $this->id,
                        'Het Depot'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_be_deleted(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->delete();
                }
            )
            ->then(
                [
                    new OrganizerDeleted($this->id),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_be_deleted_only_once(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->delete();
                    $organizer->delete();
                }
            )
            ->then(
                [
                    new OrganizerDeleted($this->id),
                ]
            );
    }

    protected function getAggregateRootClass(): string
    {
        return Organizer::class;
    }

    private function getCdbXML(string $filename): string
    {
        return file_get_contents(__DIR__ . '/' . $filename);
    }
}
