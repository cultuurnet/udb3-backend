<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

trait GuardOrganizer
{
    private function guardOrganizer(string $organizerId, DocumentRepository $organizerDocumentRepository): void
    {
        try {
            $organizerDocumentRepository->fetch($organizerId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/organizer',
                    'The organizer with id "' . $organizerId . '" was not found.'
                )
            );
        }
    }
}
