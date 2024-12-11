<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class UuidGenerationFactoryTest extends TestCase
{
    /** @test */
    public function returns_a_valid_uuid(): void
    {
        $factory = new GeneratedUuidFactory();
        $this->assertInstanceOf(Uuid::class, $factory->uuid4());
    }
}
