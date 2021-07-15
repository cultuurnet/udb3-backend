<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Firewall;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\TokenFactory;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class JwtListener implements ListenerInterface
{
    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    public function __construct(
        TokenFactory $tokenFactory,
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $jwtString = $this->getJwtString($request);

        if (empty($jwtString)) {
            return;
        }

        try {
            $token = $this->tokenFactory->createFromJwtString($jwtString);
            $authenticatedToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authenticatedToken);
        } catch (InvalidArgumentException $e) {
            $response = new ApiProblemJsonResponse(
                ApiProblems::unauthorized('Could not parse the given JWT.')
            );
            $event->setResponse($response);
            return;
        } catch (AuthenticationException $e) {
            $response = new ApiProblemJsonResponse(
                ApiProblems::unauthorized($e->getMessage())
            );
            if ($e->getCode() === 403) {
                $response = new ApiProblemJsonResponse(
                    ApiProblems::forbidden($e->getMessage())
                );
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
