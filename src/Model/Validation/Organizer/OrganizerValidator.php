<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\HasMainLanguageRule;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class OrganizerValidator extends Validator
{
    /**
     * @param Validator[] $extraRules
     */
    public function __construct(array $extraRules = [])
    {
        // Note that url is NOT required when validating Organizer JSON returned
        // by UiTdatabank, because older organizers were created without url.
        // However, url is required to create new organizers.
        $rules = [
            new HasMainLanguageRule('name'),
            new HasMainLanguageRule('address'),
        ];

        $rules = array_merge($rules, $extraRules);

        parent::__construct($rules);
    }
}
