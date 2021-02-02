<?php

namespace CultuurNet\UDB3\Jwt;

use ValueObjects\StringLiteral\StringLiteral;

interface JwtDecoderServiceInterface
{
    public function parse(StringLiteral $tokenString) : Udb3Token;

    public function validateData(Udb3Token $jwt) : bool;

    public function validateRequiredClaims(Udb3Token $udb3Token) : bool;

    public function verifySignature(Udb3Token $udb3Token) : bool;
}
