<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\ImageMustBeLinkedException;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\DescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\MainImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\SampleFiles;

class OrganizerTest extends AggregateRootScenarioTestCase
{
    private string $id;

    private Language $mainLanguage;

    private Url $website;

    private Title $title;

    private OrganizerCreatedWithUniqueWebsite $organizerCreatedWithUniqueWebsite;

    public function setUp(): void
    {
        parent::setUp();

        $this->id = '18eab5bf-09bf-4521-a8b4-c0f4a585c096';
        $this->mainLanguage = new Language('en');
        $this->website = new Url('http://www.stuk.be');
        $this->title = new Title('STUK');

        $this->organizerCreatedWithUniqueWebsite = new OrganizerCreatedWithUniqueWebsite(
            $this->id,
            'en',
            $this->website->toString(),
            $this->title->toString()
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
                function (Organizer $organizer): void {
                    $organizer->addLabel(new Label(new LabelName('foo')));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_import_same_label_with_correct_visibility_after_udb2_import_with_incorrect_visibility(): void
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
                function (Organizer $organizer): void {
                    $organizer->importLabels(
                        new Labels(
                            new Label(
                                new LabelName('foo'),
                                false
                            )
                        )
                    );
                }
            )
            ->then(
                [
                    new LabelsImported(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        [],
                        ['foo']
                    ),
                    new LabelRemoved(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        'foo',
                        true
                    ),
                    new LabelAdded(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        'foo',
                        false
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_import_labels(): void
    {
        $labels = new Labels(
            new Label(
                new LabelName('new_label_1'),
                true
            ),
            new Label(
                new LabelName('existing_label_1'),
                true
            )
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new LabelsImported(
                        $this->id,
                        [
                            'existing_label_1',
                            'existing_label_2',
                            'existing_label_3',
                        ],
                        []
                    ),
                    new LabelAdded($this->id, 'existing_label_1'),
                    new LabelAdded($this->id, 'existing_label_2'),
                    new LabelAdded($this->id, 'existing_label_3'),
                ]
            )
            ->when(
                function (Organizer $organizer) use ($labels): void {
                    $organizer->importLabels($labels);
                    $organizer->importLabels($labels);
                }
            )
            ->then(
                [
                    new LabelsImported(
                        $this->id,
                        ['new_label_1'],
                        []
                    ),
                    new LabelRemoved($this->id, 'existing_label_2'),
                    new LabelRemoved($this->id, 'existing_label_3'),
                    new LabelAdded($this->id, 'new_label_1'),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_remove_invalid_labels(): void
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new LabelAdded($this->id, 'invalid;label'),
                    new LabelAdded($this->id, "newline\r\nLabel"),
                ]
            )
            ->when(
                function (Organizer $organizer): void {
                    $organizer->removeLabel('invalid;label');
                    $organizer->removeLabel("newline\r\nLabel");
                }
            )
            ->then(
                [
                    new LabelRemoved($this->id, 'invalid;label'),
                    new LabelRemoved($this->id, "newline\r\nLabel"),
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

        $language = new Language($this->organizerCreatedWithUniqueWebsite->getMainLanguage());

        $this->scenario
            ->given([$this->organizerCreatedWithUniqueWebsite])
            ->when(
                function (Organizer $organizer) use ($initialAddress, $updatedAddress, $language): void {
                    $organizer->updateAddress($initialAddress, $language);

                    // Update the address twice with the same value so we can
                    // test it doesn't get recorded the second time.
                    $organizer->updateAddress($updatedAddress, $language);
                    $organizer->updateAddress($updatedAddress, $language);
                }
            )
            ->then(
                [
                    new AddressUpdated(
                        $this->id,
                        $initialAddress->getStreet()->toString(),
                        $initialAddress->getPostalCode()->toString(),
                        $initialAddress->getLocality()->toString(),
                        $initialAddress->getCountryCode()->toString()
                    ),
                    new AddressUpdated(
                        $this->id,
                        $updatedAddress->getStreet()->toString(),
                        $updatedAddress->getPostalCode()->toString(),
                        $updatedAddress->getLocality()->toString(),
                        $updatedAddress->getCountryCode()->toString()
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_set_an_initial_address_and_remove_it_later(): void
    {
        $initialAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new AddressUpdated(
                        $this->id,
                        $initialAddress->getStreet()->toString(),
                        $initialAddress->getPostalCode()->toString(),
                        $initialAddress->getLocality()->toString(),
                        $initialAddress->getCountryCode()->toString()
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer): void {
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

        $initialContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0444/444444'))
        );
        $updatedContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0455/454545')),
            new EmailAddresses(new EmailAddress('foo@bar.com'))
        );

        $this->scenario
            ->given([$this->organizerCreatedWithUniqueWebsite])
            ->when(
                function (Organizer $organizer) use ($emptyContactPoint, $initialContactPoint, $updatedContactPoint): void {
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
                    new ContactPointUpdated($this->id, ['0444/444444']),
                    new ContactPointUpdated($this->id, ['0455/454545'], ['foo@bar.com']),
                    new ContactPointUpdated($this->id),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_broken_contactpoints(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                    new ContactPointUpdated(
                        $this->id,
                        [],
                        ['broken@email'],
                        ['htps://.broken-site']
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer): void {
                    $organizer->updateContactPoint(
                        new ContactPoint(
                            new TelephoneNumbers(),
                            new EmailAddresses(
                                new EmailAddress('fixed@email.be')
                            ),
                            new Urls(
                                new Url('https://fixed-site.be')
                            )
                        )
                    );
                }
            )
            ->then(
                [
                    new ContactPointUpdated($this->id, [], ['fixed@email.be'], ['https://fixed-site.be']),
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
                function (Organizer $organizer): void {
                    $organizer->updateWebsite(new Url('http://www.stuk.be'));
                    $organizer->updateWebsite(new Url('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with website 'http://www.stuk.be'.
                    new WebsiteUpdated(
                        $this->id,
                        'http://www.hetdepot.be'
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
                function (Organizer $organizer): void {
                    $organizer->updateWebsite(new Url('http://www.hetdepot.be'));
                }
            )
            ->then(
                [
                    // Organizer was created with an empty website.
                    new WebsiteUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        'http://www.hetdepot.be'
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
                function (Organizer $organizer): void {
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
                function (Organizer $organizer): void {
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
     * @dataProvider updateDescriptionDataProvider
     */
    public function it_can_update_a_description(array $given, callable $update, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $update($organizer))
            ->then($then);
    }

    public function updateDescriptionDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Set initial description' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateDescription(
                        new Description('Description of the organizer'),
                        new Language('en')
                    );
                },
                [
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                ],
            ],
            'Try update with same description' => [
                [
                    $organizerCreated,
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->updateDescription(
                        new Description('Description of the organizer'),
                        new Language('en')
                    );
                },
                [
                ],
            ],
            'Translate description' => [
                [
                    $organizerCreated,
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->updateDescription(
                        new Description('Beschrijving van de organisatie'),
                        new Language('nl')
                    );
                },
                [
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Beschrijving van de organisatie',
                        'nl'
                    ),
                ],
            ],
            'Various description updates' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateDescription(
                        new Description('Description of the organizer'),
                        new Language('en')
                    );
                    $organizer->updateDescription(
                        new Description('Beschrijving van de organisatie'),
                        new Language('nl')
                    );
                },
                [
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Beschrijving van de organisatie',
                        'nl'
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider deleteDescriptionDataProvider
     */
    public function it_can_delete_a_description(array $given, callable $delete, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $delete($organizer))
            ->then($then);
    }

    public function deleteDescriptionDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Delete existing description' => [
                [
                    $organizerCreated,
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteDescription(new Language('en'));
                },
                [
                    new DescriptionDeleted('ae3aab28-6351-489e-a61c-c48aec0a77df', 'en'),
                ],
            ],
            'Try deleting non-existing description' => [
                [
                    $organizerCreated,
                    new DescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteDescription(new Language('fr'));
                },
                [
                ],
            ],
            'Try deleting when no description available' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteDescription(new Language('fr'));
                },
                [
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider updateEducationalDescriptionDataProvider
     */
    public function it_can_update_an_educational_description(array $given, callable $update, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $update($organizer))
            ->then($then);
    }

    public function updateEducationalDescriptionDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Set initial educational description' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateEducationalDescription(
                        new Description('Educational description of the organizer'),
                        new Language('en')
                    );
                },
                [
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                ],
            ],
            'Try update with same educational description' => [
                [
                    $organizerCreated,
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->updateEducationalDescription(
                        new Description('Educational description of the organizer'),
                        new Language('en')
                    );
                },
                [
                ],
            ],
            'Translate educational description' => [
                [
                    $organizerCreated,
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->updateEducationalDescription(
                        new Description('Educatieve beschrijving van de organisatie'),
                        new Language('nl')
                    );
                },
                [
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educatieve beschrijving van de organisatie',
                        'nl'
                    ),
                ],
            ],
            'Various educational description updates' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateEducationalDescription(
                        new Description('Educational description of the organizer'),
                        new Language('en')
                    );
                    $organizer->updateEducationalDescription(
                        new Description('Educatieve beschrijving van de organisatie'),
                        new Language('nl')
                    );
                },
                [
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educatieve beschrijving van de organisatie',
                        'nl'
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider deleteEducationalDescriptionDataProvider
     */
    public function it_can_delete_an_educational_description(array $given, callable $delete, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $delete($organizer))
            ->then($then);
    }

    public function deleteEducationalDescriptionDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Delete existing educational description' => [
                [
                    $organizerCreated,
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteEducationalDescription(new Language('en'));
                },
                [
                    new EducationalDescriptionDeleted('ae3aab28-6351-489e-a61c-c48aec0a77df', 'en'),
                ],
            ],
            'Try deleting non-existing educational description' => [
                [
                    $organizerCreated,
                    new EducationalDescriptionUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'Educational description of the organizer',
                        'en'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteEducationalDescription(new Language('fr'));
                },
                [
                ],
            ],
            'Try deleting when no educational description available' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->deleteEducationalDescription(new Language('fr'));
                },
                [
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addImageDataProvider
     */
    public function it_can_add_an_image(array $given, callable $addImage, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $addImage($organizer))
            ->then($then);
    }

    public function addImageDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Set initial image' => [
                [
                    $organizerCreated,
                ],
                function (Organizer $organizer): void {
                    $organizer->addImage(
                        new Image(
                            new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                            new Language('nl'),
                            new Description('Beschrijving van de afbeelding'),
                            new CopyrightHolder('publiq')
                        )
                    );
                },
                [
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving van de afbeelding',
                        'publiq'
                    ),
                ],
            ],
            'Prevent setting same image' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving van de afbeelding',
                        'publiq'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->addImage(
                        new Image(
                            new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                            new Language('nl'),
                            new Description('Beschrijving van de afbeelding'),
                            new CopyrightHolder('publiq')
                        )
                    );
                },
                [
                ],
            ],
            'Allow setting extra image' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving van de afbeelding',
                        'publiq'
                    ),
                ],
                function (Organizer $organizer): void {
                    $organizer->addImage(
                        new Image(
                            new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                            new Language('en'),
                            new Description('Description of the image'),
                            new CopyrightHolder('publiq')
                        )
                    );
                },
                [
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        '03789a2f-5063-4062-b7cb-95a0a2280d92',
                        'en',
                        'Description of the image',
                        'publiq'
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider updateImageDataProvider
     */
    public function it_can_update_an_image(array $given, callable $updateImage, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $updateImage($organizer))
            ->then($then);
    }

