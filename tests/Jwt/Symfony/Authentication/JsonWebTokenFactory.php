<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class JsonWebTokenFactory
{
    public static function createWithClaims(array $claims): JsonWebToken
    {
        $builder = new Builder();
        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }
        return new JsonWebToken(
            (string) $builder->getToken(
                new Sha256(),
                new Key(file_get_contents(__DIR__ . '/../../samples/private.pem'), 'secret')
            )
        );
    }

    public static function createWithInvalidSignature(): JsonWebToken
    {
        return new JsonWebToken(
            (string) (new Builder())->getToken(
                new Sha256(),
                new Key(file_get_contents(__DIR__ . '/../../samples/private-invalid.pem'))
            )
        );
    }

    public static function getPublicKey(): string
    {
        return file_get_contents(__DIR__ . '/../../samples/public.pem');
    }
}
