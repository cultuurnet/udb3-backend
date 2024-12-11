<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class FixedUuidFactoryTest extends TestCase
{
    /** @test */
    public function returns_a_valid_fixed_uuid(): void
    {
        $uuid = '384b0ebe-6e28-4183-9b22-80243ee08b0d';
        $factory = new FixedUuidFactory(new Uuid($uuid));
        $this->assertInstanceOf(Uuid::class, $factory->uuid4());
        $this->assertEquals($uuid, $factory->uuid4()->toString());
    }
}
