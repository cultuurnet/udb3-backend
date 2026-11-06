<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

final class AiGeneratedImageUrl
{
    private const string MARKER = '.ai';

    public static function addMarker(string $url): string
    {
        [$base, $extension] = self::split($url);

        if ($extension === null || str_ends_with($base, self::MARKER)) {
            return $url;
        }

        return $base . self::MARKER . '.' . $extension;
    }

    public static function removeMarker(string $url): string
    {
        [$base, $extension] = self::split($url);

        if ($extension === null || !str_ends_with($base, self::MARKER)) {
            return $url;
        }

        return substr($base, 0, -strlen(self::MARKER)) . '.' . $extension;
    }

    /**
     * @return array{string, string|null}
     */
    private static function split(string $url): array
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if ($extension === '') {
            return [$url, null];
        }

        return [substr($url, 0, -(strlen($extension) + 1)), $extension];
    }
}
