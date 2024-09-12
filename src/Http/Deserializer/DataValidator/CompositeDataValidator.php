<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\DataValidator;

use CultuurNet\UDB3\Deserializer\DataValidationException;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class CompositeDataValidator implements DataValidatorInterface
{
    private array $validators = [];

    private string $fieldLevelGlue = '.';

    private bool $overwriteErrorMessages;

    public function __construct(
        string $fieldLevelGlue = '.',
        bool $overwriteErrorMessages = false
    ) {
        $this->validators = [];
        $this->fieldLevelGlue = (string) $fieldLevelGlue;
        $this->overwriteErrorMessages = (bool) $overwriteErrorMessages;
    }

    /**
     * @param string[] $depth
     */
    public function withValidator(DataValidatorInterface $validator, array $depth = []): CompositeDataValidator
    {
        $c = clone $this;
        $c->validators[] = [
            'validator' => $validator,
            'depth' => $depth,
        ];
        return $c;
    }

    /**
     * @throws DataValidationException
     */
    public function validate(array $data): void
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

    private function getValidatorContext(array $data, array $depth): ?array
    {
        if (empty($depth)) {
            return $data;
        }

        return $this->getValidatorContextRecursively($data, $depth);
    }

    private function getValidatorContextRecursively(array $data, array $depth): ?array
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

    private function getCompleteFieldName(string $fieldName, array $depth): string
    {
        if (empty($depth)) {
            return $fieldName;
        }

        return implode($this->fieldLevelGlue, $depth) . '.' . $fieldName;
    }

    private function storeFieldErrorMessage(string $fieldName, string $validationMessage, array &$errors): void
    {
        if (!isset($errors[$fieldName]) || $this->overwriteErrorMessages) {
            $errors[$fieldName] = $validationMessage;
        }
    }
}
