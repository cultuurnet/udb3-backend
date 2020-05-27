<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
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
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url;

class OrganizerTest extends AggregateRootScenarioTestCase
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var OrganizerCreatedWithUniqueWebsite
     */
    private $organizerCreatedWithUniqueWebsite;

    public function setUp()
    {
        parent::setUp();

        $this->id = '18eab5bf-09bf-4521-a8b4-c0f4a585c096';
        $this->mainLanguage = new Language('en');
        $this->website = Url::fromNative('http://www.stuk.be');
        $this->title = new Title('STUK');

        $this->organizerCreatedWithUniqueWebsite = new OrganizerCreatedWithUniqueWebsite(
            $this->id,
            new Language('en'),
            $this->website,
            $this->title
        );
    }

    /**
     * @test
     */
    public function it_imports_from_udb2_actors_and_takes_labels_into_account()
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
    public function it_updates_from_udb2_actors_and_takes_labels_into_account()
    {
        $cdbXml = $this->getCdbXML('organizer_with_keyword.cdbxml.xml');

        $this->scenario
            ->withAggregateId('404EE8DE-E828-9C07-FE7D12DC4EB24480')
            ->given(
                [
                    new OrganizerCreated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Title('DE Studio'),
                        [
                            new Address(
                                new Street('Wetstraat 1'),
                                new PostalCode('1000'),
                                new Locality('Brussel'),
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
    public function it_can_import_labels()
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
    public function it_can_create_new_organizers()
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
    public function it_can_set_an_initial_address_and_update_it_later_if_changed()
    {
        $initialAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            Country::fromNative('BE')
        );

        $updatedAddress = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $language = $this->organizerCreatedWithUniqueWebsite->getMainLanguage();

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
                    new AddressUpdated($this->id, $initialAddress),
                    new AddressUpdated($this->id, $updatedAddress),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_set_an_initial_address_and_remove_it_later()
    {
        $initialAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
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
    public function it_can_set_an_initial_contact_point_if_not_empty_and_can_update_it_later_if_changed()
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
    public function it_can_update_the_website_when_different_from_the_current_website()
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer) {
                    $organizer->updateWebsite(Url::fromNative('http://www.stuk.be'));
                    $organizer->updateWebsite(Url::fromNative('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with website 'http://www.stuk.be'.
                    new WebsiteUpdated(
                        $this->id,
                        Url::fromNative('http://www.hetdepot.be')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_website_when_organizer_imported_from_udb2()
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
                    $organizer->updateWebsite(Url::fromNative('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with an empty website.
                    new WebsiteUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        Url::fromNative('http://www.hetdepot.be')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_title_when_different_from_same_language_title()
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
                        new Title('Het Depot')
                    ),
                    new TitleTranslated(
                        $this->id,
                        new Title('Le Depot'),
                        new Language('fr')
                    ),
                    new TitleTranslated(
                        $this->id,
                        new Title('STUK'),
                        new Language('fr')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_a_title()
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
                        new Title('Pièce'),
                        new Language('fr')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_an_address()
    {
        $addressFr = new Address(
            new Street('Rue de la Loi 1'),
            new PostalCode('1000'),
            new Locality('Bruxelles'),
            Country::fromNative('BE')
        );

        $addressEn = new Address(
            new Street('Gesetz Straße 1'),
            new PostalCode('1000'),
            new Locality('Brüssel'),
            Country::fromNative('BE')
        );

        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new AddressUpdated(
                        $this->id,
                        new Address(
                            new Street('Wetstraat 1'),
                            new PostalCode('1000'),
                            new Locality('Brussel'),
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
                        $addressFr,
                        new Language('fr')
                    ),
                    new AddressTranslated(
                        $this->id,
                        $addressEn,
                        new Language('de')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_sets_the_title_on_organizer_imported_from_udb2()
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
                        new Title('STUK')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_sets_the_title_on_organizer_updated_from_udb2()
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
                        new Title('Het Depot')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_be_deleted()
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
     * Returns a string representing the aggregate root
     *
     * @return string AggregateRoot
     */
    protected function getAggregateRootClass()
    {
        return Organizer::class;
    }

    /**
     * @param string $filename
     * @return string
     */
    private function getCdbXML($filename)
    {
        return file_get_contents(__DIR__ . '/' . $filename);
    }
}
