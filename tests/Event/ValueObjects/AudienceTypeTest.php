<?php

namespace CultuurNet\UDB3\Event\ValueObjects;

use PHPUnit\Framework\TestCase;

class AudienceTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_instantiate_audience_type_enums_by_a_name()
    {
        $options = AudienceType::getConstants();

        $this->assertEquals(
            [
                AudienceType::EVERYONE()->getName() => AudienceType::EVERYONE(),
                AudienceType::MEMBERS()->getName() => AudienceType::MEMBERS(),
                AudienceType::EDUCATION()->getName() => AudienceType::EDUCATION(),
            ],
            $options
        );
    }
}
