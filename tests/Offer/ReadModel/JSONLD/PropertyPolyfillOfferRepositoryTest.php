<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\StringLiteral;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PropertyPolyfillOfferRepositoryTest extends TestCase
{
    public const DOCUMENT_ID = '5d7ed700-17de-4c1f-923a-0affe7cf2d4c';
    private MockObject $labelReadRepository;

    private PropertyPolyfillOfferRepository $repository;

    protected function setUp(): void
    {
        $this->labelReadRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->repository = new PropertyPolyfillOfferRepository(
            new InMemoryDocumentRepository(),
            $this->labelReadRepository
        );
    }

    /**
     * @test
     */
    public function it_should_polyfill_a_mediaObject_id_based_on_the_id_url_if_not_set(): void
    {
        $this
            ->given(
                [
                    'mediaObject' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            'id' => '29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                        ],
                        'invalid',
                        [
                            '@id_missing' => true,
                        ],
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'mediaObject' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                            'id' => 'b01d92c0-5e53-4341-9625-c2264325d8c6',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            'id' => '29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                        ],
                        'invalid',
                        [
                            '@id_missing' => true,
                        ],
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_add_mediaObject_if_not_set(): void
    {
        $this
            ->given(
                []
            )
            ->assertReturnedDocumentDoesNotContainKey('mediaObject');
    }

    /**
     * @test
     */
    public function it_should_ignore_invalid_mediaObject_type(): void
    {
        $this
            ->given(
                [
                    'mediaObject' => 'invalid!',
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'mediaObject' => 'invalid!',
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_polyfill_a_default_status_if_not_set(): void
    {
        $this
            ->given([])
            ->assertReturnedDocumentContains(
                [
                    'status' => [
                        'type' => 'Available',
                    ],
                ]
            );
    }

    /**
     * @test
     * @dataProvider statusProvider
     */
    public function it_should_not_change_status_if_already_set_with_correct_format(array $status): void
    {
        $this
            ->given($status)
            ->assertReturnedDocumentContains($status);
    }

    public function statusProvider(): array
    {
        return [
            'without_reason' => [
                'status' => [
                    'type' => 'Unavailable',
                ],
            ],
            'with_reason' => [
                'status' => [
                    'type' => 'Unavailable',
                    'reason' => [
                        'nl' => 'Uitgesteld',
                        'en' => 'Postponed',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_fix_status_if_already_set_with_wrong_format(): void
    {
        $this
            ->given(['status' => 'Unavailable'])
            ->assertReturnedDocumentContains([
                'status' => [
                    'type' => 'Unavailable',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_polyfill_a_default_attendanceMode_if_not_set(): void
    {
        $this
            ->given([])
            ->assertReturnedDocumentContains(
                [
                    'attendanceMode' => AttendanceMode::offline()->toString(),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_change_existing_attendanceMode(): void
    {
        $this
            ->given([
                'attendanceMode' => AttendanceMode::mixed()->toString(),
            ])
            ->assertReturnedDocumentContains([
                'attendanceMode' => AttendanceMode::mixed()->toString(),
            ]);
    }

    /**
     * @test
     */
    public function it_should_polyfill_a_default_booking_availability_if_not_set(): void
    {
        $this
            ->given([])
            ->assertReturnedDocumentContains(
                [
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_change_booking_availability(): void
    {
        $this
            ->given([
                'bookingAvailability' => [
                    'type' => 'Unavailable',
                ],
            ])
            ->assertReturnedDocumentContains([
                'bookingAvailability' => [
                    'type' => 'Unavailable',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_polyfill_a_default_status_and_booking_availability_on_subEvent_if_not_set(): void
    {
        $this
            ->given(
                [
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-01-01T16:00:00+01:00',
                            'endDate' => '2020-01-01T20:00:00+01:00',
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-01-02T16:00:00+01:00',
                            'endDate' => '2020-01-02T20:00:00+01:00',
                            'status' => [
                                'type' => 'Unavailable',
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-01-03T16:00:00+01:00',
                            'endDate' => '2020-01-03T20:00:00+01:00',
                            'bookingAvailability' => [
                                'type' => 'Unavailable',
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-01-04T16:00:00+01:00',
                            'endDate' => '2020-01-04T20:00:00+01:00',
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Tijdelijk uitgesteld',
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2020-01-01T16:00:00+01:00',
                            'endDate' => '2020-01-01T20:00:00+01:00',
                            'status' => [
                                'type' => 'Available',
                            ],
                            'bookingAvailability' => [
                                'type' => 'Available',
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-01-02T16:00:00+01:00',
                            'endDate' => '2020-01-02T20:00:00+01:00',
                            'status' => [
                                'type' => 'Unavailable',
                            ],
                            'bookingAvailability' => [
                                'type' => 'Available',
                            ],
                        ],
                        [
                            'id' => 2,
                            '@type' => 'Event',
                            'startDate' => '2020-01-03T16:00:00+01:00',
                            'endDate' => '2020-01-03T20:00:00+01:00',
                            'status' => [
                                'type' => 'Available',
                            ],
                            'bookingAvailability' => [
                                'type' => 'Unavailable',
                            ],
                        ],
                        [
                            'id' => 3,
                            '@type' => 'Event',
                            'startDate' => '2020-01-04T16:00:00+01:00',
                            'endDate' => '2020-01-04T20:00:00+01:00',
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Tijdelijk uitgesteld',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => 'Available',
                            ],
                        ],
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_fix_status_of_embedded_location_if_already_set_with_wrong_format(): void
    {
        $this
            ->given([
                'location' => [
                    'status' => 'Unavailable',
                    'bookingAvailability' => ['type' => 'Unavailable'],
                ],
            ])
            ->assertReturnedDocumentContains([
                'location' => [
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                    'bookingAvailability' => ['type' => 'Unavailable'],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_add_default_status_of_embedded_location(): void
    {
        $this
            ->given(['location' => ['bookingAvailability' => ['type' => 'Unavailable']]])
            ->assertReturnedDocumentContains([
                'location' => [
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => ['type' => 'Unavailable'],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_not_add_default_status_of_embedded_location_if_there_is_no_location(): void
    {
        $this
            ->given(['@type' => 'Place'])
            ->assertReturnedDocumentDoesNotContainKey('location');
    }

    /**
     * @test
     */
    public function it_should_add_default_booking_availability_of_embedded_location(): void
    {
        $this
            ->given(['location' => ['status' => ['type' => 'Available']]])
            ->assertReturnedDocumentContains([
                'location' => [
                    'status' => ['type' => 'Available'],
                    'bookingAvailability' => ['type' => 'Available'],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_not_add_default_booking_availability_embedded_location_if_there_is_no_location(): void
    {
        $this
            ->given(['@type' => 'Place'])
            ->assertReturnedDocumentDoesNotContainKey('location');
    }

    /**
     * @test
     */
    public function it_should_remove_calendarSummary_if_set(): void
    {
        $this
            ->given(['calendarSummary' => 'Foo bar bla bla'])
            ->assertReturnedDocumentDoesNotContainKey('calendarSummary');
    }

    /**
     * @test
     */
    public function it_should_not_complain_if_calendarSummary_property_is_not_found(): void
    {
        $this
            ->given(['@type' => 'Event'])
            ->assertReturnedDocumentContains(['@type' => 'Event']);
    }

    /**
     * @test
     */
    public function it_should_fix_same_as(): void
    {
        $this
            ->given([
                '@id' => 'https://io.uitdatabank.dev/event/5ece8d77-48dd-402d-9c5e-e64936fb87f5',
                    'name' => [
                        'nl' => 'Kopieertest',
                        ],
                    'sameAs' => [
                        'http://www.uitinvlaanderen.be/agenda/e/kopieertest/279e7428-f44f-4b0c-af09-3c53bc2504ef',
                        ],
                    ])
            ->assertReturnedDocumentContains([
                '@id' => 'https://io.uitdatabank.dev/event/5ece8d77-48dd-402d-9c5e-e64936fb87f5',
                'name' => [
                    'nl' => 'Kopieertest',
                    ],
                'sameAs' => [
                    'http://www.uitinvlaanderen.be/agenda/e/kopieertest/5ece8d77-48dd-402d-9c5e-e64936fb87f5',
                    ],
                ]);
    }

    /**
     * @test
     */
    public function it_should_fix_visibility_of_label_both_in_labels_and_hiddenLabels(): void
    {
        // Mock that "UiTPAS Mechelen" is visible
        $this->labelReadRepository->expects($this->any())
            ->method('getByName')
            ->with(new StringLiteral('UiTPAS Mechelen'))
            ->willReturn(
                new Entity(
                    new UUID('7ba9e0e6-f1b5-4931-a00a-cd660c990e57'),
                    new StringLiteral('UiTPAS Mechelen'),
                    Visibility::VISIBLE(),
                    Privacy::PRIVACY_PUBLIC()
                )
            );

        // Make sure the hiddenLabels property gets completely removed.
        $this
            ->given(
                [
                    'labels' => [
                        'Aanvaarden van SABAM-cultuurchèques',
                        'UiTPAS Mechelen',
                    ],
                    'hiddenLabels' => [
                        'UiTPAS Mechelen',
                    ],
                ]
            )
            ->assertReturnedDocumentDoesNotContainKey('hiddenLabels');
    }

    /**
     * @test
     */
    public function it_assumes_labels_are_invisible_if_duplicate_and_not_found_in_read_repository(): void
    {
        $this
            ->given(
                [
                    'labels' => [
                        'Aanvaarden van SABAM-cultuurchèques',
                        'UiTPAS Mechelen',
                    ],
                    'hiddenLabels' => [
                        'UiTPAS Mechelen',
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'labels' => [
                        'Aanvaarden van SABAM-cultuurchèques',
                    ],
                    'hiddenLabels' => [
                        'UiTPAS Mechelen',
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_add_labels_if_not_set(): void
    {
        $this
            ->given(
                []
            )
            ->assertReturnedDocumentDoesNotContainKey('labels');
    }

    /**
     * @test
     */
    public function it_should_not_add_hiddenLabels_if_not_set(): void
    {
        $this
            ->given(
                []
            )
            ->assertReturnedDocumentDoesNotContainKey('hiddenLabels');
    }

    private function given(array $given): self
    {
        $this->repository->save(
            new JsonDocument(
                self::DOCUMENT_ID,
                json_encode($given)
            )
        );
        return $this;
    }

    private function assertReturnedDocumentContains(array $expected): void
    {
        $actualFromFetch = $this->repository->fetch(self::DOCUMENT_ID)->getAssocBody();
        $this->assertArrayContainsExpectedKeys($expected, $actualFromFetch);
    }

    private function assertReturnedDocumentDoesNotContainKey(string $key): void
    {
        $actualFromFetch = $this->repository->fetch(self::DOCUMENT_ID)->getAssocBody();
        $this->assertArrayNotHasKey($key, $actualFromFetch);
    }

    private function assertArrayContainsExpectedKeys(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
