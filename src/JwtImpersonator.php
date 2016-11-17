<?php

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Jwt\JwtDecoderService;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\ValidationData;
use Silex\Application;
use ValueObjects\String\String as StringLiteral;

class JwtImpersonator
{
    /**
     * @var JwtDecoderService
     */
    private $jwtDecoderService;

    /**
     * AMQPImpersonator constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $data = new ValidationData();
        $data->setIssuer($app['config']['jwt']['validation']['iss']);

        $file = __DIR__ . '/../' . $app['config']['jwt']['keys']['public']['file'];
        $key = new Key('file://' . $file);

        $jwtDecoderService = new JwtDecoderService(
            new Parser(),
            $data,
            new Sha256(),
            $key
        );

        $this->jwtDecoderService = $jwtDecoderService;
    }

    /**
     * @param Impersonator $impersonator
     * @param StringLiteral $jwt
     */
    public function updateImpersonator(
        Impersonator $impersonator,
        StringLiteral $jwt
    ) {
        $token = $this->jwtDecoderService->parse($jwt);

        $impersonator->impersonate(
            new Metadata(
                [
                    'user_id' => $token->getClaim('uid'),
                    'user_nick' => $token->getClaim('nick'),
                    'user_email' => $token->getClaim('email'),
                    'auth_jwt' => $jwt->toNative(),
                    'uitid_token_credentials' => null,
                ]
            )
        );
    }
}
