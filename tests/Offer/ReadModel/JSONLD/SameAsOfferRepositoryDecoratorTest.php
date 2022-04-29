<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SameAs;
use PHPUnit\Framework\TestCase;

class SameAsOfferRepositoryDecoratorTest extends TestCase
{
    private const GIVEN_JSON = [
        '@id' => '5ece8d77-48dd-402d-9c5e-e64936fb87f5',
        'name' => [
            'nl' => 'Kopieertest',
            'fr' => 'Essai de copie',
            'en' => 'Copy Test',
        ],
        'sameAs' => [
            'http://www.uitinvlaanderen.be/agenda/e/kopieertest/279e7428-f44f-4b0c-af09-3c53bc2504ef',
            ],
    ];

    private const FIXED_JSON = [
        '@id' => '5ece8d77-48dd-402d-9c5e-e64936fb87f5',
        'name' => [
            'nl' => 'Kopieertest',
            'fr' => 'Essai de copie',
            'en' => 'Copy Test',
        ],
        'sameAs' => [
            'http://www.uitinvlaanderen.be/agenda/e/kopieertest/5ece8d77-48dd-402d-9c5e-e64936fb87f5',
        ],
    ];

    /**
     * @test
     */
    public function it_should_correct_the_same_as(): void
    {
        $id = '5ece8d77-48dd-402d-9c5e-e64936fb87f5';

        $sameAsOfferRepositoryDecorator = new SameAsOfferRepositoryDecorator(
            new InMemoryDocumentRepository(),
            new SameAs()
        );

        $givenDocument = new JsonDocument($id, Json::encode(self::GIVEN_JSON));

        $sameAsOfferRepositoryDecorator->save($givenDocument);
        $actualDocument = $sameAsOfferRepositoryDecorator->fetch($id, true);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(self::FIXED_JSON, $actualJson);
    }
}
