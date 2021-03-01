<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ClassNameCommandFilterTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_true_when_class_name_matches()
    {
        $classNameCommandFilter = new ClassNameCommandFilter(
            new StringLiteral(DummyCommand::class)
        );

        $this->assertTrue($classNameCommandFilter->matches(new DummyCommand()));
    }

    /**
     * @test
     */
    public function it_returns_false_when_class_name_does_not_match()
    {
        $classNameCommandFilter = new ClassNameCommandFilter();

        $this->assertFalse($classNameCommandFilter->matches(new DummyCommand()));
    }
}
