<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\PreProcessing;

use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;

class TermPreProcessingDocumentImporter implements DocumentImporterInterface
{
    /**
     * @var DocumentImporterInterface
     */
    private $jsonImporter;

    /**
     * @var CategoryResolverInterface
     */
    private $categoryResolver;


    public function __construct(
        DocumentImporterInterface $jsonImporter,
        CategoryResolverInterface $categoryResolver
    ) {
        $this->jsonImporter = $jsonImporter;
        $this->categoryResolver = $categoryResolver;
    }

    /**
     * Pre-processes the JSON to polyfill missing term properties if possible.
     *
     */
    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null)
    {
        $data = $decodedDocument->getBody();

        // Attempt to add label and/or domain to terms, or fix them if they're incorrect.
        if (isset($data['terms']) && is_array($data['terms'])) {
            $data['terms'] = array_map(
                function (array $term) {
                    if (isset($term['id']) && is_string($term['id'])) {
                        $id = $term['id'];
                        $category = $this->categoryResolver->byId(new CategoryID($id));

                        if ($category) {
                            $term['label'] = $category->getLabel()->toString();
                            $term['domain'] = $category->getDomain()->toString();
                        }
                    }

                    return $term;
                },
                $data['terms']
            );
        }

        $decodedDocument = $decodedDocument->withBody($data);

        $this->jsonImporter->import($decodedDocument, $consumer);
    }
}
