<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class NewPropertyPolyfillOfferRepositoryTest extends TestCase
{
    public const DOCUMENT_ID = '5d7ed700-17de-4c1f-923a-0affe7cf2d4c';

    /**
     * @var NewPropertyPolyfillOfferRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->repository = new NewPropertyPolyfillOfferRepository(
            new InMemoryDocumentRepository()
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
    public function it_should_polyfill_a_default_status_on_subEvent_if_not_set(): void
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
                            '@type' => 'Event',
                            'startDate' => '2020-01-01T16:00:00+01:00',
                            'endDate' => '2020-01-01T20:00:00+01:00',
                            'status' => [
                                'type' => 'Available',
                            ],
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
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Tijdelijk uitgesteld',
                                ],
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
            ->given(['location' => ['status' => 'Unavailable']])
            ->assertReturnedDocumentContains([
                'location' => [
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_should_add_default_status_of_embedded_location(): void
    {
        $this
            ->given(['location' => []])
            ->assertReturnedDocumentContains([
                'location' => [
                    'status' => [
                        'type' => 'Available',
                    ],
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
        $actualFromGet = $this->repository->get(self::DOCUMENT_ID)->getAssocBody();
        $this->assertArrayContainsExpectedKeys($expected, $actualFromFetch);
        $this->assertArrayContainsExpectedKeys($expected, $actualFromGet);
    }

    private function assertReturnedDocumentDoesNotContainKey(string $key): void
    {
        $actualFromFetch = $this->repository->fetch(self::DOCUMENT_ID)->getAssocBody();
        $actualFromGet = $this->repository->get(self::DOCUMENT_ID)->getAssocBody();
        $this->assertArrayNotHasKey($key, $actualFromFetch);
        $this->assertArrayNotHasKey($key, $actualFromGet);
    }

    private function assertArrayContainsExpectedKeys(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
