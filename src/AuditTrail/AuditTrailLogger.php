<?php

namespace CultuurNet\UDB3\Silex\AuditTrail;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AuditTrailLogger {

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    protected function requestNeedsToBeLogged(Request $request): bool {
        if ($request->getMethod() == Request::METHOD_POST ||
            $request->getMethod() == Request::METHOD_PUT ||
            $request->getMethod() == Request::METHOD_PATCH ||
            $request->getMethod() == Request::METHOD_DELETE) {
            return TRUE;
        }

        return FALSE;
    }

    protected function addToContextBasedOnContentType(
        Request $request
    ): array {

        $contextValues = [];

        if ($request->getContentType() == 'json') {
            if (!empty($request->getContent())) {
                $contextValues['request']['headers'] = $request->headers->all();
                $contextValues['request']['payload'] = json_decode($request->getContent());
            }
        }

        return $contextValues;
    }
}
