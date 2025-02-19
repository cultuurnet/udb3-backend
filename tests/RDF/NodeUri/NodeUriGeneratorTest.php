<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

use PHPUnit\Framework\TestCase;

class NodeUriGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_generate_a_node_uri(): void
    {
        $generator = $this->createMock(HashGenerator::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with(['a', 'b', 'c'])
            ->willReturn('abc');

        $nodeUriGenerator = new NodeUriGenerator($generator);
        $uri = $nodeUriGenerator->generate('address', ['a', 'b', 'c']);

        $this->assertEquals('#address-abc', $uri);
    }

    /**
     * @test
     */
    public function it_should_generate_a_node_uri_with_stripped_nodename(): void
    {
        $generator = $this->createMock(HashGenerator::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with(['a', 'b', 'c'])
            ->willReturn('abc');

        $nodeUriGenerator = new NodeUriGenerator($generator);
        $uri = $nodeUriGenerator->generate('schema:event:address', ['a', 'b', 'c']);

        $this->assertEquals('#address-abc', $uri);
    }

    /**
     * @test
     */
    public function it_should_generate_a_node_uri_with_lower_camel_case(): void
    {
        $generator = $this->createMock(HashGenerator::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with(['a', 'b', 'c'])
            ->willReturn('abc');

        $nodeUriGenerator = new NodeUriGenerator($generator);
        $uri = $nodeUriGenerator->generate('AddressDetails', ['a', 'b', 'c']);

        $this->assertEquals('#addressDetails-abc', $uri);
    }
}
