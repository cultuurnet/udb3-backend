<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use PHPUnit\Framework\TestCase;

class SimpleDeserializerLocatorTest extends TestCase
{
    protected SimpleDeserializerLocator $deserializerLocator;

    public function setUp(): void
    {
        $this->deserializerLocator = new SimpleDeserializerLocator();
    }

    public function testGivesBackDeserializerThatWasRegistered(): void
    {
        /** @var DeserializerInterface $firstDeserializer */
        $firstDeserializer = $this->createMock(DeserializerInterface::class);
        /** @var DeserializerInterface $anotherDeserializer */
        $anotherDeserializer = $this->createMock(DeserializerInterface::class);

        $this->deserializerLocator->registerDeserializer(
            'application/vnd.cultuurnet.foo',
            $firstDeserializer
        );

        $this->deserializerLocator->registerDeserializer(
            'application/vnd.cultuurnet.bar',
            $anotherDeserializer
        );

        $this->assertSame(
            $firstDeserializer,
            $this->deserializerLocator->getDeserializerForContentType(
                'application/vnd.cultuurnet.foo'
            )
        );

        $this->assertSame(
            $anotherDeserializer,
            $this->deserializerLocator->getDeserializerForContentType(
                'application/vnd.cultuurnet.bar'
            )
        );
    }

    public function testThrowsExceptionWhenDeserializerCanNotBeFound(): void
    {
        $this->expectException(DeserializerNotFoundException::class);

        $this->deserializerLocator->getDeserializerForContentType(
            'application/vnd.cultuurnet.something-else'
        );
    }
}
