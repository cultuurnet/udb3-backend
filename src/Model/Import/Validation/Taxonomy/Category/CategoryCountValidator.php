<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Callback;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class CategoryCountValidator extends Validator
{
    /**
     * @param int $min
     * @param int|null $max
     * @param string $name
     */
    public function __construct(CategoryDomain $domain, $min = 0, $max = null, $name = 'terms')
    {
        $domain = $domain->toString();

        if (is_null($max)) {
            $error = "{{name}} must contain at least {$min} item(s) with domain {$domain}.";
        } elseif ($min === 0) {
            $error = "{{name}} must contain at most {$max} item(s) with domain {$domain}.";
        } elseif ($min === $max) {
            $error = "{{name}} must contain exactly {$min} item(s) with domain {$domain}.";
        } else {
            $error = "{{name}} must contain at least {$min} and at most {$max} items with domain {$domain}.";
        }

        // Only check the category count if the categories are in the expected format.
        // Any other errors will be reported by the validators in udb3-models.
        $rules = [
            new When(
                new ArrayType(),
                (new Callback(
                    function (array $categories) use ($domain, $min, $max) {
                        $filtered = array_filter(
                            $categories,
                            function (array $category) use ($domain) {
                                return isset($category['domain']) && $category['domain'] === $domain;
                            }
                        );

                        $count = count($filtered);

                        return $count >= $min && (is_null($max) || $count <= $max);
                    }
                ))->setName($name)->setTemplate($error),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
