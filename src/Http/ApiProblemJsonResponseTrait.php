<?php

namespace CultuurNet\UDB3\Http;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiProblemJsonResponseTrait
{
    /**
     * @param string $message
     * @param string $cdbid
     * @return ApiProblemJsonResponse
     */
    private function createApiProblemJsonResponseNotFound($message, $cdbid)
    {
        return $this->createApiProblemJsonResponse(
            $message,
            $cdbid,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param string $message
     * @param string $cdbid
     * @return ApiProblemJsonResponse
     */
    private function createApiProblemJsonResponseGone($message, $cdbid)
    {
        return $this->createApiProblemJsonResponse(
            $message,
            $cdbid,
            Response::HTTP_GONE
        );
    }

    /**
     * @param string $message
     * @param string $cdbid
     * @param int $statusCode
     * @return ApiProblemJsonResponse
     */
    private function createApiProblemJsonResponse($message, $cdbid, $statusCode)
    {
        $apiProblem = new ApiProblem(
            sprintf(
                $message,
                $cdbid
            )
        );
        $apiProblem->setStatus($statusCode);

        return new ApiProblemJsonResponse($apiProblem);
    }
}