    public function updateImageDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        $imageAdded = new ImageAdded(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'cf539408-bba9-4e77-9f85-72019013db37',
            'nl',
            'Beschrijving afbeelding',
            'Rechtenhouder afbeelding'
        );

        return [
            'Update language' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                        new Language('en'),
                        new Description('Beschrijving afbeelding'),
                        new CopyrightHolder('Rechtenhouder afbeelding')
                    );
                },
                [
                    new ImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'en',
                        'Beschrijving afbeelding',
                        'Rechtenhouder afbeelding'
                    ),
                ],
            ],
            'Update description' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                        new Language('nl'),
                        new Description('Aangepaste beschrijving afbeelding'),
                        new CopyrightHolder('Rechtenhouder afbeelding')
                    );
                },
                [
                    new ImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Aangepaste beschrijving afbeelding',
                        'Rechtenhouder afbeelding'
                    ),
                ],
            ],
            'Update copyright holder' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                        new Language('nl'),
                        new Description('Beschrijving afbeelding'),
                        new CopyrightHolder('Aangepaste rechtenhouder afbeelding')
                    );
                },
                [
                    new ImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving afbeelding',
                        'Aangepaste rechtenhouder afbeelding'
                    ),
                ],
            ],
            'Update all properties of an existing image' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                        new Language('en'),
                        new Description('Aangepaste beschrijving afbeelding'),
                        new CopyrightHolder('Aangepaste rechtenhouder afbeelding')
                    );
                },
                [
                    new ImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'en',
                        'Aangepaste beschrijving afbeelding',
                        'Aangepaste rechtenhouder afbeelding'
                    ),
                ],
            ],
            'Update non existing image' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('f0515293-4e39-4679-9f39-5406dddfb234'),
                        new Language('en'),
                        new Description('Aangepaste beschrijving afbeelding'),
                        new CopyrightHolder('Aangepaste rechterhouder afbeelding')
                    );
                },
                [
                ],
            ],
            'Update with no change in values' => [
                [
                    $organizerCreated,
                    $imageAdded,
                ],
                function (Organizer $organizer): void {
                    $organizer->updateImage(
                        new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                        new Language('nl'),
                        new Description('Beschrijving afbeelding'),
                        new CopyrightHolder('Rechtenhouder afbeelding')
                    );
                },
                [
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider removeImageDataProvider
     */
    public function it_can_remove_an_image(array $given, callable $removeImage, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $removeImage($organizer))
            ->then($then);
    }

    public function removeImageDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Remove existing image' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving van de afbeelding',
                        'publiq'
                    ),
                ],
                fn (Organizer $organizer) =>
                    $organizer->removeImage(new Uuid('cf539408-bba9-4e77-9f85-72019013db37')),
                [
                    new ImageRemoved(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37'
                    ),
                ],
            ],
            'Remove image only once' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving van de afbeelding',
                        'publiq'
                    ),
                    new ImageRemoved(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37'
                    ),
                ],
                fn (Organizer $organizer) =>
                    $organizer->removeImage(new Uuid('cf539408-bba9-4e77-9f85-72019013db37')),
                [
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider updateMainImageDataProvider
     */
    public function it_can_update_the_main_image(array $given, callable $updateMainImage, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Organizer $organizer) => $updateMainImage($organizer))
            ->then($then);
    }

    public function updateMainImageDataProvider(): array
    {
        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            'ae3aab28-6351-489e-a61c-c48aec0a77df',
            'en',
            'https://www.publiq.be',
            'publiq'
        );

        return [
            'Image added sets a main image' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving',
                        'publiq'
                    ),
                ],
                fn (Organizer $organizer) =>
                    $organizer->updateMainImage(new Uuid('cf539408-bba9-4e77-9f85-72019013db37')),
                [],
            ],
            'Main image is not updated when it is already the main image' => [
                [
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving',
                        'publiq'
                    ),
                    new MainImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37'
                    ),
                ],
                fn (Organizer $organizer) =>
                    $organizer->updateMainImage(new Uuid('cf539408-bba9-4e77-9f85-72019013db37')),
                [],
            ],
            'Main image finally set' => [
                [
                    $organizerCreated,
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Beschrijving',
                        'publiq'
                    ),
                    new ImageAdded(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        '9692eef5-d844-430b-ac60-413b66227fc4',
                        'en',
                        'Description',
                        'madewithlove'
                    ),
                ],
                fn (Organizer $organizer) =>
                    $organizer->updateMainImage(new Uuid('9692eef5-d844-430b-ac60-413b66227fc4')),
                [
                    new MainImageUpdated(
                        'ae3aab28-6351-489e-a61c-c48aec0a77df',
                        '9692eef5-d844-430b-ac60-413b66227fc4'
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_selecting_an_unknown_main_image(): void
    {
        $this->expectException(ImageMustBeLinkedException::class);
        $this->scenario
            ->given([
                new OrganizerCreatedWithUniqueWebsite(
                    'ae3aab28-6351-489e-a61c-c48aec0a77df',
                    'en',
                    'https://www.publiq.be',
                    'publiq'
                ),
            ])
            ->when(fn (Organizer $organizer) => $organizer->updateMainImage(new Uuid('9692eef5-d844-430b-ac60-413b66227fc4')));
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
                        'Wetstraat 1',
                        '1000',
                        'Brussel',
                        'BE'
                    ),
                ]
            )
            ->when(
                function (Organizer $organizer) use ($addressFr, $addressEn): void {
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
                        $addressFr->getStreet()->toString(),
                        $addressFr->getPostalCode()->toString(),
                        $addressFr->getLocality()->toString(),
                        $addressFr->getCountryCode()->toString(),
                        'fr'
                    ),
                    new AddressTranslated(
                        $this->id,
                        $addressEn->getStreet()->toString(),
                        $addressEn->getPostalCode()->toString(),
                        $addressEn->getLocality()->toString(),
                        $addressEn->getCountryCode()->toString(),
                        'de'
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
                function (Organizer $organizer): void {
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
    public function it_can_be_deleted(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer): void {
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
                function (Organizer $organizer): void {
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

    /**
     * @test
     */
    public function it_can_change_the_owner(): void
    {
        $this->scenario
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite,
                ]
            )
            ->when(
                function (Organizer $organizer): void {
                    $organizer->changeOwner('5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3');
                }
            )
            ->then(
                [
                    new OwnerChanged($this->id, '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3'),
                ]
            );
    }

    protected function getAggregateRootClass(): string
    {
        return Organizer::class;
    }

    private function getCdbXML(string $filename): string
    {
        return SampleFiles::read(__DIR__ . '/' . $filename);
    }
}
