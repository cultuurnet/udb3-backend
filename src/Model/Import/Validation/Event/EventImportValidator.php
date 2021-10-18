<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Event;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\Validation\Place\PlaceReferenceExistsValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\CategoriesExistValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\EventTypeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\ThemeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label\DocumentLabelPermissionRule;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Validation\Event\EventValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;

class EventImportValidator extends EventValidator
{
    public function __construct(
        DocumentRepository $placeRepository,
        UUIDParser $uuidParser,
        string $userId,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelRelationsRepository
    ) {
        $extraRules = [
            new Key(
                'location',
                new PlaceReferenceExistsValidator(
                    new PlaceIDParser(),
                    $placeRepository
                )
            ),
            new Key(
                'terms',
                new AllOf(
                    new CategoriesExistValidator(new EventLegacyBridgeCategoryResolver(), 'event'),
                    new EventTypeCountValidator(),
                    new ThemeCountValidator()
                ),
                false
            ),
            new DocumentLabelPermissionRule(
                $uuidParser,
                $userId,
                $labelsRepository,
                $labelRelationsRepository
            ),
        ];

        parent::__construct($extraRules);
    }
}
