<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use Respect\Validation\Validator;

class OrganizerValidator extends Validator
{
    /**
     * @param Validator[] $extraRules
     */
    public function __construct(array $extraRules = [])
    {
        parent::__construct($extraRules);
    }
}
