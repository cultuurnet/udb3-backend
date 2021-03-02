<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token as Jwt;

interface JwtEncoderServiceInterface
{
    /**
     * @param array $claims
     * @return Jwt
     */
    public function encode($claims);
}
