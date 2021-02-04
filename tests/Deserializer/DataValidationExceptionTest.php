<?php

namespace CultuurNet\Deserializer;

class DataValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itHasAListOfValidationMessages()
    {
        $messages = [
            'Field foo should not be empty.',
            'Field bar should be an integer, string given.'
        ];

        $exception = new DataValidationException();
        $exception->setValidationMessages($messages);

        $this->assertEquals($messages, $exception->getValidationMessages());
    }

    /**
     * @test
     */
    public function itHasAnEmptyListOfValidationMessagesByDefault()
    {
        $exception = new DataValidationException();
        $this->assertEquals([], $exception->getValidationMessages());
    }
}
