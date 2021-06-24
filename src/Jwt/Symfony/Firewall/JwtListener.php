<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Firewall;

use CultuurNet\UDB3\HttpFoundation\Response\ForbiddenResponse;
use CultuurNet\UDB3\HttpFoundation\Response\UnauthorizedResponse;
use CultuurNet\UDB3\Jwt\JwtParserException;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtUserToken;
use CultuurNet\UDB3\Jwt\JwtDecoderServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class JwtListener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var JwtDecoderServiceInterface
     */
    private $decoderService;


    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        JwtDecoderServiceInterface $decoderService
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->decoderService = $decoderService;
    }


    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $jwtString = $this->getJwtString($request);

        if (empty($jwtString)) {
            return;
        }

        try {
            $jwt = $this->decoderService->parse(new StringLiteral($jwtString));
        } catch (JwtParserException $e) {
            $response = new UnauthorizedResponse('Could not parse the given JWT.');
            $event->setResponse($response);
            return;
        }

        $token = new JwtUserToken($jwt);

        try {
            $authenticatedToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authenticatedToken);
        } catch (AuthenticationException $e) {
            $response = new UnauthorizedResponse($e->getMessage());
            if ($e->getCode() === 403) {
                $response = new ForbiddenResponse($e->getMessage());
            }
            $event->setResponse($response);
        }
    }

    /**
     * @return null|string
     */
    private function getJwtString(Request $request)
    {
        $authorization = $request->headers->get('authorization');
        $bearerPrefix = 'Bearer ';

        if (!$authorization || strpos($authorization, $bearerPrefix) !== 0) {
            return null;
        }

        return substr($authorization, strlen($bearerPrefix));
    }
}
