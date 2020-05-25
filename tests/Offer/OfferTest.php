<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\CalendarUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\ThemeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ImageUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateImage;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Item\Item;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Exception;
use ValueObjects\Identity\UUID;
use ValueObjects\Person\Age;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class OfferTest extends AggregateRootScenarioTestCase
{
    /**
     * @inheritdoc
     */
    protected function getAggregateRootClass()
    {
        return Item::class;
    }

    /**
     * @var Item
     */
    protected $offer;

    /**
     * @var LabelCollection
     */
    protected $labels;

    /**
     * @var Image
     */
    protected $image;

    public function setUp()
    {
        parent::setUp();

        $this->offer = new Item();
        $this->offer->apply(new ItemCreated('foo'));

        $this->labels = (new LabelCollection())
            ->with(new Label('test'))
            ->with(new Label('label'))
            ->with(new Label('cultuurnet'));
        $this->image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/gif'),
            new Description('my favorite giphy gif'),
            new CopyrightHolder('Bert Ramakers'),
            Url::fromNative('http://foo.bar/media/my_favorite_giphy_gif.gif'),
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_should_only_change_the_theme_when_updating_with_another_id()
    {
        $itemId = UUID::generateAsString();
        $circusTheme = new Theme('0.52.0.0.0', 'Circus');
        $musicalTheme = new Theme('1.4.0.0.0', 'Musical');

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use ($circusTheme, $musicalTheme) {
                    $item->updateTheme($circusTheme);
                    $item->updateTheme($circusTheme);
                    $item->updateTheme($musicalTheme);
                }
            )
            ->then([
                new ThemeUpdated($itemId, $circusTheme),
                new ThemeUpdated($itemId, $musicalTheme),
            ]);
    }

    /**
     * @test
     */
    public function it_should_only_change_the_type_when_updating_with_another_id()
    {
        $itemId = UUID::generateAsString();
        $filmType = new EventType("0.50.6.0.0", "Film");
        $concertType = new EventType("0.50.4.0.0", "Concert");

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use ($filmType, $concertType) {
                    $item->updateType($filmType);
                    $item->updateType($filmType);
                    $item->updateType($concertType);
                }
            )
            ->then([
                new TypeUpdated($itemId, $filmType),
                new TypeUpdated($itemId, $concertType),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_facilities_when_changed()
    {
        $itemId = UUID::generateAsString();

        $facilities = [
            new Facility("3.27.0.0.0", "Rolstoeltoegankelijk"),
            new Facility("3.30.0.0.0", "Rolstoelpodium"),
        ];

        $sameFacilities = [
            new Facility("3.30.0.0.0", "Rolstoelpodium"),
            new Facility("3.27.0.0.0", "Rolstoeltoegankelijk"),
        ];

        $otherFacilities = [
            new Facility("3.34.0.0.0", "Vereenvoudigde informatie"),
            new Facility("3.38.0.0.0", "Inter-assistentie"),
        ];

        $moreFacilities = [
            new Facility("3.34.0.0.0", "Vereenvoudigde informatie"),
            new Facility("3.38.0.0.0", "Inter-assistentie"),
            new Facility("3.40.0.0.0", "Inter-events"),
        ];

        $lessFacilities = [
            new Facility("3.34.0.0.0", "Vereenvoudigde informatie"),
        ];

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use (
                    $facilities,
                    $sameFacilities,
                    $otherFacilities,
                    $moreFacilities,
                    $lessFacilities
                ) {
                    $item->updateFacilities($facilities);
                    $item->updateFacilities($sameFacilities);
                    $item->updateFacilities($otherFacilities);
                    $item->updateFacilities($moreFacilities);
                    $item->updateFacilities($lessFacilities);
                }
            )
            ->then([
                new FacilitiesUpdated($itemId, $facilities),
                new FacilitiesUpdated($itemId, $otherFacilities),
                new FacilitiesUpdated($itemId, $moreFacilities),
                new FacilitiesUpdated($itemId, $lessFacilities),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_contact_point_when_changed()
    {
        $itemId = UUID::generateAsString();

        $contactPoint = new ContactPoint(
            ['016/101010',],
            ['test@2dotstwice.be', 'admin@2dotstwice.be'],
            ['http://www.2dotstwice.be']
        );

        $sameContactPoint = new ContactPoint(
            ['016/101010',],
            ['test@2dotstwice.be', 'admin@2dotstwice.be'],
            ['http://www.2dotstwice.be']
        );

        $otherContactPoint = new ContactPoint(
            ['02/101010',],
            ['admin@public.be', 'test@public.be'],
            ['http://www.public.be']
        );

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use (
                    $contactPoint,
                    $sameContactPoint,
                    $otherContactPoint
                ) {
                    $item->updateContactPoint($contactPoint);
                    $item->updateContactPoint($sameContactPoint);
                    $item->updateContactPoint($otherContactPoint);
                }
            )
            ->then([
                new ContactPointUpdated($itemId, $contactPoint),
                new ContactPointUpdated($itemId, $otherContactPoint),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_typical_age_range_when_changed()
    {
        $itemId = UUID::generateAsString();

        $typicalAgeRange = new AgeRange(new Age(8), new Age(11));
        $sameAgeRange = new AgeRange(new Age(8), new Age(11));
        $otherAgeRange = new AgeRange(new Age(1), new Age(99));
        $allAges = new AgeRange();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use (
                    $typicalAgeRange,
                    $sameAgeRange,
                    $otherAgeRange,
                    $allAges
                ) {
                    $item->updateTypicalAgeRange($typicalAgeRange);
                    $item->updateTypicalAgeRange($sameAgeRange);
                    $item->deleteTypicalAgeRange();
                    $item->updateTypicalAgeRange($sameAgeRange);
                    $item->updateTypicalAgeRange($otherAgeRange);
                    $item->updateTypicalAgeRange($allAges);
                }
            )
            ->then([
                new TypicalAgeRangeUpdated($itemId, $typicalAgeRange),
                new TypicalAgeRangeDeleted($itemId),
                new TypicalAgeRangeUpdated($itemId, $sameAgeRange),
                new TypicalAgeRangeUpdated($itemId, $otherAgeRange),
                new TypicalAgeRangeUpdated($itemId, $allAges),
            ]);
    }

    /**
     * @test
     */
    public function it_should_remember_added_labels()
    {
        $itemId = UUID::generateAsString();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) {
                    $item->addLabel(new Label('purple'));
                    $item->addLabel(new Label('orange'));
                    $item->addLabel(new Label('green'));

                    $item->addLabel(new Label('purple'));
                    $item->addLabel(new Label('orange'));
                    $item->addLabel(new Label('green'));
                }
            )
            ->then([
                new LabelAdded($itemId, new Label('purple')),
                new LabelAdded($itemId, new Label('orange')),
                new LabelAdded($itemId, new Label('green')),
            ]);
    }

    /**
     * @test
     */
    public function it_should_remember_which_labels_were_removed()
    {
        $itemId = UUID::generateAsString();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) {
                    $item->addLabel(new Label('purple'));
                    $item->addLabel(new Label('orange'));
                    $item->addLabel(new Label('green'));

                    $item->removeLabel(new Label('purple'));
                    $item->addLabel(new Label('purple'));
                }
            )
            ->then([
                new LabelAdded($itemId, new Label('purple')),
                new LabelAdded($itemId, new Label('orange')),
                new LabelAdded($itemId, new Label('green')),
                new LabelRemoved($itemId, new Label('purple')),
                new LabelAdded($itemId, new Label('purple')),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_import_labels()
    {
        $itemId = UUID::generateAsString();

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

        $labelsToKeepIfApplied = new Labels(
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('existing_label_3'),
                true
            )
        );

        $labelsToRemoveWhenOnOffer = new Labels(
            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                new LabelName('existing_label_2'),
                true
            )
        );

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new LabelAdded($itemId, new Label('existing_label_1')),
                new LabelAdded($itemId, new Label('existing_label_2')),
                new LabelAdded($itemId, new Label('existing_label_3')),
            ])
            ->when(
                function (Item $item) use ($labels, $labelsToKeepIfApplied, $labelsToRemoveWhenOnOffer) {
                    $item->importLabels($labels, $labelsToKeepIfApplied, $labelsToRemoveWhenOnOffer);
                    $item->importLabels($labels, $labelsToKeepIfApplied, $labelsToRemoveWhenOnOffer);
                }
            )
            ->then([
                new LabelsImported(
                    $itemId,
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('new_label_1'),
                            true
                        )
                    )
                ),
                new LabelAdded($itemId, new Label('new_label_1')),
                new LabelRemoved($itemId, new Label('existing_label_2')),
            ]);
    }

    /**
     * @test
     * @expectedException     Exception
     */
    public function it_should_throw_an_exception_when_selecting_an_unknown_main_image()
    {
        $this->offer->selectMainImage($this->image);
    }

    /**
     * @test
     */
    public function it_should_set_the_main_image_when_selecting_another_one()
    {
        $anotherImage = new Image(
            new UUID('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            Url::fromNative('http://foo.bar/media/my_best_selfie.gif'),
            new Language('en')
        );
        $image = $this->image;

        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) use ($image, $anotherImage) {
                    $item->addImage($image);
                    $item->addImage($anotherImage);
                    $item->selectMainImage($anotherImage);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $image),
                    new ImageAdded('someId', $anotherImage),
                    new MainImageSelected('someId', $anotherImage),
                ]
            );
    }

    /**
     * @test
     * @see https://jira.uitdatabank.be/browse/III-2693
     */
    public function it_can_select_main_image_even_after_image_update(): void
    {
        $anotherImage = new Image(
            new UUID('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            Url::fromNative('http://foo.bar/media/my_best_selfie.gif'),
            new Language('en')
        );

        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) use ($anotherImage) {
                    $item->addImage($this->image);
                    $item->addImage($anotherImage);
                    $item->updateImage(
                        $anotherImage->getMediaObjectId(),
                        new Description('new description'),
                        new CopyrightHolder('new copyright holder')
                    );
                    $item->selectMainImage($anotherImage);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $this->image),
                    new ImageAdded('someId', $anotherImage),
                    new ImageUpdated(
                        'someId',
                        $anotherImage->getMediaObjectId(),
                        new Description('new description'),
                        new CopyrightHolder('new copyright holder')
                    ),
                    new MainImageSelected('someId', $anotherImage),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_presence_of_image_when_updating()
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->addImage($this->image);
                    $item->removeImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new Description('my favorite cat'),
                        new CopyrightHolder('Jane Doe')
                    );
                    $item->addImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new Description('my favorite cat'),
                        new CopyrightHolder('Jane Doe')
                    );
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $this->image),
                    new ImageRemoved('someId', $this->image),
                    new ImageAdded('someId', $this->image),
                    new ImageUpdated(
                        'someId',
                        $this->image->getMediaObjectId(),
                        new Description('my favorite cat'),
                        new CopyrightHolder('Jane Doe')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_difference_of_image_when_updating()
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->addImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        $this->image->getDescription(),
                        $this->image->getCopyrightHolder()
                    );
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new Description('other description'),
                        $this->image->getCopyrightHolder()
                    );
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new Description('other description'),
                        new CopyrightHolder('other copyright')
                    );
                }
            )
            ->then(
                [
                    new ImageAdded(
                        'someId',
                        $this->image
                    ),
                    new ImageUpdated(
                        'someId',
                        $this->image->getMediaObjectId(),
                        new Description('other description'),
                        $this->image->getCopyrightHolder()
                    ),
                    new ImageUpdated(
                        'someId',
                        $this->image->getMediaObjectId(),
                        new Description('other description'),
                        new CopyrightHolder('other copyright')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_presence_when_adding_image()
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->addImage($this->image);
                    $item->addImage($this->image);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $this->image),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image()
    {
        $oldestImage = new Image(
            new UUID('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/gif'),
            new Description('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            Url::fromNative('http://foo.bar/media/my_best_selfie.gif'),
            new Language('en')
        );
        $newerImage = new Image(
            new UUID('fdfac613-61f9-43ac-b1a9-c75f9fd58386'),
            new MIMEType('image/jpeg'),
            new Description('pic'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://foo.bar/media/pic.jpeg'),
            new Language('en')
        );
        $originalMainImage = $this->image;

        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) use ($originalMainImage, $oldestImage, $newerImage) {
                    $item->addImage($originalMainImage);
                    $item->addImage($oldestImage);
                    $item->addImage($newerImage);
                    $item->removeImage($originalMainImage);
                    // When you attempt to make the oldest image main no event should be triggered
                    $item->selectMainImage($oldestImage);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $originalMainImage),
                    new ImageAdded('someId', $oldestImage),
                    new ImageAdded('someId', $newerImage),
                    new ImageRemoved('someId', $originalMainImage),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_make_an_image_main_when_added_to_an_item_without_existing_ones()
    {
        $firstImage = $this->image;

        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) use ($firstImage) {
                    $item->addImage($firstImage);
                    // If no event fires when selecting an image as main, it is already set.
                    $item->selectMainImage($firstImage);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $firstImage),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_trigger_a_main_image_selected_event_when_the_image_is_already_selected_as_main()
    {
        $originalMainImage = $this->image;
        $newMainImage = new Image(
            new UUID('fdfac613-61f9-43ac-b1a9-c75f9fd58386'),
            new MIMEType('image/jpeg'),
            new Description('pic'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://foo.bar/media/pic.jpeg'),
            new Language('en')
        );

        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item) use ($originalMainImage, $newMainImage) {
                    $item->addImage($originalMainImage);
                    $item->addImage($newMainImage);
                    $item->selectMainImage($newMainImage);
                    // When you attempt to make the current main image main, no events should trigger
                    $item->selectMainImage($newMainImage);
                }
            )
            ->then(
                [
                    new ImageAdded('someId', $originalMainImage),
                    new ImageAdded('someId', $newMainImage),
                    new MainImageSelected('someId', $newMainImage),
                ]
            );
    }

    /**
     * @test
     */
    public function it_publishes_an_offer_with_workflow_status_draft()
    {
        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($now) {
                $item->publish($now);
            })
            ->then([
                new Published($itemId, $now),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_publish_an_offer_more_then_once()
    {
        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new Published($itemId, $now),
            ])
            ->when(function (Item $item) use ($now) {
                $item->publish($now);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_when_trying_to_publish_a_non_draft_offer()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You can not publish an offer that is not draft');

        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new Published($itemId, $now),
                new FlaggedAsDuplicate($itemId),
            ])
            ->when(function (Item $item) use ($now) {
                $item->publish($now);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_approve_an_offer_that_is_ready_for_validation()
    {
        $itemId = UUID::generateAsString();
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->approve();
                }
            )
            ->then(
                [
                    new Approved($itemId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_approve_an_offer_more_than_once()
    {
        $itemId = UUID::generateAsString();
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->approve();
                    $item->approve();
                }
            )
            ->then(
                [
                    new Approved($itemId),
                ]
            );
    }

    /**
     * @test
     * @expectedException        Exception
     * @expectedExceptionMessage You can not approve an offer that is not ready for validation
     */
    public function it_should_not_approve_an_offer_after_it_was_rejected()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('There are spelling mistakes in the description.');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Rejected($itemId, $reason),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->approve();
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_reject_an_offer_more_than_once_for_the_same_reason()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('The title is misleading.');
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->reject($reason);
                    $item->reject($reason);
                }
            )
            ->then(
                [
                    new Rejected($itemId, $reason),
                ]
            );
    }

    /**
     * @test
     * @expectedException        Exception
     * @expectedExceptionMessage The offer has already been rejected for another reason: The title is misleading.
     */
    public function it_should_not_reject_an_offer_that_is_already_rejected_for_a_different_reason()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('The title is misleading.');
        $differentReason = new StringLiteral('I\'m afraid I can\'t let you do that.');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Rejected($itemId, $reason),
                ]
            )
            ->when(
                function (Item $item) use ($differentReason) {
                    $item->reject($differentReason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_reject_an_offer_that_is_ready_for_validation_with_a_reason()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('You forgot to add an organizer.');
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->reject($reason);
                }
            )
            ->then(
                [
                    new Rejected($itemId, $reason),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_flag_an_offer_that_is_ready_for_validation_as_duplicate()
    {
        $itemId = UUID::generateAsString();
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->flagAsDuplicate();
                }
            )
            ->then(
                [
                    new FlaggedAsDuplicate($itemId),
                ]
            );
    }

    /**
     * @test
     * @expectedException        Exception
     * @expectedExceptionMessage The offer has already been rejected for another reason: duplicate
     */
    public function it_should_reject_an_offer_when_it_is_flagged_as_duplicate()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('The theme does not match the description.');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new FlaggedAsDuplicate($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_flag_an_offer_that_is_ready_for_validation_as_inappropriate()
    {
        $itemId = UUID::generateAsString();
        $now = new \DateTime();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Published($itemId, $now),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->flagAsInappropriate();
                }
            )
            ->then(
                [
                    new FlaggedAsInappropriate($itemId),
                ]
            );
    }

    /**
     * @test
     * @expectedException        Exception
     * @expectedExceptionMessage The offer has already been rejected for another reason: inappropriate
     */
    public function it_should_not_reject_an_offer_when_it_is_flagged_as_inappropriate()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('The theme does not match the description.');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new FlaggedAsInappropriate($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     * @expectedException        Exception
     * @expectedExceptionMessage You can not reject an offer that is not ready for validation
     */
    public function it_should_not_reject_an_offer_that_is_flagged_as_approved()
    {
        $itemId = UUID::generateAsString();
        $reason = new StringLiteral('Yeah, but no, but yeah...');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Approved($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason) {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_update_an_offer_with_an_organizer_when_it_is_already_set()
    {
        $itemId = UUID::generateAsString();
        $organizerId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new OrganizerUpdated($itemId, $organizerId),
                ]
            )
            ->when(
                function (Item $item) use ($organizerId) {
                    $item->updateOrganizer($organizerId);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_update_an_offer_with_the_same_organizer_after_removing_it()
    {
        $itemId = UUID::generateAsString();
        $organizerId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new OrganizerUpdated($itemId, $organizerId),
                    new OrganizerDeleted($itemId, $organizerId),
                ]
            )
            ->when(
                function (Item $item) use ($organizerId) {
                    $item->updateOrganizer($organizerId);
                }
            )
            ->then([new OrganizerUpdated($itemId, $organizerId)]);
    }

    /**
     * @test
     */
    public function it_should_delete_the_current_organizer_regardless_of_the_id()
    {
        $itemId = UUID::generateAsString();
        $organizerId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new OrganizerUpdated($itemId, $organizerId),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->deleteCurrentOrganizer();
                }
            )
            ->then([new OrganizerDeleted($itemId, $organizerId)]);
    }

    /**
     * @test
     */
    public function it_should_not_delete_the_current_organizer_if_there_is_none()
    {
        $itemId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(
                function (Item $item) {
                    $item->deleteCurrentOrganizer();
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_title_update_that_does_not_change_the_existing_title()
    {
        $itemId = UUID::generateAsString();
        $title = new Title('Titel');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, $title),
                ]
            )
            ->when(
                function (Item $item) use ($title) {
                    $item->updateTitle(new Language('nl'), $title);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_translate_the_title_when_updating_with_a_foreign_language()
    {
        $itemId = UUID::generateAsString();
        $title = new Title('The Title');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, new Title('Een titel')),
                ]
            )
            ->when(
                function (Item $item) use ($title, $language) {
                    $item->updateTitle($language, $title);
                }
            )
            ->then([
                new TitleTranslated($itemId, $language, $title),
            ]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_title_translation_that_does_not_translate_the_title()
    {
        $itemId = UUID::generateAsString();
        $title = new Title('The Title');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, new Title('Een titel')),
                ]
            )
            ->when(
                function (Item $item) use ($title, $language) {
                    $item->updateTitle($language, $title);
                    $item->updateTitle($language, $title);
                    $item->updateTitle(new Language('nl'), new Title('Een titel'));
                }
            )
            ->then([
                new TitleTranslated($itemId, $language, $title),
            ]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_description_update_that_does_not_change_the_existing_descriptions()
    {
        $itemId = UUID::generateAsString();
        $description = new \CultuurNet\UDB3\Description('Een beschrijving');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new DescriptionUpdated($itemId, $description),
                ]
            )
            ->when(
                function (Item $item) use ($description) {
                    $item->updateDescription($description, new Language('nl'));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_translate_the_description_when_updating_with_a_foreign_language()
    {
        $itemId = UUID::generateAsString();
        $description = new \CultuurNet\UDB3\Description('La description');
        $language = new Language('fr');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new DescriptionUpdated($itemId, new \CultuurNet\UDB3\Description('Een beschrijving')),
                ]
            )
            ->when(
                function (Item $item) use ($description, $language) {
                    $item->updateDescription($description, $language);
                }
            )
            ->then([
                new DescriptionTranslated($itemId, $language, $description),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_events()
    {
        $itemId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $sameCalendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $otherCalendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-28T12:12:12+01:00')
        );

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($calendar, $sameCalendar, $otherCalendar) {
                    $item->updateCalendar($calendar);
                    $item->updateCalendar($sameCalendar);
                    $item->updateCalendar($otherCalendar);
                }
            )
            ->then(
                [
                    new CalendarUpdated($itemId, $calendar),
                    new CalendarUpdated($itemId, $otherCalendar),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_booking_info_updated_events()
    {
        $itemId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $bookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(new Language('nl'), new StringLiteral('publiq')),
            '02 123 45 67',
            'info@publiq.be'
        );

        $sameBookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(new Language('nl'), new StringLiteral('publiq')),
            '02 123 45 67',
            'info@publiq.be'
        );

        $otherBookingInfo = new BookingInfo(
            'www.2dotstwice.be',
            new MultilingualString(new Language('nl'), new StringLiteral('2dotstwice')),
            '016 12 34 56',
            'info@2dotstwice.be'
        );

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($bookingInfo, $sameBookingInfo, $otherBookingInfo) {
                    $item->updateBookingInfo($bookingInfo);
                    $item->updateBookingInfo($sameBookingInfo);
                    $item->updateBookingInfo($otherBookingInfo);
                }
            )
            ->then(
                [
                    new BookingInfoUpdated($itemId, $bookingInfo),
                    new BookingInfoUpdated($itemId, $otherBookingInfo),
                ]
            );
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     * @param Image $image
     * @param ImageCollection $imageCollection
     */
    public function it_should_import_images_from_udb2_as_media_object_and_main_image(
        Image $image,
        ImageCollection $imageCollection
    ) {
        $itemId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $imageCollection) {
                $item->importImagesFromUDB2($imageCollection);
                $item->addImage($image);
                $item->selectMainImage($image);
            })
            ->then([new ImagesImportedFromUDB2($itemId, $imageCollection)]);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     * @param Image $image
     */
    public function it_should_keep_images_translated_in_ubd3_when_updating_images_from_udb2(
        Image $image
    ) {
        $itemId = UUID::generateAsString();

        $dutchUdb3Image = new Image(
            new UUID('0773EB2A-54BE-49AD-B261-5D1099F319D4'),
            new MIMEType('image/jpg'),
            new Description('mijn favoriete wallpaper'),
            new CopyrightHolder('Dirk Dirkingn'),
            Url::fromNative('http://foo.bar/media/mijn_favoriete_wallpaper_<3.jpg'),
            new Language('nl')
        );

        $udb2Images = ImageCollection::fromArray([
            new Image(
                new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
                new MIMEType('image/jpg'),
                new Description('episch panorama'),
                new CopyrightHolder('Dirk Dirkingn'),
                Url::fromNative('http://foo.bar/media/episch_panorama.jpg'),
                new Language('nl')
            ),
        ]);

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $dutchUdb3Image, $udb2Images) {
                $item->addImage($image);
                $item->addImage($dutchUdb3Image);
                $item->importImagesFromUDB2($udb2Images);
                $item->addImage($image);
                $item->addImage($dutchUdb3Image);
            })
            ->then([
                new ImageAdded($itemId, $image),
                new ImageAdded($itemId, $dutchUdb3Image),
                new ImagesImportedFromUDB2($itemId, $udb2Images),
                new ImageAdded($itemId, $dutchUdb3Image),
            ]);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     * @param Image $image
     * @param ImageCollection $imageCollection
     */
    public function it_should_update_images_from_udb2_as_media_object_and_main_image(
        Image $image,
        ImageCollection $imageCollection
    ) {
        $itemId = UUID::generateAsString();

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $imageCollection) {
                $item->UpdateImagesFromUDB2($imageCollection);
                $item->addImage($image);
                $item->selectMainImage($image);
            })
            ->then([new ImagesUpdatedFromUDB2($itemId, $imageCollection)]);
    }

    public function imageCollectionDataProvider()
    {
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkingn'),
            Url::fromNative('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        return [
            'single image' => [
                'mainImage' => $image,
                'imageCollection' => ImageCollection::fromArray([$image]),
            ],
        ];
    }
}
