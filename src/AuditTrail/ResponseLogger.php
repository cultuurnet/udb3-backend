<?php

namespace CultuurNet\UDB3\Silex\AuditTrail;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseLogger extends AuditTrailLogger {

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function logResponse(Request $request, Response $response) {
        if (!$this->requestNeedsToBeLogged($request)) {
            return;
        }

        $contextValues = $this->addResponsePayload($response);

        $this->logger->info('Outgoing response', $contextValues);
    }

    private function addResponsePayload(Response $response): array {
        $contextValues = [];

        if (!empty($response->getContent())) {
            $contextValues['response']['headers'] = $response->headers->all();
            $contextValues['response']['payload'] = json_decode($response->getContent());
        }

        return $contextValues;
    }

}
