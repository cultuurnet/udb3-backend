<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class PropertyPolyfillRepositoryTest extends TestCase
{
    private const DOCUMENT_ID = '5d7ed700-17de-4c1f-923a-0affe7cf2d4c';

    private PropertyPolyfillRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new PropertyPolyfillRepository(
            new InMemoryDocumentRepository()
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
    public function it_should_not_add_images_if_not_set(): void
    {
        $this
            ->given(
                []
            )
            ->assertReturnedDocumentDoesNotContainKey('images');
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
