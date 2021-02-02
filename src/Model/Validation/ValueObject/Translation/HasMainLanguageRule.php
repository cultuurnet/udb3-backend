<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Translation;

use CultuurNet\UDB3\Model\Validation\GroupedValidationExceptionWithoutMainMessage;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validatable;

class HasMainLanguageRule implements Validatable
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $mainLanguageProperty;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $template;

    /**
     * @param string $path
     *   Path to the property that should have a translation value for the mainLanguage.
     *   Separate property names using a dot. Indicate that each item of an array should
     *   be validated using [].
     *   Eg. foo.bar.[].name
     * @param string $mainLanguageProperty
     */
    public function __construct($path, $mainLanguageProperty = 'mainLanguage')
    {
        $this->path = (string) rtrim($path, '.');
        $this->mainLanguageProperty = (string) $mainLanguageProperty;
        $this->template = '{{name}} must have a value for the mainLanguage ({{mainLanguage}})';
    }

    /**
     * @inheritdoc
     */
    public function assert($input)
    {
        $errors = $this->getErrors($input);

        if (count($errors) > 0) {
            throw $this->createException($errors);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function check($input)
    {
        return $this->assert($input);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name ? $this->name : $this->path;
    }

    /**
     * @inheritdoc
     * @return GroupedValidationExceptionWithoutMainMessage
     */
    public function reportError($input, array $relatedExceptions = [])
    {
        $errors = $this->getErrors($input);
        return $this->createException($errors);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        return count($this->getErrors($input)) === 0;
    }

    /**
     * @param array $input
     * @param string|null $mainLanguage
     * @param string|null $path
     * @param string|null $name
     * @return string[]
     */
    private function getErrors($input, $mainLanguage = null, $path = null, $name = null)
    {
        if (!is_array($input)) {
            // Should be handled by a different rule.
            return [];
        }

        $mainLanguageValidator = new LanguageValidator();
        if (!$mainLanguage) {
            $mainLanguage = isset($input[$this->mainLanguageProperty]) ? $input[$this->mainLanguageProperty] : null;
        }

        if (!$mainLanguageValidator->validate($mainLanguage)) {
            // Should be handled by a different rule.
            return [];
        }

        if (!$path) {
            $path = $this->path;
        }

        if (!$name) {
            $name = $this->getName();
        }

        $errors = [];

        $nestedProperties = explode('.', $path);
        $traversedProperties = [];
        $propertyReference = $input;

        while ($nestedPropertyName = array_shift($nestedProperties)) {
            if ($nestedPropertyName === '[]') {
                foreach ($propertyReference as $key => $arrayItem) {
                    $remainingPath = implode('.', $nestedProperties);
                    $traversedPath = implode('.', $traversedProperties);
                    $name = $traversedPath . '[' . $key . '].' . $remainingPath;

                    $recursiveErrors = $this->getErrors(
                        $arrayItem,
                        $mainLanguage,
                        $remainingPath,
                        $name
                    );

                    $errors = array_merge($errors, $recursiveErrors);
                }
                return $errors;
            }

            if (!isset($propertyReference[$nestedPropertyName])) {
                // Is either optional or should be handled by a different rule.
                return [];
            }

            $propertyReference = $propertyReference[$nestedPropertyName];
            $traversedProperties[] = $nestedPropertyName;
        }

        if (!is_array($propertyReference)) {
            // Should be handled by a different rule.
            return [];
        }

        if (!isset($propertyReference[$mainLanguage])) {
            $errors[] = $this->createError($name, $mainLanguage);
        }

        return $errors;
    }

    /**
     * @param string $name
     * @param string $mainLanguage
     * @return string
     */
    private function createError($name, $mainLanguage)
    {
        $template = $this->template;
        $template = str_replace('{{name}}', $name, $template);
        $template = str_replace('{{mainLanguage}}', $mainLanguage, $template);
        return $template;
    }

    /**
     * @param array $errors
     * @return GroupedValidationExceptionWithoutMainMessage
     */
    private function createException($errors)
    {
        $exceptions = array_map(
            function ($errorMessage) {
                return new ValidationException($errorMessage);
            },
            $errors
        );

        $grouped = new GroupedValidationExceptionWithoutMainMessage();
        $grouped->setRelated($exceptions);

        return $grouped;
    }
}
