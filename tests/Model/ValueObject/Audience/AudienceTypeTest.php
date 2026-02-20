<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

final class AudienceTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_four_allowed_values(): void
    {
        $this->assertEquals(
            [
                AudienceType::everyone()->toString(),
                AudienceType::members()->toString(),
                AudienceType::education()->toString(),
                AudienceType::childrenOnly()->toString(),
            ],
            AudienceType::getAllowedValues()
        );
    }
}
