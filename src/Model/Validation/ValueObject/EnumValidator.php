<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject;

use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\OneOf;
use Respect\Validation\Validator;

abstract class EnumValidator extends Validator
{
    public function __construct()
    {
        $values = $this->getAllowedValues();

        $rules = array_map(
            function ($value) {
                // Equals has a grammatically incorrect error message in 1.x.
                // @see https://github.com/Respect/Validation/pull/721
                $template = '{{name}} must be equal to {{compareTo}}';
                $equals = new Equals($value);
                $equals->setTemplate($template);
                return $equals;
            },
            $values
        );

        $rules = [
            new OneOf(...$rules),
        ];

        parent::__construct($rules);
    }

    /**
     * @return string[]
     */
    abstract protected function getAllowedValues();
}
