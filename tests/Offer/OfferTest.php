<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Event\Events\LabelsReplaced;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionDeleted;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\ImageUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\OwnerChanged;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\VideoAdded;
use CultuurNet\UDB3\Offer\Item\Events\VideoDeleted;
use CultuurNet\UDB3\Offer\Item\Events\VideoUpdated;
use CultuurNet\UDB3\Offer\Item\Item;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class OfferTest extends AggregateRootScenarioTestCase
{
    protected Item $offer;

    protected Labels $labels;

    protected Image $image;

    public function setUp(): void
    {
        parent::setUp();

        $this->offer = new Item();
        $this->offer->apply(new ItemCreated('foo'));

        $this->labels = (new Labels())
            ->with(new Label(new LabelName('test')))
            ->with(new Label(new LabelName('label')))
            ->with(new Label(new LabelName('cultuurnet')));
        $this->image = new Image(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/gif'),
            new MediaDescription('my favorite giphy gif'),
            new CopyrightHolder('Bert Ramakers'),
            new Url('http://foo.bar/media/my_favorite_giphy_gif.gif'),
            new Language('en')
        );
    }

    protected function getAggregateRootClass(): string
    {
        return Item::class;
    }

    /**
     * @test
     */
    public function it_should_only_change_the_owner_with_a_check_after_the_first_change(): void
    {
        $itemId = '77b4df58-b7e9-40cf-979f-ec741a072282';

        $newOwner1 = '8d4688f8-ef36-4a65-bac8-00dc846ed624';
        $newOwner2 = 'abd24691-1cc7-49c7-a85f-8dc4498f8072';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item) use ($newOwner1, $newOwner2): void {
                    $item->changeOwner($newOwner1);
                    $item->changeOwner($newOwner1);
                    $item->changeOwner($newOwner2);
                    $item->changeOwner($newOwner2);
                    $item->changeOwner($newOwner1);
                }
            )
            ->then([
                new OwnerChanged($itemId, $newOwner1),
                new OwnerChanged($itemId, $newOwner2),
                new OwnerChanged($itemId, $newOwner1),
            ]);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     */
    public function it_should_not_create_events_for_unaltered_images(Image $image): void
    {
        $itemId = '77b4df58-b7e9-40cf-979f-ec741a072282';

        $updatedImage = new Image(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new MediaDescription('my updated pic'),
            new CopyrightHolder('Dirk Dirkingn updated'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );
        $secondImage = new Image(
            new Uuid('837bd340-e939-4210-8af9-e4baedd0d44e'),
            new MIMEType('image/jpg'),
            new MediaDescription('my second pic'),
            new CopyrightHolder('Dirk Dirkingn again'),
            new Url('http://foo.bar/media/my_2nd_pic.jpg'),
            new Language('en')
        );
        $updatedImageCollection = ImageCollection::fromArray([$updatedImage])->withMain($secondImage);

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new ImageAdded($itemId, $secondImage),
                new MainImageSelected($itemId, $image),
            ])
            ->when(
                function (Item $item) use ($updatedImageCollection): void {
                    $item->importImages($updatedImageCollection);
                    $item->importImages($updatedImageCollection);
                    $item->importImages($updatedImageCollection);
                }
            )
            ->then([
                new ImageUpdated(
                    $itemId,
                    'de305d54-75b4-431b-adb2-eb6b9e546014',
                    'my updated pic',
                    'Dirk Dirkingn updated',
                    'en'
                ),
                new MainImageSelected($itemId, $secondImage),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_facilities_when_changed(): void
    {
        $itemId = '0fd9a1e5-1406-43b5-a641-4b4d77fe980d';

        $facilities = [
            new Category(new CategoryID('3.27.0.0.0'), new CategoryLabel('Rolstoeltoegankelijk'), CategoryDomain::facility()),
            new Category(new CategoryID('3.30.0.0.0'), new CategoryLabel('Rolstoelpodium'), CategoryDomain::facility()),
        ];

        $sameFacilities = [
            new Category(new CategoryID('3.30.0.0.0'), new CategoryLabel('Rolstoelpodium'), CategoryDomain::facility()),
            new Category(new CategoryID('3.27.0.0.0'), new CategoryLabel('Rolstoeltoegankelijk'), CategoryDomain::facility()),
        ];

        $otherFacilities = [
            new Category(new CategoryID('3.34.0.0.0'), new CategoryLabel('Vereenvoudigde informatie'), CategoryDomain::facility()),
            new Category(new CategoryID('3.38.0.0.0'), new CategoryLabel('Inter-assistentie'), CategoryDomain::facility()),
        ];

        $moreFacilities = [
            new Category(new CategoryID('3.34.0.0.0'), new CategoryLabel('Vereenvoudigde informatie'), CategoryDomain::facility()),
            new Category(new CategoryID('3.38.0.0.0'), new CategoryLabel('Inter-assistentie'), CategoryDomain::facility()),
            new Category(new CategoryID('3.40.0.0.0'), new CategoryLabel('Inter-events'), CategoryDomain::facility()),
        ];

        $lessFacilities = [
            new Category(new CategoryID('3.34.0.0.0'), new CategoryLabel('Vereenvoudigde informatie'), CategoryDomain::facility()),
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
                ): void {
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
    public function it_has_an_initial_null_state_for_facilities(): void
    {
        $itemId = '0fd9a1e5-1406-43b5-a641-4b4d77fe980d';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(fn (Item $item) => $item->updateFacilities([]))
            ->then([new FacilitiesUpdated($itemId, [])]);
    }

    /**
     * @test
     */
    public function it_ignores_removing_facilities_when_facilities_already_empty(): void
    {
        $itemId = '0fd9a1e5-1406-43b5-a641-4b4d77fe980d';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new FacilitiesUpdated($itemId, []),
            ])
            ->when(fn (Item $item) => $item->updateFacilities([]))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_updates_contact_point_when_changed(): void
    {
        $itemId = 'c25e603a-19dd-48e4-94d9-893484402189';

        $contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016/101010')),
            new EmailAddresses(
                new EmailAddress('test@2dotstwice.be'),
                new EmailAddress('admin@2dotstwice.be')
            ),
            new Urls(new Url('http://www.2dotstwice.be'))
        );

        $sameContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016/101010')),
            new EmailAddresses(
                new EmailAddress('test@2dotstwice.be'),
                new EmailAddress('admin@2dotstwice.be')
            ),
            new Urls(new Url('http://www.2dotstwice.be'))
        );

        $otherContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('02/101010')),
            new EmailAddresses(
                new EmailAddress('admin@public.b'),
                new EmailAddress('test@public.be')
            ),
            new Urls(new Url('http://www.publiq.be'))
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
                ): void {
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
    public function it_updates_typical_age_range_when_changed(): void
    {
        $itemId = '8f196b43-ece1-46ca-bfe7-bda7e60a5ea1';

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
                ): void {
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
    public function it_should_remember_added_labels(): void
    {
        $itemId = 'abfbef16-4546-4164-b5d3-165658d5053c';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item): void {
                    $item->addLabel(new Label(new LabelName('purple')));
                    $item->addLabel(new Label(new LabelName('orange')));
                    $item->addLabel(new Label(new LabelName('green')));

                    $item->addLabel(new Label(new LabelName('purple')));
                    $item->addLabel(new Label(new LabelName('orange')));
                    $item->addLabel(new Label(new LabelName('green')));
                }
            )
            ->then([
                new LabelAdded($itemId, 'purple'),
                new LabelAdded($itemId, 'orange'),
                new LabelAdded($itemId, 'green'),
            ]);
    }

    /**
     * @test
     */
    public function it_should_remember_which_labels_were_removed(): void
    {
        $itemId = '60257f64-46b3-4653-8599-e41487174744';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(
                function (Item $item): void {
                    $item->addLabel(new Label(new LabelName('purple')));
                    $item->addLabel(new Label(new LabelName('orange')));
                    $item->addLabel(new Label(new LabelName('green')));

                    $item->removeLabel('purple');
                    $item->addLabel(new Label(new LabelName('purple')));
                }
            )
            ->then([
                new LabelAdded($itemId, 'purple'),
                new LabelAdded($itemId, 'orange'),
                new LabelAdded($itemId, 'green'),
                new LabelRemoved($itemId, 'purple'),
                new LabelAdded($itemId, 'purple'),
            ]);
    }

    /**
     * @test
     */
    public function it_should_be_able_to_remove_invalid_labels(): void
    {
        $itemId = '60257f64-46b3-4653-8599-e41487174744';

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new LabelAdded($itemId, 'invalid;label', true),
                new LabelAdded($itemId, "newline\r\nlabel", false),
            ])
            ->when(
                function (Item $item): void {
                    $item->removeLabel('invalid;label');
                    $item->removeLabel("newline\r\nlabel");
                }
            )
            ->then([
                new LabelRemoved($itemId, 'invalid;label'),
                new LabelRemoved($itemId, "newline\r\nlabel"),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_import_labels(): void
    {
        $itemId = '9538e4b6-2b8c-404c-93dc-e0dccf8eb175';

        $labels = new Labels(
            new Label(
                new LabelName('new_label_1'),
                true
            ),
            new Label(
                new LabelName('existing_label_1_added_via_ui_and_also_in_new_import'),
                true
            ),
            new Label(
                new LabelName('existing_label_3_added_via_import_and_also_in_new_import'),
                true
            )
        );

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new LabelAdded($itemId, 'existing_label_1_added_via_ui_and_also_in_new_import'),
                new LabelAdded($itemId, 'existing_label_2_added_via_ui'),
                new LabelsImported(
                    $itemId,
                    [
                        'existing_label_3_added_via_import_and_also_in_new_import',
                        'existing_label_4_added_via_import',
                    ],
                    []
                ),
                new LabelAdded($itemId, 'existing_label_3_added_via_import_and_also_in_new_import'),
                new LabelAdded($itemId, 'existing_label_4_added_via_import'),
            ])
            ->when(
                function (Item $item) use ($labels): void {
                    $item->importLabels($labels);
                }
            )
            ->then([
                new LabelsImported(
                    $itemId,
                    ['new_label_1'],
                    []
                ),
                new LabelRemoved($itemId, 'existing_label_4_added_via_import'),
                new LabelAdded($itemId, 'new_label_1'),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_replace_labels(): void
    {
        $itemId = '9538e4b6-2b8c-404c-93dc-e0dccf8eb175';

        $labels = new Labels(
            new Label(
                new LabelName('new_label_1'),
                true
            ),
            new Label(
                new LabelName('existing_label_1_added_via_ui_and_also_added_in_new_replace_command'),
                true
            ),
        );

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new LabelAdded($itemId, 'existing_label_1_added_via_ui_and_also_added_in_new_replace_command'),
                new LabelAdded($itemId, 'existing_label_2_added_via_ui'),
                new LabelsReplaced(
                    $itemId,
                    [
                        'existing_label_3_added_via_replace_and_also_in_new_replace',
                        'existing_label_4_added_via_replace',
                    ],
                    []
                ),
                new LabelAdded($itemId, 'existing_label_3_added_via_replace'),
            ])
            ->when(
                function (Item $item) use ($labels): void {
                    $item->replaceLabels($labels);
                    $item->replaceLabels($labels);
                }
            )
            ->then([
                new LabelsReplaced(
                    $itemId,
                    ['new_label_1'],
                    []
                ),
                new LabelRemoved($itemId, 'existing_label_2_added_via_ui'),
                new LabelRemoved($itemId, 'existing_label_3_added_via_replace'),
                new LabelAdded($itemId, 'new_label_1'),
                new LabelsReplaced(
                    $itemId,
                    [],
                    []
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_selecting_an_unknown_main_image(): void
    {
        $this->expectException(ImageMustBeLinkedException::class);
        $this->offer->selectMainImage($this->image);
    }

    /**
     * @test
     */
    public function it_should_set_the_main_image_when_selecting_another_one(): void
    {
        $anotherImage = new Image(
            new Uuid('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/jpeg'),
            new MediaDescription('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_best_selfie.gif'),
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
                function (Item $item) use ($image, $anotherImage): void {
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
            new Uuid('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/jpeg'),
            new MediaDescription('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_best_selfie.gif'),
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
                function (Item $item) use ($anotherImage): void {
                    $item->addImage($this->image);
                    $item->addImage($anotherImage);
                    $item->updateImage(
                        $anotherImage->getMediaObjectId(),
                        new MediaDescription('new description'),
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
                        $anotherImage->getMediaObjectId()->toString(),
                        'new description',
                        'new copyright holder'
                    ),
                    new MainImageSelected('someId', $anotherImage),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_presence_of_image_when_updating(): void
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item): void {
                    $item->addImage($this->image);
                    $item->removeImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new MediaDescription('my favorite cat'),
                        new CopyrightHolder('Jane Doe')
                    );
                    $item->addImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new MediaDescription('my favorite cat'),
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
                        $this->image->getMediaObjectId()->toString(),
                        'my favorite cat',
                        'Jane Doe'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_difference_of_image_when_updating(): void
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item): void {
                    $item->addImage($this->image);
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        $this->image->getDescription(),
                        $this->image->getCopyrightHolder()
                    );
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new MediaDescription('other description'),
                        $this->image->getCopyrightHolder()
                    );
                    $item->updateImage(
                        $this->image->getMediaObjectId(),
                        new MediaDescription('other description'),
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
                        $this->image->getMediaObjectId()->toString(),
                        'other description',
                        $this->image->getCopyrightHolder()->toString()
                    ),
                    new ImageUpdated(
                        'someId',
                        $this->image->getMediaObjectId()->toString(),
                        'other description',
                        'other copyright'
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_checks_for_presence_when_adding_image(): void
    {
        $this->scenario
            ->withAggregateId('someId')
            ->given(
                [
                    new ItemCreated('someId'),
                ]
            )
            ->when(
                function (Item $item): void {
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
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image(): void
    {
        $oldestImage = new Image(
            new Uuid('798b4619-07c4-456d-acca-8f3f3e6fd43f'),
            new MIMEType('image/gif'),
            new MediaDescription('my best selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_best_selfie.gif'),
            new Language('en')
        );
        $newerImage = new Image(
            new Uuid('fdfac613-61f9-43ac-b1a9-c75f9fd58386'),
            new MIMEType('image/jpeg'),
            new MediaDescription('pic'),
            new CopyrightHolder('Henk'),
            new Url('http://foo.bar/media/pic.jpeg'),
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
                function (Item $item) use ($originalMainImage, $oldestImage, $newerImage): void {
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
    public function it_should_make_an_image_main_when_added_to_an_item_without_existing_ones(): void
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
                function (Item $item) use ($firstImage): void {
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
    public function it_should_not_trigger_a_main_image_selected_event_when_the_image_is_already_selected_as_main(): void
    {
        $originalMainImage = $this->image;
        $newMainImage = new Image(
            new Uuid('fdfac613-61f9-43ac-b1a9-c75f9fd58386'),
            new MIMEType('image/jpeg'),
            new MediaDescription('pic'),
            new CopyrightHolder('Henk'),
            new Url('http://foo.bar/media/pic.jpeg'),
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
                function (Item $item) use ($originalMainImage, $newMainImage): void {
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
    public function it_handles_a_video_add(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(function (Item $item) use ($video): void {
                $item->addVideo($video);
            })
            ->then(
                [
                    new VideoAdded(
                        $itemId,
                        $video
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_multiple_video_adds(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video1 = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $video2 = (new Video(
            '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
            new Url('https://vimeo.com/98765432'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Public Domain'));

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(function (Item $item) use ($video1, $video2): void {
                $item->addVideo($video1);
                $item->addVideo($video2);
            })
            ->then(
                [
                    new VideoAdded(
                        $itemId,
                        $video1
                    ),
                    new VideoAdded(
                        $itemId,
                        $video2
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_prevents_adding_an_identical_video(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video1 = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $video2 = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://vimeo.com/98765432'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Public Domain'));

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(function (Item $item) use ($video1, $video2): void {
                $item->addVideo($video1);
                $item->addVideo($video2);
            })
            ->then(
                [
                    new VideoAdded(
                        $itemId,
                        $video1
                    ),
                ]
            );
    }

    /**
     * @dataProvider updateVideoDataProvider
     * @test
     */
    public function it_handles_updating_a_video(?Url $url, ?Language $language, ?CopyrightHolder $copyrightHolder): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video1 = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('madewithlove'));

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                ]
            )
            ->when(function (Item $item) use ($url, $language, $copyrightHolder): void {
                $item->updateVideo(
                    '91c75325-3830-4000-b580-5778b2de4548',
                    $url,
                    $language,
                    $copyrightHolder
                );
            })
            ->then([
                new VideoUpdated(
                    $itemId,
                    (new Video(
                        '91c75325-3830-4000-b580-5778b2de4548',
                        $url ?? new Url('https://www.youtube.com/watch?v=123'),
                        $language ?? new Language('nl'),
                    ))->withCopyrightHolder($copyrightHolder ?? new CopyrightHolder('madewithlove'))
                ),
            ]);
    }

    public function updateVideoDataProvider(): array
    {
        return [
            'Update url' => [
                new Url('https://www.vimeo.com/123'),
                null,
                null,
            ],
            'Update language' => [
                null,
                new Language('fr'),
                null,
            ],
            'Update copyright holder' => [
                null,
                null,
                new CopyrightHolder('publiq'),
            ],
            'Update url and copyright holder' => [
                new Url('https://www.vimeo.com/123'),
                null,
                new CopyrightHolder('publiq'),
            ],
            'Update url and language' => [
                new Url('https://www.vimeo.com/123'),
                new Language('fr'),
                null,
            ],
            'Update copyright holder and language' => [
                null,
                new Language('fr'),
                new CopyrightHolder('publiq'),
            ],
            'Update all properties' => [
                new Url('https://www.vimeo.com/123'),
                new Language('fr'),
                new CopyrightHolder('publiq'),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_update_a_video_when_none_are_present(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(function (Item $item): void {
                $item->updateVideo(
                    '65d29008-a8da-4479-863c-beba35ec7412',
                    null,
                    new Language('fr'),
                    null
                );
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_a_video_with_an_unknown_id(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video1 = new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                ]
            )
            ->when(function (Item $item): void {
                $item->updateVideo(
                    '65d29008-a8da-4479-863c-beba35ec7412',
                    null,
                    new Language('fr'),
                    null
                );
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_a_video_when_no_changes_are_given(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $video1 = new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        $this->scenario
            ->given(
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                ]
            )
            ->when(function (Item $item): void {
                $item->updateVideo(
                    '91c75325-3830-4000-b580-5778b2de4548',
                    null,
                    null,
                    null
                );
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_video(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $videoId = '91c75325-3830-4000-b580-5778b2de4548';

        $video = (new Video(
            $videoId,
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new VideoAdded($itemId, $video),
            ])
            ->when(fn (Item $item) => $item->deleteVideo($videoId))
            ->then([
                new VideoDeleted($itemId, $videoId),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_delete_a_video_twice(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $videoId = '91c75325-3830-4000-b580-5778b2de4548';

        $video = (new Video(
            $videoId,
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new VideoAdded($itemId, $video),
                new VideoDeleted($itemId, $videoId),
            ])
            ->when(fn (Item $item) => $item->deleteVideo($videoId))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_deleting_an_unknown_video(): void
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $videoId = '91c75325-3830-4000-b580-5778b2de4548';
        $unknownVideoId = 'b7857d2e-121c-4e1c-a04b-eba755f89289';

        $video = (new Video(
            $videoId,
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new VideoAdded($itemId, $video),
            ])
            ->when(fn (Item $item) => $item->deleteVideo($unknownVideoId))
            ->then([]);
    }

    /**
     * @dataProvider importVideosDataProvider
     * @test
     */
    public function it_can_import_videos(array $given, VideoCollection $videoCollection, array $then): void
    {
        $this->scenario
            ->given($given)
            ->when(fn (Item $item) => $item->importVideos($videoCollection))
            ->then($then);
    }

    public function importVideosDataProvider(): array
    {
        $itemId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $video1 = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $video2 = (new Video(
            'bcd9fcf8-c1dc-4a2e-87cc-1a61b394234a',
            new Url('https://www.vimeo.com/123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Minds'));

        $video3 = (new Video(
            '33c44698-1d99-4e72-b41a-6f3b61bcafca',
            new Url('https://www.vimeo.com/abc'),
            new Language('fr')
        ))->withCopyrightHolder(new CopyrightHolder('Minds from publiq'));

        return [
            'import videos on an event without any videos' => [
                [
                    new ItemCreated($itemId),
                ],
                new VideoCollection($video1),
                [
                    new VideoAdded($itemId, $video1),
                ],
            ],
            'import videos resulting in adding one new video' => [
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                ],
                new VideoCollection($video1, $video2),
                [
                    new VideoAdded($itemId, $video2),
                ],
            ],
            'import videos resulting in deleting one video' => [
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                    new VideoAdded($itemId, $video2),
                ],
                new VideoCollection($video2),
                [
                    new VideoDeleted($itemId, $video1->getId()),
                ],
            ],
            'import videos resulting in updating one video' => [
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                    new VideoAdded($itemId, $video2),
                ],
                new VideoCollection(
                    $video1,
                    $video2->withCopyrightHolder(new CopyrightHolder('changed copyright'))
                ),
                [
                    new VideoUpdated(
                        $itemId,
                        $video2->withCopyrightHolder(new CopyrightHolder('changed copyright'))
                    ),
                ],
            ],
            'import videos resulting in a mix' => [
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                    new VideoAdded($itemId, $video2),
                ],
                new VideoCollection(
                    $video2->withCopyrightHolder(new CopyrightHolder('changed copyright')),
                    $video3
                ),
                [
                    new VideoAdded($itemId, $video3),
                    new VideoDeleted($itemId, $video1->getId()),
                    new VideoUpdated(
                        $itemId,
                        $video2->withCopyrightHolder(new CopyrightHolder('changed copyright'))
                    ),
                ],
            ],
            'import zero videos results in deleting all existing videos' => [
                [
                    new ItemCreated($itemId),
                    new VideoAdded($itemId, $video1),
                    new VideoAdded($itemId, $video2),
                ],
                new VideoCollection(),
                [
                    new VideoDeleted($itemId, $video1->getId()),
                    new VideoDeleted($itemId, $video2->getId()),
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_publishes_an_offer_with_workflow_status_draft(): void
    {
        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($now): void {
                $item->publish($now);
            })
            ->then([
                new Published($itemId, $now),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_publish_an_offer_more_then_once(): void
    {
        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new Published($itemId, $now),
            ])
            ->when(function (Item $item) use ($now): void {
                $item->publish($now);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_when_trying_to_publish_a_non_draft_offer(): void
    {
        $this->expectException(InvalidWorkflowStatusTransition::class);
        $this->expectExceptionMessage('Cannot transition from workflowStatus "REJECTED" to "READY_FOR_VALIDATION".');

        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new Published($itemId, $now),
                new FlaggedAsDuplicate($itemId),
            ])
            ->when(function (Item $item) use ($now): void {
                $item->publish($now);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_throw_when_publishing_an_offer_that_is_already_published_and_approved(): void
    {
        $itemId = 'itemId';
        $now = new \DateTime();

        $this->scenario
            ->given([
                new ItemCreated($itemId),
                new Published($itemId, $now),
                new Approved($itemId),
            ])
            ->when(function (Item $item) use ($now): void {
                $item->publish($now);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_approve_an_offer_that_is_ready_for_validation(): void
    {
        $itemId = '23bb131f-d060-4e00-86c7-cf8af0de1190';
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
                function (Item $item): void {
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
    public function it_should_not_approve_an_offer_more_than_once(): void
    {
        $itemId = 'beb53a50-c683-48ae-ab8a-97070063516d';
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
                function (Item $item): void {
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
     */
    public function it_should_not_approve_an_offer_after_it_was_rejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You can not approve an offer that is not ready for validation');
        $itemId = '4b5f30bf-a612-4cb9-bba0-4a77a4385a73';
        $reason = 'There are spelling mistakes in the description.';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Rejected($itemId, $reason),
                ]
            )
            ->when(
                function (Item $item): void {
                    $item->approve();
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_reject_an_offer_more_than_once_for_the_same_reason(): void
    {
        $itemId = '04bf2962-2d7c-4da9-8e01-8a8fa249e70c';
        $reason = 'The title is misleading.';
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
                function (Item $item) use ($reason): void {
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
     */
    public function it_should_not_reject_an_offer_that_is_already_rejected_for_a_different_reason(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The offer has already been rejected for another reason: The title is misleading.');
        $itemId = '1cb18f8c-5be4-4301-9761-dea2bbfa9a1f';
        $reason = 'The title is misleading.';
        $differentReason = 'I\'m afraid I can\'t let you do that.';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Rejected($itemId, $reason),
                ]
            )
            ->when(
                function (Item $item) use ($differentReason): void {
                    $item->reject($differentReason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_reject_an_offer_that_is_ready_for_validation_with_a_reason(): void
    {
        $itemId = '0c93d516-cda2-4062-b8b3-f649cbc8086c';
        $reason = 'You forgot to add an organizer.';
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
                function (Item $item) use ($reason): void {
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
    public function it_should_flag_an_offer_that_is_ready_for_validation_as_duplicate(): void
    {
        $itemId = '7ef827a1-e30b-4dad-9dc9-0e1683afa3f6';
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
                function (Item $item): void {
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
     */
    public function it_should_reject_an_offer_when_it_is_flagged_as_duplicate(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The offer has already been rejected for another reason: duplicate');
        $itemId = '0e3a13ec-a88d-4cd5-9565-d7b00690c52f';
        $reason = 'The theme does not match the description.';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new FlaggedAsDuplicate($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason): void {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_flag_an_offer_that_is_ready_for_validation_as_inappropriate(): void
    {
        $itemId = '1dc18bb1-89e6-4ecb-90b1-4608bb58e3e2';
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
                function (Item $item): void {
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
     */
    public function it_should_not_reject_an_offer_when_it_is_flagged_as_inappropriate(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The offer has already been rejected for another reason: inappropriate');
        $itemId = '05f4b7f7-a0ed-4530-8b25-2a573fe7f305';
        $reason = 'The theme does not match the description.';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new FlaggedAsInappropriate($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason): void {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_reject_an_offer_that_is_flagged_as_approved(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You can not reject an offer that is not ready for validation');
        $itemId = '5988798b-c211-4c04-a9f4-ceb2568d93b3';
        $reason = 'Yeah, but no, but yeah...';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new Approved($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($reason): void {
                    $item->reject($reason);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_update_an_offer_with_an_organizer_when_it_is_already_set(): void
    {
        $itemId = 'b601d70e-ce4f-4484-86bb-2b8459b41e75';
        $organizerId = '12ab8c11-521e-4f2d-a575-c637a8af6292';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new OrganizerUpdated($itemId, $organizerId),
                ]
            )
            ->when(
                function (Item $item) use ($organizerId): void {
                    $item->updateOrganizer($organizerId);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_update_an_offer_with_the_same_organizer_after_removing_it(): void
    {
        $itemId = 'c835a4f2-decc-401d-a1fa-061d5b924805';
        $organizerId = '3d93ef34-88ab-4277-b1d0-1fa9e9dfe5f3';

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
                function (Item $item) use ($organizerId): void {
                    $item->updateOrganizer($organizerId);
                }
            )
            ->then([new OrganizerUpdated($itemId, $organizerId)]);
    }

    /**
     * @test
     */
    public function it_should_delete_the_current_organizer_regardless_of_the_id(): void
    {
        $itemId = '8a49a8df-544e-44c1-b888-dd2a1fde8416';
        $organizerId = '0e0dfc0f-073c-4956-9352-06bbf8266e69';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new OrganizerUpdated($itemId, $organizerId),
                ]
            )
            ->when(
                function (Item $item): void {
                    $item->deleteCurrentOrganizer();
                }
            )
            ->then([new OrganizerDeleted($itemId, $organizerId)]);
    }

    /**
     * @test
     */
    public function it_should_not_delete_the_current_organizer_if_there_is_none(): void
    {
        $itemId = 'a9274e23-4207-47d7-a2c2-ba0ef8d65eb0';

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(
                function (Item $item): void {
                    $item->deleteCurrentOrganizer();
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_title_update_that_does_not_change_the_existing_title(): void
    {
        $itemId = '90df24ec-5a6c-4eb5-9321-2bf7855041d9';
        $title = new Title('Titel');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, $title->toString()),
                ]
            )
            ->when(
                function (Item $item) use ($title): void {
                    $item->updateTitle(new Language('nl'), $title);
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_translate_the_title_when_updating_with_a_foreign_language(): void
    {
        $itemId = '36cecb1d-3482-4593-8195-83ae32ef4e5e';
        $title = new Title('The Title');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, 'Een titel'),
                ]
            )
            ->when(
                function (Item $item) use ($title, $language): void {
                    $item->updateTitle($language, $title);
                }
            )
            ->then([
                new TitleTranslated($itemId, $language, $title->toString()),
            ]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_title_translation_that_does_not_translate_the_title(): void
    {
        $itemId = 'a7f81946-2479-462c-93e1-43cbae959f79';
        $title = new Title('The Title');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new TitleUpdated($itemId, 'Een titel'),
                ]
            )
            ->when(
                function (Item $item) use ($title, $language): void {
                    $item->updateTitle($language, $title);
                    $item->updateTitle($language, $title);
                    $item->updateTitle(new Language('nl'), new Title('Een titel'));
                }
            )
            ->then([
                new TitleTranslated($itemId, $language, $title->toString()),
            ]);
    }

    /**
     * @test
     */
    public function it_should_ignore_a_description_update_that_does_not_change_the_existing_descriptions(): void
    {
        $itemId = '169f0526-8754-4791-a33b-7a13275881b9';
        $description = new Description('Een beschrijving');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new DescriptionUpdated($itemId, $description),
                ]
            )
            ->when(
                function (Item $item) use ($description): void {
                    $item->updateDescription($description, new Language('nl'));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_translate_the_description_when_updating_with_a_foreign_language(): void
    {
        $itemId = '81598b26-68f3-424c-85e0-29293fd92723';
        $description = new Description('La description');
        $language = new Language('fr');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                    new DescriptionUpdated($itemId, new Description('Een beschrijving')),
                ]
            )
            ->when(
                function (Item $item) use ($description, $language): void {
                    $item->updateDescription($description, $language);
                }
            )
            ->then([
                new DescriptionTranslated($itemId, $language, $description),
            ]);
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     * Test for only 1 language
     */
    public function it_does_delete_the_description_for_the_main_language(): void
    {
        $itemId = '81598b26-68f3-424c-85e0-29293fd92723';
        $language = new Language('nl');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId, $language),
                    new DescriptionUpdated('my-id', new Description('test')),
                ]
            )
            ->when(
                fn (Item $item) => $item->deleteDescription($language)
            )
            ->then([
                new DescriptionDeleted($itemId, $language),
            ]);
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     * Test for 2 languages
     */
    public function it_does_delete_the_description_for_the_different_language(): void
    {
        $itemId = '81598b26-68f3-424c-85e0-29293fd92723';
        $language = new Language('nl');
        $differentLanguage = new Language('fr');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId, $language),
                    new DescriptionUpdated('my-id', new Description('test')),
                    new DescriptionTranslated(
                        'my-id',
                        $differentLanguage,
                        new Description('test')
                    ),
                ]
            )
            ->when(
                fn (Item $item) => $item->deleteDescription($differentLanguage)
            )
            ->then([
                new DescriptionDeleted($itemId, $differentLanguage),
            ]);
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     */
    public function it_does_not_delete_the_description_twice(): void
    {
        $itemId = '81598b26-68f3-424c-85e0-29293fd92723';
        $language = new Language('nl');
        $differentLanguage = new Language('fr');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId, $language),
                    new DescriptionUpdated('my-id', new Description('test')),
                    new DescriptionTranslated(
                        'my-id',
                        $differentLanguage,
                        new Description('test')
                    ),
                    new DescriptionDeleted($itemId, $differentLanguage),
                ]
            )
            ->when(
                fn (Item $item) => $item->deleteDescription($differentLanguage)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_booking_info_updated_events(): void
    {
        $itemId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $bookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://www.publiq.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('publiq')
                )
            ),
            new TelephoneNumber('02 123 45 67'),
            new EmailAddress('info@publiq.be')
        );

        $sameBookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://www.publiq.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('publiq')
                )
            ),
            new TelephoneNumber('02 123 45 67'),
            new EmailAddress('info@publiq.be')
        );

        $otherBookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://www.2dotstwice.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('2dotstwice')
                )
            ),
            new TelephoneNumber('016 12 34 56'),
            new EmailAddress('info@2dotstwice.be')
        );

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    new ItemCreated($itemId),
                ]
            )
            ->when(
                function (Item $item) use ($bookingInfo, $sameBookingInfo, $otherBookingInfo): void {
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
     */
    public function it_should_import_images_from_udb2_as_media_object_and_main_image(
        Image $image,
        ImageCollection $imageCollection
    ): void {
        $itemId = '34b9c25a-3b26-446f-9292-c42030199992';

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $imageCollection): void {
                $item->importImagesFromUDB2($imageCollection);
                $item->addImage($image);
                $item->selectMainImage($image);
            })
            ->then([new ImagesImportedFromUDB2($itemId, $imageCollection)]);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     */
    public function it_should_keep_images_translated_in_ubd3_when_updating_images_from_udb2(
        Image $image
    ): void {
        $itemId = 'ca7a178e-24cb-4d66-b4e6-a575b25d531f';

        $dutchUdb3Image = new Image(
            new Uuid('0773EB2A-54BE-49AD-B261-5D1099F319D4'),
            new MIMEType('image/jpg'),
            new MediaDescription('mijn favoriete wallpaper'),
            new CopyrightHolder('Dirk Dirkingn'),
            new Url('http://foo.bar/media/mijn_favoriete_wallpaper_<3.jpg'),
            new Language('nl')
        );

        $udb2Images = ImageCollection::fromArray([
            new Image(
                new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
                new MIMEType('image/jpg'),
                new MediaDescription('episch panorama'),
                new CopyrightHolder('Dirk Dirkingn'),
                new Url('http://foo.bar/media/episch_panorama.jpg'),
                new Language('nl')
            ),
        ]);

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $dutchUdb3Image, $udb2Images): void {
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
     */
    public function it_should_update_images_from_udb2_as_media_object_and_main_image(
        Image $image,
        ImageCollection $imageCollection
    ): void {
        $itemId = '33967f3b-88cd-4ec4-85ed-dde3c329a3c8';

        $this->scenario
            ->withAggregateId($itemId)
            ->given([
                new ItemCreated($itemId),
            ])
            ->when(function (Item $item) use ($image, $imageCollection): void {
                $item->UpdateImagesFromUDB2($imageCollection);
                $item->addImage($image);
                $item->selectMainImage($image);
            })
            ->then([new ImagesUpdatedFromUDB2($itemId, $imageCollection)]);
    }

    public function imageCollectionDataProvider(): array
    {
        $image = new Image(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new MediaDescription('my pic'),
            new CopyrightHolder('Dirk Dirkingn'),
            new Url('http://foo.bar/media/my_pic.jpg'),
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
