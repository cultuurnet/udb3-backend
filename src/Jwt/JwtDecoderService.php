<?php

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
     * @var ValidationData
     */
    private $validationData;

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
     * @param string[] $requiredClaims
     */
    public function __construct(
        Parser $parser,
        ValidationData $validationData,
        Signer $signer,
        Key $publicKey,
        array $requiredClaims = []
    ) {
        $this->parser = $parser;
        $this->validationData = $validationData;
        $this->signer = $signer;
        $this->publicKey = $publicKey;
        $this->requiredClaims = $requiredClaims;

        if (count($requiredClaims) !== count(array_filter($this->requiredClaims, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All required claims should be strings.'
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

    public function validateData(Udb3Token $udb3Token): bool
    {
        return $udb3Token->jwtToken()->validate($this->validationData);
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

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        return $udb3Token->jwtToken()->verify(
            $this->signer,
            $this->publicKey
        );
    }
}
