<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Term\TermNotFoundException;
use CultuurNet\UDB3\Term\TermRepository;

final class TermLabelOfferRepositoryDecorator extends DocumentRepositoryDecorator
{
    private TermRepository $termRepository;

    public function __construct(DocumentRepository $repository, TermRepository $termRepository)
    {
        parent::__construct($repository);
        $this->termRepository = $termRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        return $document->applyAssoc(
            function (array $json) {
                if (!isset($json['terms']) || !is_array($json['terms'])) {
                    // JSON is not formatted as expected, continue without trying to fix it.
                    return $json;
                }

                $json['terms'] = array_map(
                    function ($term) {
                        if (!is_array($term) || !isset($term['id']) || !is_string($term['id'])) {
                            // The term is not formatted as expected, continue on to the next one without trying to fix
                            // this one.
                            return $term;
                        }

                        $id = $term['id'];

                        try {
                            $termConfig = $this->termRepository->getById($id);
                        } catch (TermNotFoundException $e) {
                            // The term id is not found in the config files with term labels. Continue on to the next
                            // term without trying to fix this one.
                            return $term;
                        }

                        $label = $termConfig->getLabel();
                        if (!is_null($label)) {
                            $term['label'] = $label->toString();
                        }
                        return $term;
                    },
                    $json['terms']
                );

                return $json;
            }
        );
    }
}
