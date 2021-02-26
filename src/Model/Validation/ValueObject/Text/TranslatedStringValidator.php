<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Text;

use CultuurNet\UDB3\Model\Validation\ValueObject\NotEmptyStringValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Length;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class TranslatedStringValidator extends Validator
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);

        $rules = [
            new ArrayType(),
            new When(
                new ArrayType(),
                new AllOf(
                    new Each(
                        // This is a quick fix to prevent '"" must not be empty" messages.
                        // @see https://github.com/Respect/Validation/issues/924
                        (new NotEmptyStringValidator())->setName($this->getName() . ' value'),
                        new LanguageValidator()
                    ),
                    (new Length(1, null, true))->setName($this->getName())
                ),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
