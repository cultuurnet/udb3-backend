<?php

namespace CultuurNet\UDB3\Silex\AuditTrail;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtUserToken;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class TokenStorageProcessor implements ProcessorInterface
{
    protected $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return array The processed records
     */
    public function __invoke(array $records)
    {
        $authToken = $this->tokenStorage->getToken();

        if ($authToken instanceof JwtUserToken && $authToken->isAuthenticated()) {
            $jwt = $authToken->getCredentials();
            $records['token_storage']['user_id'] = $jwt->id();
            $records['token_storage']['user_nick'] = $jwt->userName();
            $records['token_storage']['user_email'] = $jwt->email();
        }

        return $records;
    }
}
