<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Psr\Http\Message\ServerRequestInterface;

final class QueryParameters
{
    private array $queryParameters;

    public function __construct(ServerRequestInterface $request)
    {
        $this->queryParameters = $request->getQueryParams();
    }

    public function get(string $parameterName, ?string $default = null): ?string
    {
        if (isset($this->queryParameters[$parameterName])) {
            return (string) $this->queryParameters[$parameterName];
        }
        return $default;
    }

    public function getAsBoolean(string $parameterName, ?bool $default = null): bool
    {
        $defaultAsString = $default === null ? null : (string) $default;
        $valueAsString = $this->get($parameterName, $defaultAsString);

        // Do not just use (bool) to cast to boolean but also use filter_var() with FILTER_VALIDATE_BOOL to prevent for
        // example "false" as being cast to true (because a non-empty string = true when casting to bool).
        // See https://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php/15075609
        return (bool) filter_var($valueAsString, FILTER_VALIDATE_BOOL);
    }

    public function getAsInt(string $parameterName, ?int $default = null): int
    {
        $defaultAsString = $default === null ? null : (string) $default;
        $valueAsString = $this->get($parameterName, $defaultAsString);

        return (int) filter_var($valueAsString, FILTER_VALIDATE_INT);
    }

    public function guardEnum(string $parameterName, array $allowedValues): void
    {
        $value = $this->get($parameterName);
        if ($value !== null && !in_array($value, $allowedValues, true)) {
            throw ApiProblem::queryParameterInvalidValue($parameterName, $value, $allowedValues);
        }
    }

    public function guardRequiredEnum(string $parameterName, array $allowedValues): void
    {
        $value = $this->get($parameterName);
        if ($value === null || !in_array($value, $allowedValues, true)) {
            throw ApiProblem::queryParameterInvalidValue($parameterName, $value ?? 'null', $allowedValues);
        }
    }
}
