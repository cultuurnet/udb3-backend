<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

final class VariableState
{
    private const START_DELIMITER = '%{';
    private const END_DELIMITER = '}';

    private static ?string $scenarioLabel = null;

    private array $variables = [];

    public function setVariable(string $key, string $value): void
    {
        $this->variables[$key] = $value;
    }

    public function setRandomVariable(string $key, int $length): string
    {
        $variable = $this->generateRandomVariable($length);
        $this->variables[$key] = $variable;
        return $variable;
    }

    public function setRandomEmail(string $key): string
    {
        $name = $this->generateRandomVariable(10);
        $domain = $this->generateRandomVariable(5);
        $variable = $name . '@' . $domain . '.com';

        $this->variables[$key] = $variable;
        return $variable;
    }

    public function getVariable(string $key): string
    {
        return $this->variables[$key];
    }

    public static function setScenarioLabel(string $label): void
    {
        self::$scenarioLabel = $label;
    }

    public static function getScenarioLabel(): ?string
    {
        return self::$scenarioLabel;
    }

    public static function clearScenarioLabel(): void
    {
        self::$scenarioLabel = null;
    }

    public function replaceVariables(string $key): string
    {
        if ($this->isVariable($key)) {
            return $this->variables[$this->extractVariable($key)];
        }

        while ($this->containsVariable($key)) {
            $variable = $this->extractVariable($key);
            $value = $this->variables[$variable];
            $key = str_replace('%{' . $variable . '}', $value, $key);
        }

        return $this->variableState[$key] ?? $key;
    }

    private function generateRandomVariable(int $length): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charLength = strlen($characters);
        $randomVariable = '';

        for ($i = 0; $i < $length; $i++) {
            $randomVariable .= $characters[rand(0, $charLength - 1)];
        }

        return $randomVariable;
    }

    private function extractVariable(string $input): string
    {
        $startPos = strpos($input, self::START_DELIMITER);

        if ($startPos === false) {
            return '';
        }

        $startPos += strlen(self::START_DELIMITER);
        $endPos = strpos($input, self::END_DELIMITER, $startPos);

        if ($endPos === false) {
            return '';
        }

        return substr($input, $startPos, $endPos - $startPos);
    }

    private function containsVariable(string $input): bool
    {
        $containsStart = strpos($input, self::START_DELIMITER) !== false;
        $containsEnd = strpos($input, self::END_DELIMITER) !== false;

        return $containsStart && $containsEnd;
    }

    private function isVariable(string $input): bool
    {
        $containsStart = strpos($input, self::START_DELIMITER);
        $containsEnd = strpos($input, self::END_DELIMITER);

        return $containsStart === 0 && $containsEnd === strlen($input);
    }
}
