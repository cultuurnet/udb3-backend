<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Claim\EqualsTo;
use Lcobucci\JWT\Claim\GreaterOrEqualsTo;
use Lcobucci\JWT\Claim\LesserOrEqualsTo;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Parsing\Decoder;
use Lcobucci\JWT\Parsing\Encoder;
use Lcobucci\JWT\Signature;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token as Jwt;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtValidatorTest extends TestCase
{
    /**
     * @var string
     */
    private $publicKeyString;

    /**
     * @var string
     */
    private $tokenString;

    /**
     * @var array
     */
    private $tokenHeaders;

    /**
     * @var array
     */
    private $tokenClaims;

    /**
     * @var array
     */
    private $tokenClaimsAsValueObjects;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var Signature
     */
    private $signature;

    /**
     * @var Udb3Token
     */
    private $token;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Sha256
     */
    private $signer;

    /**
     * @var Key
     */
    private $publicKey;

    /**
     * @var string[]
     */
    private $requiredCLaims;

    /**
     * @var JwtValidator
     */
    private $validator;

    public function setUp()
    {
        $this->publicKeyString = file_get_contents(__DIR__ . '/samples/public.pem');

        $this->tokenString = rtrim(
            file_get_contents(__DIR__ . '/samples/token.txt'),
            '\\r\\n'
        );

        $this->tokenHeaders = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $this->tokenClaims = [
            'sub' => 'auth0|1',
            'iss' => 'https://account-acc.uitid.be',
            'iat' => '1461829061',
            'exp' => '1461829061',
            'nbf' => '1461829061',
        ];

        $this->tokenClaimsAsValueObjects = [
            'sub' => new Basic('sub', 'auth0|1'),
            'iss' => new EqualsTo('iss', 'https://account-acc.uitid.be'),
            'iat' =>  new LesserOrEqualsTo('iat', '1461829061'),
            'exp' =>  new GreaterOrEqualsTo('exp', '1461829061'),
            'nbf' => new LesserOrEqualsTo('nbf', '1461829061'),
        ];

        $this->payload = explode('.', $this->tokenString);

        $decoder = new Decoder();
        $hash = $decoder->base64UrlDecode($this->payload[2]);
        $this->signature = new Signature($hash);

        $this->token = new Udb3Token(
            new Jwt(
                $this->tokenHeaders,
                $this->tokenClaimsAsValueObjects,
                $this->signature,
                $this->payload
            )
        );

        $this->parser = new Parser();

        $this->signer = new Sha256();
        $this->publicKey = new Key($this->publicKeyString);

        $this->requiredCLaims = [
            'sub',
        ];

        $this->validator = new JwtValidator(
            $this->signer,
            $this->publicKey,
            $this->requiredCLaims,
            ['iss1', 'https://account-acc.uitid.be', 'iss2']
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_token_is_expired(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->validator->validateTimeSensitiveClaims($this->token);
    }

    /**
     * @test
     */
    public function it_accepts_a_token_that_has_not_expired(): void
    {
        // Mock a later expiration date.
        // This token will not have a valid signature, but data validation does
        // not take the signature into account.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        $manipulatedClaims['exp'] = new GreaterOrEqualsTo('exp', time() + 3600);

        $unexpiredToken = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $this->validator->validateTimeSensitiveClaims(new Udb3Token($unexpiredToken));
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_accepts_a_token_that_has_all_required_claims(): void
    {
        $validatorWithoutRequiredClaims = new JwtValidator(
            $this->signer,
            $this->publicKey
        );

        // Mock a missing sub claim.
        // This token will not have a valid signature, but claim validation does
        // not take the signature into account.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        unset($manipulatedClaims['sub']);

        $tokenWithoutNick = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $validatorWithoutRequiredClaims->validateRequiredClaims($this->token);
        $this->addToAssertionCount(1);

        $validatorWithoutRequiredClaims->validateRequiredClaims(new Udb3Token($tokenWithoutNick));
        $this->addToAssertionCount(1);

        $this->validator->validateRequiredClaims($this->token);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->validator->validateRequiredClaims(new Udb3Token($tokenWithoutNick));
    }

    /**
     * @test
     */
    public function it_accepts_a_token_that_has_a_valid_issuer_from_an_allowed_list(): void
    {
        $this->validator->validateIssuer($this->token);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_missing(): void
    {
        // Mock a missing iss claim.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        unset($manipulatedClaims['iss']);
        $tokenWithoutIss = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateIssuer(new Udb3Token($tokenWithoutIss));
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_invalid(): void
    {
        // Mock an invalid iss claim.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        $manipulatedClaims['iss'] = new Basic('iss', 'invalid');
        $tokenWithInvalidIss = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateIssuer(new Udb3Token($tokenWithInvalidIss));
    }

    /**
     * @test
     */
    public function it_accepts_a_token_with_a_valid_signature(): void
    {
        $this->validator->verifySignature(
            new Udb3Token(
                $this->parser->parse($this->tokenString)
            )
        );
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_throws_if_the_signature_is_invalid_for_the_payload(): void
    {
        // Change one of the claims, but keep the original header and
        // signature.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        $manipulatedClaims['uid'] = new Basic('uid', '0');
        $encoder = new Encoder();
        $manipulatedPayload = $this->payload;
        $manipulatedPayload[1] = $encoder->base64UrlEncode(
            $encoder->jsonEncode($manipulatedClaims)
        );

        // Re-create the token string using the original header and signature,
        // but with manipulated claims.
        $manipulatedTokenString = implode('.', $manipulatedPayload);

        $this->expectException(AuthenticationException::class);
        $this->validator->verifySignature(
            new Udb3Token(
                $this->parser->parse($manipulatedTokenString)
            )
        );
    }

    /**
     * @test
     */
    public function it_checks_that_the_required_claims_are_strings(): void
    {
        $required = [
            new Basic('uid', null),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All required claims should be strings.');

        new JwtValidator(
            $this->signer,
            $this->publicKey,
            $required
        );
    }
}
