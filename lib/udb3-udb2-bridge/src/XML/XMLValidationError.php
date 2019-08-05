<?php

namespace CultuurNet\UDB3\UDB2\XML;

class XMLValidationError
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $lineNumber;

    /**
     * @var int
     */
    private $columnNumber;

    /**
     * @param string $message
     * @param int $lineNumber
     * @param int $columnNumber
     */
    public function __construct(
        $message,
        $lineNumber,
        $columnNumber
    ) {
        $this->message = (string) $message;
        $this->lineNumber = (int) $lineNumber;
        $this->columnNumber = (int) $columnNumber;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * @return int
     */
    public function getColumnNumber()
    {
        return $this->columnNumber;
    }
}
