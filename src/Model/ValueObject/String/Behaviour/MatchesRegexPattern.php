<?php

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait MatchesRegexPattern
{
    /**
     * @param string $pattern
     * @param string $value
     * @param string|null $customExceptionMessage
     * @throws \InvalidArgumentException
     */
    private function guardRegexPattern($pattern, $value, $customExceptionMessage = null)
    {
        /* @var IsString $this */
        $this->guardString($value);

        if (!preg_match($pattern, $value)) {
            $message = "String '{$value}' does not match regex pattern {$pattern}.";
            if ($customExceptionMessage) {
                $message = $customExceptionMessage;
            }

            throw new \InvalidArgumentException($message);
        }
    }
}
