<?php

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
use Lcobucci\JWT\ValidationData;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class JwtDecoderServiceTest extends TestCase
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
     * @var ValidationData
     */
    private $validationData;

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
     * @var JwtDecoderService
     */
    private $decoderService;

    public function setUp()
    {
        $this->publicKeyString = file_get_contents(__DIR__ . '/samples/public.pem');

        $this->tokenString = rtrim(
            file_get_contents(__DIR__ . '/samples/token.txt'),
            '\\r\\n'
        );

        $this->tokenHeaders = [
            "typ" => "JWT",
            "alg" => "RS256",
        ];

        $this->tokenClaims = [
            "uid" => "1",
            "nick" => "foo",
            "email" => "foo@bar.com",
            "iss" => "http://culudb-jwt-provider.dev",
            "iat" => "1461829061",
            "exp" => "1461829061",
            "nbf" => "1461829061",
        ];

        $this->tokenClaimsAsValueObjects = [
            "uid" => new Basic('uid', '1'),
            "nick" => new Basic('nick', 'foo'),
            "email" => new Basic('email', 'foo@bar.com'),
            "iss" => new EqualsTo('iss', 'http://culudb-jwt-provider.dev'),
            "iat" =>  new LesserOrEqualsTo('iat', '1461829061'),
            "exp" =>  new GreaterOrEqualsTo('exp', '1461829061'),
            "nbf" => new LesserOrEqualsTo('nbf', '1461829061'),
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

        $this->validationData = new ValidationData();
        $this->validationData->setIssuer("http://culudb-jwt-provider.dev");

        $this->signer = new Sha256();
        $this->publicKey = new Key($this->publicKeyString);

        $this->requiredCLaims = [
            'uid',
            'nick',
            'email',
        ];

        $this->decoderService = new JwtDecoderService(
            $this->parser,
            $this->validationData,
            $this->signer,
            $this->publicKey,
            $this->requiredCLaims
        );
    }

    /**
     * @test
     */
    public function it_can_parse_a_jwt_string_into_a_token_object_and_read_its_contents()
    {
        $actualToken = $this->decoderService->parse(
            new StringLiteral($this->tokenString)
        );

        $this->assertEquals($this->token, $actualToken);
    }

    /**
     * @test
     */
    public function it_can_validate_a_token()
    {
        // Test token should have been expired.
        $this->assertFalse(
            $this->decoderService->validateData($this->token)
        );

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

        $this->assertTrue(
            $this->decoderService->validateData(new Udb3Token($unexpiredToken))
        );

        // Change the iss claim of the unexpired token, which should cause
        // validation to fail again.
        $manipulatedClaims['iss'] = new EqualsTo('exp', 'http://hooli.com');

        $unexpiredTokenWithDifferentIssuer = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $this->assertFalse(
            $this->decoderService->validateData(new Udb3Token($unexpiredTokenWithDifferentIssuer))
        );
    }

    /**
     * @test
     */
    public function it_can_validate_that_a_token_has_all_required_claims()
    {
        $decoderWithoutRequiredClaims = new JwtDecoderService(
            $this->parser,
            $this->validationData,
            $this->signer,
            $this->publicKey
        );

        // Mock a missing nick claim.
        // This token will not have a valid signature, but claim validation does
        // not take the signature into account.
        $manipulatedClaims = $this->tokenClaimsAsValueObjects;
        unset($manipulatedClaims['nick']);

        $tokenWithoutNick = new Token(
            $this->tokenHeaders,
            $manipulatedClaims,
            $this->signature,
            $this->payload
        );

        $this->assertTrue($decoderWithoutRequiredClaims->validateRequiredClaims($this->token));
        $this->assertTrue($decoderWithoutRequiredClaims->validateRequiredClaims(new Udb3Token($tokenWithoutNick)));
        $this->assertTrue($this->decoderService->validateRequiredClaims($this->token));
        $this->assertFalse($this->decoderService->validateRequiredClaims(new Udb3Token($tokenWithoutNick)));
    }

    /**
     * @test
     */
    public function it_can_verify_a_token_signature()
    {
        $this->assertTrue(
            $this->decoderService->verifySignature(
                new Udb3Token(
                    $this->parser->parse($this->tokenString)
                )
            )
        );

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

        $this->assertFalse(
            $this->decoderService->verifySignature(
                new Udb3Token(
                    $this->parser->parse($manipulatedTokenString)
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_checks_that_the_required_claims_are_strings()
    {
        $required = [
            new Basic('uid', null),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All required claims should be strings.');

        new JwtDecoderService(
            $this->parser,
            $this->validationData,
            $this->signer,
            $this->publicKey,
            $required
        );
    }

    /**
     * @test
     */
    public function it_rethrows_a_jwtparserexception_when_parse_fails()
    {
        $this->expectException(JwtParserException::class);

        $this->decoderService = new JwtDecoderService(
            $this->parser,
            $this->validationData,
            $this->signer,
            $this->publicKey
        );

        $this->tokenString = new StringLiteral(str_repeat(rtrim(
            file_get_contents(__DIR__ . '/samples/token.txt'),
            '\\r\\n'
        ), 2));

        $this->decoderService->parse($this->tokenString);
    }
}
