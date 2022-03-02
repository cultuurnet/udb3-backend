<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Event;

use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\Validation\Place\PlaceReferenceExistsValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\CategoriesExistValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\EventTypeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\ThemeCountValidator;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Validation\Event\EventValidator;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;

class EventImportValidator extends EventValidator
{
    public function __construct(DocumentRepository $placeRepository)
    {
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
                    new CategoriesExistValidator(new EventCategoryResolver(), 'event'),
                    new EventTypeCountValidator(),
                    new ThemeCountValidator()
                ),
                false
            ),
        ];

        parent::__construct($extraRules);
    }
}
