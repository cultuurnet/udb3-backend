<?php

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

class AudienceTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_three_possible_values()
    {
        $everyone = AudienceType::everyone();
        $members = AudienceType::members();
        $education = AudienceType::education();

        $this->assertEquals('everyone', $everyone->toString());
        $this->assertEquals('members', $members->toString());
        $this->assertEquals('education', $education->toString());
    }
}
