<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Http\Deserializer\DataValidator\CompositeDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class CreateEventDataValidator implements DataValidatorInterface
{
    private CompositeDataValidator $validator;

    public function __construct()
    {
        $this->validator = (new CompositeDataValidator())
            ->withValidator(new MajorInfoDataValidator())
            ->withValidator(new RequiredPropertiesDataValidator(['mainLanguage']));
    }

    public function validate(array $data): void
    {
        $this->validator->validate($data);
    }
}
