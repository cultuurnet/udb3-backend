<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

final class SearchParameter
{
    public const SUPPORTED_URL_PARAMETERS = ['itemId', 'state', 'state[]', 'ownerId'];

    private string $urlParameter;
    private string $value;

    public function __construct(string $urlParameter, string $value)
    {
        if (!in_array($urlParameter, self::SUPPORTED_URL_PARAMETERS)) {
            throw new \InvalidArgumentException('Unsupported url parameter: ' . $urlParameter);
        }

        $this->urlParameter = $urlParameter;
        $this->value = $value;
    }

    public function getUrlParameter(): string
    {
        return $this->urlParameter;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function hasMultipleValues(): bool
    {
        return str_ends_with($this->value, '[]');
    }
}
