<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Organizer;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Symfony\Deserializer\Address\AddressDataValidator;
use CultuurNet\UDB3\Symfony\Deserializer\ContactPoint\ContactPointDataValidator;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\CompositeDataValidator;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\RequiredPropertiesDataValidator;
use CultuurNet\UDB3\Title;

class OrganizerCreationPayloadDataValidator implements DataValidatorInterface
{
    /**
     * @var CompositeDataValidator
     */
    private $validator;

    public function __construct()
    {
        $this->validator = (new CompositeDataValidator())
            ->withValidator(new RequiredPropertiesDataValidator(['website', 'name']))
            ->withValidator(new AddressDataValidator(), ['address'])
            ->withValidator(new ContactPointDataValidator(), ['contact']);
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $messages = [];

        try {
            $this->validator->validate($data);
        } catch (DataValidationException $e) {
            $messages = $e->getValidationMessages();
        }

        if (isset($data['name'])) {
            try {
                new Title($data['name']);
            } catch (\Exception $e) {
                $messages['name'] = $e->getMessage();
            }
        }

        if (isset($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $messages['website'] = 'Not a valid url.';
            }
        }

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }
    }
}
