<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class JsonWebTokenFactory
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Key
     */
    private $key;

    public function __construct(string $privateKey, ?string $passphrase = null)
    {
        $this->builder = new Builder();
        $this->signer = new Sha256();
        $this->key = new Key($privateKey, $passphrase);
    }

    public function createWithClaims(array $claims): JsonWebToken
    {
        $builder = clone $this->builder;
        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }
        return new JsonWebToken($builder->getToken($this->signer, $this->key));
    }
}
