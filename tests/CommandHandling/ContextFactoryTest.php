<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use PHPUnit\Framework\TestCase;

class ContextFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_a_jwt_context_value(): void
    {
        $jsonWebToken = JsonWebTokenFactory::createWithClaims([]);
        $context = ContextFactory::createContext(null, $jsonWebToken);

        $encodedContext = base64_encode(serialize($context));
        $decodedContext = unserialize(base64_decode($encodedContext));

        $this->assertEquals($context, $decodedContext);
    }
}
