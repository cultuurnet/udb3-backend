<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class CategoryExistsValidator extends Validator
{

    /**
     * @param CategoryResolverInterface $categoryResolver
     * @param $documentType
     */
    public function __construct(
        CategoryResolverInterface $categoryResolver,
        $documentType
    ) {
        // Only check that the category exists if it actually has an id.
        // Any other errors will be reported by the validators in udb3-models.
        $rules = [
            new Key(
                'id',
                (new CategoryIDExistsValidator($categoryResolver))
                    ->setTemplate('term {{name}} does not exist or is not applicable for ' . $documentType),
                false
            ),
        ];

        parent::__construct($rules);
    }
}
