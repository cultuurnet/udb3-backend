<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Place;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Import\Place\PlaceLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\CategoriesExistValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\EventTypeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\ThemeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label\DocumentLabelPermissionRule;
use CultuurNet\UDB3\Model\Validation\Place\PlaceValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;

class PlaceImportValidator extends PlaceValidator
{
    public function __construct(
        UUIDParser $uuidParser,
        string $userId,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelRelationsRepository
    ) {
        $extraRules = [
            new Key(
                'terms',
                new AllOf(
                    new CategoriesExistValidator(new PlaceLegacyBridgeCategoryResolver(), 'place'),
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
