<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use CultuurNet\UDB3\Event\ReadModel\JsonDocument;

class VariationIdToJSONLDDocumentConverter
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     */
    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @param $id
     * @return JsonDocument
     */
    public function convert($id)
    {
        try {
            $document = $this->documentRepository->get($id);
        }
        catch (DocumentGoneException $e) {
            throw new GoneHttpException();
        }

        if (!$document) {
            throw new NotFoundHttpException();
        }

        return $document;
    }
}
