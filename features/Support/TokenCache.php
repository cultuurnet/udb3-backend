<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

final class TokenCache
{
    public static function getTokenForUser(string $userName): ?string
    {
        $tokenFile = sys_get_temp_dir() . '/jwt_token_' . $userName . '.txt';
        if (file_exists($tokenFile)) {
            return file_get_contents($tokenFile);
        }

        return null;
    }

    public static function setTokenForUser(string $userName, string $token): void
    {
        $tokenFile = self::createTokenFileName($userName);
        file_put_contents($tokenFile, $token);
    }

    public static function clearTokens(): void
    {
        $config = require __DIR__ . '/../config.features.php';
        $userNames = array_keys($config['users']);

        foreach ($userNames as $userName) {
            $tokenFile = self::createTokenFileName($userName);
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
        }
    }

    private static function createTokenFileName(string $userName): string
    {
        return sys_get_temp_dir() . '/jwt_token_' . $userName . '.txt';
    }
}
