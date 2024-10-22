<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels\Name;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class DocumentItemNameResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_resolve_the_name_of_an_organization(): void
    {
        $organizerId = '9e68dafc-01d8-4c1c-9612-599c918b981d';

        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(
                new JsonDocument(
                    $organizerId,
                    Json::encode([
                        'name' => [
                            'nl' => 'publiq vzw',
                            'fr' => 'publiq asbl',
                        ],
                        'mainLanguage' => 'fr',
                    ])
                )
            );

        $resolver = new DocumentItemNameResolver($documentRepository);

        $this->assertEquals('publiq asbl', $resolver->resolve($organizerId));
    }
}
