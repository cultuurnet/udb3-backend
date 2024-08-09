<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\SampleFiles;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\RegisteredClaims;

final class JsonWebTokenFactory
{
    public static function createWithClaims(array $claims): JsonWebToken
    {
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file(__DIR__ . '/samples/private.pem', 'secret')
        );

        $builder = $config->builder();
        foreach ($claims as $claim => $value) {
            if (!in_array($claim, RegisteredClaims::ALL, true)) {
                $builder = $builder->withClaim($claim, $value);
            }

            if ($claim === 'iss') {
                $builder = $builder->issuedBy($value);
            }

            if ($claim === 'sub') {
                $builder = $builder->relatedTo($value);
            }

            if ($claim === 'aud') {
                $builder = $builder->permittedFor($value);
            }

            if (in_array($claim, RegisteredClaims::DATE_CLAIMS, true)) {
                $date = (new DateTimeImmutable())->setTimestamp($value);

                if ($claim === 'iat') {
                    $builder = $builder->issuedAt($date);
                }

                if ($claim === 'nbf') {
                    $builder = $builder->canOnlyBeUsedAfter($date);
                }

                if ($claim === 'exp') {
                    $builder = $builder->expiresAt($date);
                }
            }
        }

        return new JsonWebToken(
            $builder->getToken($config->signer(), $config->signingKey())->toString()
        );
    }

    public static function createWithInvalidSignature(): JsonWebToken
    {
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file(__DIR__ . '/samples/private-invalid.pem', 'secret')
        );

        return new JsonWebToken(
            $config->builder()->getToken($config->signer(), $config->signingKey())->toString()
        );
    }

    public static function getPublicKey(): string
    {
        return SampleFiles::read(__DIR__ . '/samples/public.pem');
    }
}
