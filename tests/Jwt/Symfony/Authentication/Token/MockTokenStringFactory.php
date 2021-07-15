<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class MockTokenStringFactory
{
    public static function createWithClaims(array $claims): string
    {
        $builder = new Builder();
        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }
        return (string) $builder->getToken(
            new Sha256(),
            new Key(file_get_contents(__DIR__ . '/../../../samples/private.pem'), 'secret')
        );
    }
}
