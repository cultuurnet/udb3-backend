<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JwtAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @param Request $request
     *   The request that resulted in an AuthenticationException
     *
     * @param AuthenticationException $authException
     *   The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $responseData = ['error' => 'Unauthorized'];

        if (!is_null($authException)) {
            $responseData['details'] = $authException->getMessage();
        }

        return JsonResponse::create($responseData, 401);
    }
}
