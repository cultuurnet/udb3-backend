<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use PHPUnit\Framework\TestCase;

trait AssertApiProblemExceptionTrait
{
    private function assertCallableThrowsApiProblemException(ApiProblem $expectedApiProblem, callable $callback): void
    {
        /** @var TestCase $this */
        try {
            $callback();
            $this->fail('No ' . ApiProblemException::class . ' thrown');
        } catch (ApiProblemException $e) {
            $this->assertEquals($expectedApiProblem, $e->getApiProblem());
        }
    }
}
