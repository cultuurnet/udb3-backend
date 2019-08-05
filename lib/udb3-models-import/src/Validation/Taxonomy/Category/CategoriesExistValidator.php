<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class CategoriesExistValidator extends Validator
{
    /**
     * @param CategoryResolverInterface $categoryResolver
     * @param string $documentType
     * @param string $propertyName
     */
    public function __construct(CategoryResolverInterface $categoryResolver, $documentType, $propertyName = 'terms')
    {
        // Only check that the categories exist if they are in the expected format.
        // Any other errors will be reported by the validators in udb3-models.
        $rules = [
            new When(
                new ArrayType(),
                (new Each(
                    new When(
                        new ArrayType(),
                        new CategoryExistsValidator($categoryResolver, $documentType),
                        new AlwaysValid()
                    )
                ))->setName($propertyName),
                new AlwaysValid()
            )
        ];

        parent::__construct($rules);
    }
}
