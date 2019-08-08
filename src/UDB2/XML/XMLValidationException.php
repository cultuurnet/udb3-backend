<?php

namespace CultuurNet\UDB3\UDB2\XML;

class XMLValidationException extends \Exception
{
    /**
     * @param XMLValidationError[] $xmlValidationErrors
     * @param int $code
     * @param \Exception $previous
     * @return XMLValidationException
     */
    public static function fromXMLValidationErrors(array $xmlValidationErrors, $code = 0, \Exception $previous = null)
    {
        $errorMessages = array_map(
            function (XMLValidationError $xmlValidationError) {
                return sprintf(
                    '%s (Line: %d, column: %d)',
                    $xmlValidationError->getMessage(),
                    $xmlValidationError->getLineNumber(),
                    $xmlValidationError->getColumnNumber()
                );
            },
            $xmlValidationErrors
        );

        $exceptionMessage = implode(PHP_EOL, $errorMessages);

        return new static($exceptionMessage, $code, $previous);
    }
}
