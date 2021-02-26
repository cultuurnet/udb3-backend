<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Organizer;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label\DocumentLabelPermissionRule;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Organizer\WebsiteLookupServiceInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

class OrganizerImportValidator extends OrganizerValidator
{
    /**
     * @param bool $urlRequired
     */
    public function __construct(
        WebsiteLookupServiceInterface $websiteLookupService,
        UUIDParser $uuidParser,
        UserIdentificationInterface $userIdentification,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelRelationsRepository,
        $urlRequired = false
    ) {
        $extraRules = [
            new OrganizerHasUniqueUrlValidator(
                new OrganizerIDParser(),
                $websiteLookupService
            ),
            new DocumentLabelPermissionRule(
                $uuidParser,
                $userIdentification,
                $labelsRepository,
                $labelRelationsRepository
            ),
        ];

        parent::__construct($extraRules, $urlRequired);
    }
}
