<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use PHPUnit\Framework\TestCase;
use TypeError;

class SpecificationCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_accept_objects_of_type_specification_class(): void
    {
        $specification = $this->createMock(SpecificationInterface::class);

        $specifications = new SpecificationCollection();
        $specifications = $specifications->with($specification);

        $this->assertTrue($specifications->contains($specification));
    }

    /**
     * @test
     */
    public function it_does_accept_objects_of_subclass_type_specification(): void
    {
        $payloadSpecification = $this->createMock(PayloadIsInstanceOf::class);

        $specifications = new SpecificationCollection();
        $specifications = $specifications->with($payloadSpecification);

        $this->assertTrue($specifications->contains($payloadSpecification));
    }

    /**
     * @test
     */
    public function it_does_throws_invalid_argument_exception_for_wrong_types(): void
    {
        $this->expectException(TypeError::class);

        $specifications = new SpecificationCollection();
        $specifications->with($this->createMock(\JsonSerializable::class));
    }
}
