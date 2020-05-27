<?php

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\OfferCommandHandlerTestTrait;
use CultuurNet\UDB3\Place\Commands\AddLabel;
use CultuurNet\UDB3\Place\Commands\CreatePlace;
use CultuurNet\UDB3\Place\Commands\ImportLabels;
use CultuurNet\UDB3\Place\Commands\RemoveLabel;
use CultuurNet\UDB3\Place\Commands\DeletePlace;
use CultuurNet\UDB3\Place\Commands\PlaceCommandFactory;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateCalendar;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceHandlerTest extends CommandHandlerScenarioTestCase
{
    use OfferCommandHandlerTestTrait;

    /**
     * @var PlaceCommandFactory
     */
    private $commandFactory;

    /**
     * @test
     */
    public function it_should_create_a_new_place()
    {
        $id = '1';
        $language = new Language('nl');
        $title = new Title('foo');
        $type = new EventType('0.50.4.0.0', 'jeugdhuis');
        $address = new Address(
            new Street('Kerkstraat 69'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = new Theme('0.1.0.1.0.1', 'foo');
        $publicationDate = new \DateTimeImmutable();

        $command = new CreatePlace(
            $id,
            $language,
            $title,
            $type,
            $address,
            $calendar,
            $theme,
            $publicationDate
        );

        $this->scenario
            ->withAggregateId($id)
            ->when($command)
            ->then([new PlaceCreated($id, $language, $title, $type, $address, $calendar, $theme, $publicationDate)]);
    }

    /**
     * @test
     */
    public function it_can_update_major_info_of_a_place()
    {
        $id = '1';
        $title = new Title('foo');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $address = new Address(
            new Street('Kerkstraat 69'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );
        $calendar = new Calendar(CalendarType::PERMANENT());

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new UpdateMajorInfo($id, $title, $eventType, $address, $calendar)
            )
            ->then([new MajorInfoUpdated($id, $title, $eventType, $address, $calendar)]);
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     * @param Address $updatedAddress
     */
    public function it_should_handle_an_update_address_command_for_the_main_language(
        Address $updatedAddress
    ) {
        $id = '45b9e456-f5d6-4b5c-b692-a4bb22b88332';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->factorOfferCreated($id)])
            ->when(
                new UpdateAddress($id, $updatedAddress, new Language('nl'))
            )
            ->then([new AddressUpdated($id, $updatedAddress)]);
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     * @param Address $updatedAddress
     */
    public function it_should_handle_an_update_address_command_for_any_language_other_than_the_language(
        Address $updatedAddress
    ) {
        $id = '45b9e456-f5d6-4b5c-b692-a4bb22b88332';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->factorOfferCreated($id)])
            ->when(
                new UpdateAddress($id, $updatedAddress, new Language('fr'))
            )
            ->then([new AddressTranslated($id, $updatedAddress, new Language('fr'))]);
    }

    /**
     * @return array
     */
    public function updateAddressDataProvider()
    {
        return [
            [
                'updated' => new Address(
                    new Street('Eenmeilaan 35'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo'),
                    Country::fromNative('BE')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_can_delete_places()
    {
        $id = 'event-id';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new DeletePlace($id)
            )
            ->then([new PlaceDeleted($id)]);
    }

    /**
     * @test
     */
    public function it_can_label_a_place()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(new AddLabel($id, new Label('foo')))
            ->then([new LabelAdded($id, new Label('foo'))]);
    }

    /**
     * @test
     */
    public function it_can_unlabel_a_place()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                    new LabelAdded($id, new Label('foo')),
                ]
            )
            ->when(new RemoveLabel($id, new Label('foo')))
            ->then([new LabelRemoved($id, new Label('foo'))]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_label_that_is_not_present_on_a_place()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(new RemoveLabel($id, new Label('foo')))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_label_from_a_place_that_has_been_unlabelled_already()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                    new LabelAdded($id, new Label('foo')),
                    new LabelRemoved($id, new Label('foo')),
                ]
            )
            ->when(new RemoveLabel($id, new Label('foo')))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_import_labels()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                ]
            )
            ->when(
                new ImportLabels(
                    $id,
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('foo'),
                            true
                        ),
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('bar'),
                            true
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        new Labels(
                            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                                new LabelName('foo'),
                                true
                            ),
                            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                                new LabelName('bar'),
                                true
                            )
                        )
                    ),
                    new LabelAdded($id, new Label('foo')),
                    new LabelAdded($id, new Label('bar')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_the_title_of_a_place_by_updating_with_a_foreign_language()
    {
        $id = '1';
        $title = new Title('Voorbeeld');
        $language = new Language('fr');
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                ]
            )
            ->when(new UpdateTitle($id, $language, $title))
            ->then(
                [
                    new TitleTranslated($id, $language, $title),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_the_description_by_updating_with_a_foreign_language()
    {
        $id = '1';
        $description = new Description('Lorem ipsum dolor si amet...');
        $language = new Language('fr');
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(new UpdateDescription($id, $language, $description))
            ->then([new DescriptionTranslated($id, $language, $description)]);
    }

    /**
     * @test
     */
    public function it_handles_price_info_commands()
    {
        $id = '1';

        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                ]
            )
            ->when(
                $this->commandFactory->createUpdatePriceInfoCommand($id, $priceInfo)
            )
            ->then(
                [
                    new PriceInfoUpdated($id, $priceInfo),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_calendar_of_an_event()
    {
        $placeId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $this->scenario
            ->withAggregateId($placeId)
            ->given(
                [
                    $this->factorOfferCreated($placeId),
                ]
            )
            ->when(
                new UpdateCalendar($placeId, $calendar)
            )
            ->then(
                [
                    new CalendarUpdated($placeId, $calendar),
                ]
            );
    }

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ) {
        $repository = new PlaceRepository(
            $eventStore,
            $eventBus
        );

        $this->organizerRepository = $this->createMock(RepositoryInterface::class);

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRepository->method('getByName')
            ->with(new StringLiteral('foo'))
            ->willReturn(new Entity(
                new UUID(),
                new StringLiteral('foo'),
                Visibility::VISIBLE(),
                Privacy::PRIVACY_PUBLIC()
            ));

        $this->mediaManager = $this->createMock(MediaManager::class);

        $this->commandFactory = new PlaceCommandFactory();

        return new CommandHandler(
            $repository,
            $this->organizerRepository,
            $this->labelRepository,
            $this->mediaManager
        );
    }

    private function factorOfferCreated($id)
    {
        return new PlaceCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Kerkstraat 69'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
