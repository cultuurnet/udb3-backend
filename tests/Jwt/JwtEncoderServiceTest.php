<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Clock\FrozenClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;
use ValueObjects\Number\Integer as IntegerLiteral;

class JwtEncoderServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $tokenString;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Sha256
     */
    private $signer;

    /**
     * @var string
     */
    private $keyString;

    /**
     * @var Key
     */
    private $key;

    /**
     * @var FrozenClock
     */
    private $clock;

    /**
     * @var IntegerLiteral
     */
    private $exp;

    /**
     * @var IntegerLiteral
     */
    private $nbf;

    /**
     * @var JwtEncoderService
     */
    private $encoderService;

    public function setUp()
    {
        $this->tokenString = rtrim(
            file_get_contents(__DIR__ . '/samples/token.txt'),
            '\\r\\n'
        );

        $this->builder = new Builder();
        $this->builder->setIssuer('http://culudb-jwt-provider.dev');

        $this->signer = new Sha256();
        $this->keyString = file_get_contents(__DIR__ . '/samples/private.pem');
        $this->key = new Key($this->keyString, 'secret');

        $this->clock = new FrozenClock(
            new \DateTime('@1461829061')
        );

        // Test token has the same exp time as nbf time.
        // This wouldn't make sense in real-life but makes testing easier.
        $this->exp = new IntegerLiteral(0);
        $this->nbf = new IntegerLiteral(0);

        $this->encoderService = new JwtEncoderService(
            $this->builder,
            $this->signer,
            $this->key,
            $this->clock,
            $this->exp,
            $this->nbf
        );
    }

    /**
     * @test
     */
    public function it_encodes_claims_to_a_jwt()
    {
        $claims = [
            'uid' => 1,
            'nick' => 'foo',
            'email' => 'foo@bar.com',
        ];

        $jwt = $this->encoderService->encode($claims);

        $this->assertEquals($this->tokenString, (string) $jwt);
    }
}
