<?php

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Language;

class JSONLDMainLanguageQuery implements MainLanguageQueryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var Language|null
     */
    private $fallbackLanguage;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param Language $fallbackLanguage
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        Language $fallbackLanguage
    ) {
        $this->documentRepository = $documentRepository;
        $this->fallbackLanguage = $fallbackLanguage;
    }

    /**
     * @inheritdoc
     */
    public function execute($cdbid)
    {
        $document = $this->documentRepository->get($cdbid);

        if (!$document) {
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
