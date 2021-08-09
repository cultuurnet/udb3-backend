<?php

namespace CultuurNet\UDB3\Http\ApiProblem;

use PHPUnit\Framework\TestCase;

class ApiProblemTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_minimal_required_properties(): void
    {
        $problem = ApiProblem::internalServerError();

        $this->assertEquals('about:blank', $problem->getType());
        $this->assertEquals('Internal Server Error', $problem->getTitle());
        $this->assertEquals(500, $problem->getStatus());
    }

    /**
     * @test
     */
    public function it_can_have_a_detail(): void
    {
        $problem = ApiProblem::invalidEmailAddress('foo');
        $this->assertEquals('"foo" is not a valid email address', $problem->getDetail());
    }

    /**
     * @test
     */
    public function it_can_have_a_jsonPointer(): void
    {
        $problem = ApiProblem::bodyInvalidData('Property mock should not be empty', '/mock');
        $this->assertEquals('/mock', $problem->getJsonPointer());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_json_array(): void
    {
        $problem = ApiProblem::bodyInvalidData('Property mock should not be empty', '/mock');
        $this->assertEquals(
            [
                'type' => 'https://api.publiq.be/probs/body/invalid-data',
                'title' => 'Invalid body data',
                'status' => 400,
                'detail' => 'Property mock should not be empty',
                'jsonPointer' => '/mock',
            ],
            $problem->toArray()
        );
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_an_exception(): void
    {
        $given = ApiProblem::bodyInvalidData('Property mock should not be empty', '/mock');
        $expected = new ApiProblemException($given);
        $actual = $given->toException();

        $this->assertEquals($expected, $actual);

        $this->expectException(ApiProblemException::class);
        throw $actual;
    }
}
