<?php

namespace CultuurNet\UDB3\Silex\AuditTrail;

use Symfony\Component\HttpFoundation\Request;

class RequestLogger extends AuditTrailLogger
{

    public function logRequest(Request $request)
    {
        if (!$this->requestNeedsToBeLogged($request)) {
            return;
        }

        $contextValues = $this->addToContextBasedOnContentType($request);

        $this->logger->info('Incoming request', $contextValues);
    }
}
