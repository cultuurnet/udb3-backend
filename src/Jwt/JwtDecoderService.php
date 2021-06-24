<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;
use ValueObjects\StringLiteral\StringLiteral;

class JwtDecoderService implements JwtDecoderServiceInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Key
     */
    private $publicKey;

    /**
     * @var string[]
     */
    private $requiredClaims;

    /**
     * @var string[]
     */
    private $validIssuers;

    /**
     * @param string[] $requiredClaims
     * @param string[] $validIssuers
     */
    public function __construct(
        Parser $parser,
        Signer $signer,
        Key $publicKey,
        array $requiredClaims = [],
        array $validIssuers = []
    ) {
        $this->parser = $parser;
        $this->signer = $signer;
        $this->publicKey = $publicKey;
        $this->requiredClaims = $requiredClaims;
        $this->validIssuers = $validIssuers;

        if (count($requiredClaims) !== count(array_filter($this->requiredClaims, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All required claims should be strings.'
            );
        }

        if (count($validIssuers) !== count(array_filter($this->validIssuers, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All valid issuers should be strings.'
            );
        }
    }

    public function parse(StringLiteral $tokenString): Udb3Token
    {
        try {
            $token = $this->parser->parse($tokenString->toNative());
            return new Udb3Token($token);
        } catch (\InvalidArgumentException $e) {
            throw new JwtParserException($e);
        }
    }

    public function validateTimeSensitiveClaims(Udb3Token $udb3Token): bool
    {
        return $udb3Token->jwtToken()->validate(new ValidationData());
    }

    public function validateRequiredClaims(Udb3Token $udb3Token): bool
    {
        foreach ($this->requiredClaims as $claim) {
            if (!$udb3Token->jwtToken()->hasClaim($claim)) {
                return false;
            }
        }

        return true;
    }

    public function validateIssuer(Udb3Token $udb3Token): bool
    {
        $jwt = $udb3Token->jwtToken();

        if (!$jwt->hasClaim('iss')) {
            return false;
        }

        $issuer = $jwt->getClaim('iss');
        return in_array($issuer, $this->validIssuers, true);
    }

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        return $udb3Token->jwtToken()->verify(
            $this->signer,
            $this->publicKey
        );
    }
}
