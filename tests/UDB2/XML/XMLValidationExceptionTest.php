<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XML;

use PHPUnit\Framework\TestCase;

class XMLValidationExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_like_any_exception()
    {
        $message = 'Oops';
        $code = 500;
        $previous = new \Exception();

        $exception = new XMLValidationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    /**
     * @test
     */
    public function it_can_instantiated_from_an_array_of_xml_validation_errors()
    {
        $errors = [
            new XMLValidationError('Opening and ending tag mismatch: cdbxml line 2 and foo', 56, 0),
            new XMLValidationError('Some other error', 32, 7),
        ];

        $code = 500;
        $previous = new \Exception();

        $expectedMessage = 'Opening and ending tag mismatch: cdbxml line 2 and foo (Line: 56, column: 0)' . PHP_EOL .
            'Some other error (Line: 32, column: 7)';

        $exception = XMLValidationException::fromXMLValidationErrors($errors, $code, $previous);

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }
}
