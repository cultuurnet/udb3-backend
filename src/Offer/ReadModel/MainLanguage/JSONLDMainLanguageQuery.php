<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class JSONLDMainLanguageQuery implements MainLanguageQueryInterface
{
    private DocumentRepository $documentRepository;

    private ?Language $fallbackLanguage;

    public function __construct(
        DocumentRepository $documentRepository,
        Language $fallbackLanguage
    ) {
        $this->documentRepository = $documentRepository;
        $this->fallbackLanguage = $fallbackLanguage;
    }

    public function execute($cdbid): Language
    {
        try {
            $document = $this->documentRepository->fetch($cdbid);
        } catch (DocumentDoesNotExist $e) {
            throw new EntityNotFoundException('Could not load JSON-LD document for cdbid ' . $cdbid);
        }

        $json = $document->getBody();

        if (isset($json->mainLanguage)) {
            return new Language($json->mainLanguage);
        } else {
            return $this->fallbackLanguage;
        }
    }
}
