<?php

namespace CultuurNet\UDB3\UDB2\XML;

interface XMLValidationServiceInterface
{
    /**
     * @param string $xml
     *   XML source code.
     *
     * @return XMLValidationError[]
     *   All validation errors, if any.
     */
    public function validate($xml);
}
