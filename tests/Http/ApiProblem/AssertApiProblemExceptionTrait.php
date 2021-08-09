<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use PHPUnit\Framework\TestCase;

trait AssertApiProblemExceptionTrait
{
    private function assertCallableThrowsApiProblem(ApiProblem $expectedApiProblem, callable $callback): void
    {
        /** @var TestCase $this */
        try {
            $callback();
            $this->fail('No ' . ApiProblem::class . ' thrown');
        } catch (ApiProblem $e) {
            $this->assertEquals($expectedApiProblem, $e);
        }
    }
}
