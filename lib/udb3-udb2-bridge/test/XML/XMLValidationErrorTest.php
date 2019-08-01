<?php

namespace CultuurNet\UDB3\UDB2\XML;

class XMLValidationErrorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_contains_a_readable_message_and_a_line_number_and_column_number()
    {
        $message = 'Opening and ending tag mismatch: titles line 4 and title';
        $line = 4;
        $column = 46;

        $error = new XMLValidationError($message, $line, $column);

        $this->assertEquals($message, $error->getMessage());
        $this->assertEquals($line, $error->getLineNumber());
        $this->assertEquals($column, $error->getColumnNumber());
    }
}
