<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

trait AssertApiProblemTrait
{
    private function assertCallableThrowsApiProblem(ApiProblem $expectedApiProblem, callable $callback): void
    {
        try {
            $callback();
            $this->fail('No ' . ApiProblem::class . ' thrown');
        } catch (ApiProblem $e) {
            $this->assertEquals($expectedApiProblem, $e);
        } catch (ConvertsToApiProblem $e) {
            $this->assertEquals($expectedApiProblem, $e->toApiProblem());
        }
    }
}
