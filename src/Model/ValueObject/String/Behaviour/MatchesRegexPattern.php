<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait MatchesRegexPattern
{
    /**
     * @throws \InvalidArgumentException
     */
    private function guardRegexPattern(string $pattern, string $value, ?string $customExceptionMessage = null)
    {
        if (!preg_match($pattern, $value)) {
            $message = "String '{$value}' does not match regex pattern {$pattern}.";
            if ($customExceptionMessage) {
                $message = $customExceptionMessage;
            }

            throw new \InvalidArgumentException($message);
        }
    }
}
