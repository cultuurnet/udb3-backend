<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Jwt\JsonWebToken;

interface JwtValidator
{
    /**
     * @throws ApiProblem
     */
    public function verifySignature(JsonWebToken $token): void;

    /**
     * @throws ApiProblem
     */
    public function validateClaims(JsonWebToken $token): void;
}
