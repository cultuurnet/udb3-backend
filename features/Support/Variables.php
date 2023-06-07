<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

final class Variables
{
    private const START_DELIMITER = '%{';
    private const END_DELIMITER = '}';

    private array $variables = [];

    public function addVariable(string $key, string $value): void
    {
        $this->variables[$key] = $value;
    }

    public function getVariable(string $key): string
    {
        if ($this->isVariable($key)) {
            return $this->variables[$this->extractVariable($key)];
        }

        if ($this->containsVariable($key)) {
            $variable = $this->extractVariable($key);
            $value = $this->variables[$variable];
            return str_replace('%{'. $variable . '}', $value, $key);
        }

         return $this->variables[$key] ?? $key;
    }

    public function addRandomVariable(string $key, int $length): string
    {
        $variable = $this->generateRandomVariable($length);
        $this->variables[$key] = $variable;
        return $variable;
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