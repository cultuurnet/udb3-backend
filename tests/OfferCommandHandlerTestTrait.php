<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Repository\Repository;
use Broadway\CommandHandling\Testing\Scenario;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;

/**
 * Provides a trait to test commands that are applicable for all UDB3 offer types
 * @property Scenario $scenario
 */
trait OfferCommandHandlerTestTrait
{
    /**
     * @var Repository&MockObject
     */
    protected $organizerRepository;

    /**
     * @var MediaManagerInterface&MockObject
     */
    protected $mediaManager;

    private function getCommandClass(string $className): string
    {
        $reflection = new ReflectionObject($this);
        return $reflection->getNamespaceName() . '\\Commands\\' . $className;
    }

    private function getEventClass(string $className): string
    {
        $reflection = new ReflectionObject($this);
        return $reflection->getNamespaceName() . '\\Events\\' . $className;
    }

    /**
     * @test
     */
    public function it_can_update_booking_info_of_an_offer(): void
    {
        $id = '1';
        $bookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://www.publiq.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('publiq')
                )
            ),
        );
        $commandClass = $this->getCommandClass('UpdateBookingInfo');
        $eventClass = $this->getEventClass('BookingInfoUpdated');

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new $commandClass($id, $bookingInfo)
            )
            ->then([new $eventClass($id, $bookingInfo)]);
    }

    /**
     * @test
     */
    public function it_can_update_contact_point_of_an_offer(): void
    {
        $id = '1';
        $contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016102030')),
            null,
            null
        );
        $commandClass = $this->getCommandClass('UpdateContactPoint');
        $eventClass = $this->getEventClass('ContactPointUpdated');

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new $commandClass($id, $contactPoint)
            )
            ->then([new $eventClass($id, $contactPoint)]);
    }

    /**
     * @test
     */
    public function it_can_update_description_of_an_offer(): void
    {
        $id = '1';
        $description = new Description('foo');
        $commandClass = $this->getCommandClass('UpdateDescription');
        $eventClass = $this->getEventClass('DescriptionUpdated');

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new $commandClass($id, new Language('nl'), $description)
            )
            ->then([new $eventClass($id, $description)]);
    }

    /**
     * @test
     */
    public function it_can_add_an_image_to_an_offer(): void
    {
        $id = '1';
        $imageId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $image = new Image(
            $imageId,
            new MIMEType('image/png'),
            new MediaDescription('Some description.'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $commandClass = $this->getCommandClass('AddImage');
        $eventClass = $this->getEventClass('ImageAdded');

        $this->mediaManager->expects($this->once())
            ->method('getImage')
            ->with($imageId)
            ->willReturn($image);

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new $commandClass($id, $imageId)
            )
            ->then([new $eventClass($id, $image)]);
    }

    /**
     * @test
     */
    public function it_can_remove_an_image_from_an_offer(): void
    {
        $id = '1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new MediaDescription('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $imageAddedEventClass = $this->getEventClass('ImageAdded');
        $commandClass = $this->getCommandClass('RemoveImage');
        $eventClass = $this->getEventClass('ImageRemoved');

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                    new $imageAddedEventClass($id, $image),
                ]
            )
            ->when(
                new $commandClass($id, $image)
            )
            ->then([new $eventClass($id, $image)]);
    }

    /**
     * @test
     */
    public function it_can_update_an_image_of_an_offer(): void
    {
        $itemId = '1';
        $mediaObjectId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $description = 'A description.';
        $copyrightHolder = new CopyrightHolder('Dirk');
        $imageAdded = $this->getEventClass('ImageAdded');
        $commandClass = $this->getCommandClass('UpdateImage');
        $eventClass = $this->getEventClass('ImageUpdated');

        $this->scenario
            ->withAggregateId($itemId)
            ->given(
                [
                    $this->factorOfferCreated($itemId),
                    new $imageAdded(
                        $itemId,
                        new Image(
                            $mediaObjectId,
                            new MIMEType('image/jpeg'),
                            new MediaDescription('my best selfie'),
                            new CopyrightHolder('Dirk Dirkington'),
                            new Url('http://foo.bar/media/my_best_selfie.gif'),
                            new Language('en')
                        )
                    ),
                ]
            )
            ->when(
                new $commandClass(
                    $itemId,
                    $mediaObjectId,
                    $description,
                    $copyrightHolder
                )
            )
            ->then([
                new $eventClass(
                    $itemId,
                    $mediaObjectId->toString(),
                    $description,
                    $copyrightHolder->toString()
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_can_import_images(): void
    {
        $itemId = '1';

        $imageAdded = $this->getEventClass('ImageAdded');
        $imageUpdated = $this->getEventClass('ImageUpdated');
        $imageRemoved = $this->getEventClass('ImageRemoved');
        $mainImageSelected = $this->getEventClass('MainImageSelected');

        $addedEvent = function (Image $image) use ($itemId, $imageAdded) {
            return new $imageAdded($itemId, $image);
        };

        $updatedEvent = function (Image $image) use ($itemId, $imageUpdated) {
            return new $imageUpdated(
                $itemId,
                $image->getMediaObjectId()->toString(),
                $image->getDescription()->toString(),
                $image->getCopyrightHolder()->toString(),
                $image->getLanguage()->getCode()
            );
        };

        $removedEvent = function (Image $image) use ($itemId, $imageRemoved) {
            return new $imageRemoved($itemId, $image);
        };

        $initialImages = [
            new Image(
                new UUID('b0939d37-68f7-4c55-bfa3-fabdbb46154e'),
                new MIMEType('image/jpeg'),
                new MediaDescription('my best selfie'),
                new CopyrightHolder('Dirk Dirkington'),
                new Url('http://foo.bar/media/my_best_selfie.jpg'),
                new Language('en')
            ),
            new Image(
                new UUID('0de3d4a4-abaa-4685-896a-34cfa70f3cd0'),
                new MIMEType('image/png'),
                new MediaDescription('mijn beste selfie'),
                new CopyrightHolder('Dirk Dirkington'),
                new Url('http://foo.bar/media/mijn_beste_selfie.png'),
                new Language('nl')
            ),
            new Image(
                new UUID('0aee1053-bc6c-43bc-9743-20656b579167'),
                new MIMEType('image/jpeg'),
                new MediaDescription('my second best selfie'),
                new CopyrightHolder('Dirk Dirkington'),
                new Url('http://foo.bar/media/my_second_best_selfie.jpg'),
                new Language('en')
            ),
            new Image(
                new UUID('7e1d0180-97cc-4fbf-a133-c691a07a5a04'),
                new MIMEType('image/gif'),
                new MediaDescription('mijn tweede beste selfie'),
                new CopyrightHolder('Dirk Dirkington'),
                new Url('http://foo.bar/media/mijn_tweede_beste_selfie.gif'),
                new Language('nl')
            ),
        ];

        $importImages = [
            new Image(
                new UUID('4a66a43a-83e5-4d87-a28d-c16508140fd7'),
                new MIMEType('image/jpeg'),
                new MediaDescription('new selfie'),
                new CopyrightHolder('Dirk Dirkington'),
                new Url('http://foo.bar/media/new_selfie.jpg'),
                new Language('en')
            ),
            new Image(
                new UUID('b0939d37-68f7-4c55-bfa3-fabdbb46154e'),
                new MIMEType('image/jpeg'),
                new MediaDescription('my best selfie UPDATED'),
                new CopyrightHolder('Dirk Dirkington UPDATED'),
                new Url('http://foo.bar/media/my_best_selfie.jpg'),
                new Language('en')
            ),
            new Image(
                new UUID('0de3d4a4-abaa-4685-896a-34cfa70f3cd0'),
                new MIMEType('image/png'),
                new MediaDescription('mijn beste selfie UPDATED'),
                new CopyrightHolder('Dirk Dirkington UPDATED'),
                new Url('http://foo.bar/media/mijn_beste_selfie.png'),
                new Language('nl')
            ),
        ];

        $expectedAddedImages = [
            $importImages[0],
        ];

        $expectedUpdatedImages = [
            $importImages[1],
            $importImages[2],
        ];

        $expectedRemovedImages = [
            $initialImages[2],
            $initialImages[3],
        ];

        $expectedMainImage = $importImages[0];

        $initialEvents = array_map($addedEvent, $initialImages);
        array_unshift($initialEvents, $this->factorOfferCreated($itemId));

        $expectedAddedEvents = array_map($addedEvent, $expectedAddedImages);
        $expectedUpdatedEvents = array_map($updatedEvent, $expectedUpdatedImages);
        $expectedRemovedEvents = array_map($removedEvent, $expectedRemovedImages);
        $expectedMainImageEvent = new $mainImageSelected($itemId, $expectedMainImage);

        $commandClass = $this->getCommandClass('ImportImages');
        $command = new $commandClass($itemId, ImageCollection::fromArray($importImages));

        $expectedEvents = array_merge(
            $expectedAddedEvents,
            $expectedUpdatedEvents,
            $expectedRemovedEvents
        );
        $expectedEvents[] = $expectedMainImageEvent;

        $this->scenario
            ->withAggregateId($itemId)
            ->given($initialEvents)
            ->when($command)
            ->then($expectedEvents);
    }

    /**
     * @test
     */
    public function it_can_update_typical_agerange_of_an_offer(): void
    {
        $id = '1';
        $ageRange = new AgeRange(null, new Age(18));
        $commandClass = $this->getCommandClass('UpdateTypicalAgeRange');
        $eventClass = $this->getEventClass('TypicalAgeRangeUpdated');

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new $commandClass($id, $ageRange)
            )
            ->then([new $eventClass($id, $ageRange)]);
    }

    /**
     * @test
     */
    public function it_can_delete_typical_agerange_of_an_offer(): void
    {
        $id = '1';
        $commandClass = $this->getCommandClass('DeleteTypicalAgeRange');
        $eventClass = $this->getEventClass('TypicalAgeRangeDeleted');


        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                    new TypicalAgeRangeUpdated(
                        $id,
                        new AgeRange(
                            new Age(8),
                            new Age(11)
                        )
                    ),
                ]
            )
            ->when(
                new $commandClass($id)
            )
            ->then([new $eventClass($id)]);
    }
}
