<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation;

use Respect\Validation\Exceptions\NestedValidationException;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator;

abstract class ValidatorTestCase extends TestCase
{
    protected function assertValidationErrors($data, array $expectedMessages): void
    {
        try {
            $this->getValidator()->assert($data);
            $this->fail('No error messages found.');
        } catch (NestedValidationException $e) {
            $actualMessages = $e->getMessages();
            $this->assertEquals($expectedMessages, $actualMessages);
        }
    }

    abstract protected function getValidator(): Validator;
}
