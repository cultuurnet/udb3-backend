<?php

namespace CultuurNet\UDB3\Silex\AuditTrail;

use Symfony\Component\HttpFoundation\Request;

class ErrorLogger extends AuditTrailLogger
{
    public function logError(\Exception $e, Request $request, $code)
    {
        if (!$this->requestNeedsToBeLogged($request)) {
            return;
        }

        $contextValues = $this->addToContextBasedOnContentType($request);
        $contextValues['exception'] = $e->getMessage();
        $contextValues['code'] = $code;

        $this->logger->error('Error message', $contextValues);
    }
}
