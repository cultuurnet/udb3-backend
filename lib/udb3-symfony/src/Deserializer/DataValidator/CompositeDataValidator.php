<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\DataValidator;

use CultuurNet\Deserializer\DataValidationException;

class CompositeDataValidator implements DataValidatorInterface
{
    /**
     * @var array
     */
    private $validators = [];

    /**
     * @var string
     */
    private $fieldLevelGlue = '.';

    /**
     * @var bool
     */
    private $overwriteErrorMessages;

    /**
     * @param string $fieldLevelGlue
     * @param bool $overwriteErrorMessages
     */
    public function __construct(
        $fieldLevelGlue = '.',
        $overwriteErrorMessages = false
    ) {
        $this->validators = [];
        $this->fieldLevelGlue = (string) $fieldLevelGlue;
        $this->overwriteErrorMessages = (bool) $overwriteErrorMessages;
    }

    /**
     * @param DataValidatorInterface $validator
     * @param string[] $depth
     * @return CompositeDataValidator
     */
    public function withValidator(DataValidatorInterface $validator, $depth = [])
    {
        $c = clone $this;
        $c->validators[] = [
            'validator' => $validator,
            'depth' => $depth
        ];
        return $c;
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $errors = [];

        foreach ($this->validators as $validatorInfo) {
            /* @var DataValidatorInterface $validator */
            $validator = $validatorInfo['validator'];
            $depth = $validatorInfo['depth'];

            $validatorContext = $this->getValidatorContext($data, $depth);

            if (is_null($validatorContext)) {
                continue;
            }

            try {
                $validator->validate($validatorContext);
            } catch (DataValidationException $e) {
                foreach ($e->getValidationMessages() as $fieldName => $validationMessage) {
                    $completeFieldName = $this->getCompleteFieldName($fieldName, $depth);
                    $this->storeFieldErrorMessage($completeFieldName, $validationMessage, $errors);
                }
            }
        }

        if (!empty($errors)) {
            $e = new DataValidationException();
            $e->setValidationMessages($errors);
            throw $e;
        }
    }

    /**
     * @param array $data
     * @param array $depth
     * @return array|null
     */
    private function getValidatorContext(array $data, $depth)
    {
        if (empty($depth)) {
            return $data;
        }

        return $this->getValidatorContextRecursively($data, $depth);
    }

    /**
     * @param array $data
     * @param $depth
     * @return array|null
     */
    private function getValidatorContextRecursively(array $data, $depth)
    {
        $depth = array_values($depth);
        $key = $depth[0];

        if (!isset($data[$key])) {
            return null;
        }

        if (count($depth) > 1) {
            return $this->getValidatorContextRecursively(
                $data[$key],
                array_shift($depth)
            );
        }

        return $data[$key];
    }

    /**
     * @param string $fieldName
     * @param array $depth
     * @return string
     */
    private function getCompleteFieldName($fieldName, $depth)
    {
        if (empty($depth)) {
            return $fieldName;
        }

        return implode($this->fieldLevelGlue, $depth) . '.' . $fieldName;
    }

    /**
     * @param string $fieldName
     * @param string $validationMessage
     * @param array $errors
     */
    private function storeFieldErrorMessage($fieldName, $validationMessage, &$errors)
    {
        if (!isset($errors[$fieldName]) || $this->overwriteErrorMessages) {
            $errors[$fieldName] = $validationMessage;
        }
    }
}
