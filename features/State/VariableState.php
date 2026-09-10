<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

use RuntimeException;

final class VariableState
{
    private const VARIABLE_PATTERN = '%\\{([A-Za-z0-9_]+)\\}';

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

    public function replaceVariables(string $input): string
    {
        return preg_replace_callback(
            '/' . self::VARIABLE_PATTERN . '/',
            function (array $match): string {
                if (!array_key_exists($match[1], $this->variables)) {
                    throw new RuntimeException('Unknown variable %{' . $match[1] . '} used in a step');
                }

                return $this->variables[$match[1]];
            },
            $input
        );
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
}
