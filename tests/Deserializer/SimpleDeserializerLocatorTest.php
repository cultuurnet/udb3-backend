<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SimpleDeserializerLocatorTest extends TestCase
{
    /**
     * @var SimpleDeserializerLocator
     */
    protected $deserializerLocator;

    public function setUp()
    {
        $this->deserializerLocator = new SimpleDeserializerLocator();
    }

    public function testGivesBackDeserializerThatWasRegistered()
    {
        /** @var DeserializerInterface $firstDeserializer */
        $firstDeserializer = $this->createMock(DeserializerInterface::class);
        /** @var DeserializerInterface $anotherDeserializer */
        $anotherDeserializer = $this->createMock(DeserializerInterface::class);

        $this->deserializerLocator->registerDeserializer(
            new StringLiteral('application/vnd.cultuurnet.foo'),
            $firstDeserializer
        );

        $this->deserializerLocator->registerDeserializer(
            new StringLiteral('application/vnd.cultuurnet.bar'),
            $anotherDeserializer
        );

        $this->assertSame(
            $firstDeserializer,
            $this->deserializerLocator->getDeserializerForContentType(
                new StringLiteral('application/vnd.cultuurnet.foo')
            )
        );

        $this->assertSame(
            $anotherDeserializer,
            $this->deserializerLocator->getDeserializerForContentType(
                new StringLiteral('application/vnd.cultuurnet.bar')
            )
        );
    }

    public function testThrowsExceptionWhenDeserializerCanNotBeFound()
    {
        $this->expectException(DeserializerNotFoundException::class);

        $this->deserializerLocator->getDeserializerForContentType(
            new StringLiteral('application/vnd.cultuurnet.something-else')
        );
    }
}
