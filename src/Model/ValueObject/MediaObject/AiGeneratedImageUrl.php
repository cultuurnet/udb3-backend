<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

final class AiGeneratedImageUrl
{
    public static function addMarker(string $url): string
    {
        return preg_replace('/(?<!\.ai)(\.[A-Za-z0-9]+)$/', '.ai$1', $url) ?? $url;
    }

    public static function removeMarker(string $url): string
    {
        return preg_replace('/\.ai(\.[A-Za-z0-9]+)$/', '$1', $url) ?? $url;
    }
}
