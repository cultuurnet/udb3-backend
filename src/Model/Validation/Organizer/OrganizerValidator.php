<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use CultuurNet\UDB3\Model\Validation\ValueObject\Contact\ContactPointValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Geography\TranslatedAddressValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Label\LabelsValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Text\TranslatedStringValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\HasMainLanguageRule;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class OrganizerValidator extends Validator
{
    /**
     * @param Validator[] $extraRules
     * @param bool $urlRequired
     */
    public function __construct(array $extraRules = [], $urlRequired = false)
    {
        // Note that url is NOT required when validating Organizer JSON returned
        // by UiTdatabank, because older organizers were created without url.
        // However, url is required to create new organizers.
        $rules = [
            new Key('@id', new OrganizerIDValidator(), true),
            new Key('mainLanguage', new LanguageValidator(), true),
            new Key('name', new TranslatedStringValidator('name'), true),
            new HasMainLanguageRule('name'),
            new Key('url', new Url(), $urlRequired),
            new Key('address', new TranslatedAddressValidator(), false),
            new HasMainLanguageRule('address'),
            new Key('labels', new LabelsValidator(), false),
            new Key('hiddenLabels', new LabelsValidator(), false),
            new Key('contactPoint', new ContactPointValidator(), false),
        ];

        $rules = array_merge($rules, $extraRules);

        parent::__construct($rules);
    }
}
