<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PropertyPolyfillRepositoryTest extends TestCase
{
    private const DOCUMENT_ID = '5d7ed700-17de-4c1f-923a-0affe7cf2d4c';

    private PropertyPolyfillRepository $repository;
    private MockObject $labelReadRepository;

    protected function setUp(): void
    {
        $this->labelReadRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->repository = new PropertyPolyfillRepository(
            new InMemoryDocumentRepository(),
            $this->labelReadRepository
        );
    }

    /**
     * @test
     */
    public function it_should_polyfill_an_image_type_if_not_set(): void
    {
        $this
            ->given(
                [
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                        ],
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                            '@type' => 'schema:ImageObject',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                        ],
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_polyfill_an_image_inLanguage_if_not_set_but_language_is_set(): void
    {
        $this
            ->given(
                [
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                            '@type' => 'schema:ImageObject',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                            'language' => 'fr',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                            'inLanguage' => 'de',
                        ],
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/b01d92c0-5e53-4341-9625-c2264325d8c6',
                            '@type' => 'schema:ImageObject',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                            'inLanguage' => 'fr',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/29a88d72-2ec0-48ea-aa1c-5c083deea0c8',
                            '@type' => 'schema:ImageObject',
                            'inLanguage' => 'de',
                        ],
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_add_images_if_not_set(): void
    {
        $this
            ->given(
                []
            )
            ->assertReturnedDocumentDoesNotContainKey('images');
    }

    /**
     * @test
     */
    public function it_should_fix_visibility_of_label_both_in_labels_and_hiddenLabels(): void
    {
        // Mock that "UiTPAS Mechelen" is visible
        $this->labelReadRepository->expects($this->any())
            ->method('getByName')
            ->with('uitpas mechelen')
            ->willReturn(
                new Entity(
                    new Uuid('7ba9e0e6-f1b5-4931-a00a-cd660c990e57'),
                    'UiTPAS Mechelen',
                    Visibility::visible(),
                    Privacy::public()
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
                        'uitpas Mechelen',
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
                        'uitpas Mechelen',
                        '3rd label to check that the array does not become an object when a label in the middle is removed',
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
                        '3rd label to check that the array does not become an object when a label in the middle is removed',
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


    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-4708
     */
    public function it_should_remove_null_labels(): void
    {
        $this
            ->given(
                [
                    'hiddenLabels' => [null, 'foo'],
                ]
            )
            ->assertReturnedDocumentContains(['hiddenLabels' => ['foo']]);
    }

    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-4708
     */
    public function it_should_remove_label_properties_with_only_null_values(): void
    {
        $this
            ->given(
                [
                    'labels' => [null],
                ]
            )
            ->assertReturnedDocumentDoesNotContainKey('labels');
    }

    private function given(array $given): self
    {
        $this->repository->save(
            new JsonDocument(
                self::DOCUMENT_ID,
                Json::encode($given)
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
