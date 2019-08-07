<?php

namespace CultuurNet\UDB3\UDB2\XSD;

class CachedInMemoryXSDReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XSD
     */
    private $xsd;

    /**
     * @var XSDReaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedReader;

    /**
     * @var CachedInMemoryXSDReader
     */
    private $cachedReader;

    public function setUp()
    {
        $this->xsd = new XSD(file_get_contents(__DIR__ . '/samples/CdbXSD.3.3.xsd.xml'));
        $this->decoratedReader = $this->createMock(XSDReaderInterface::class);
        $this->cachedReader = new CachedInMemoryXSDReader($this->decoratedReader);
    }

    /**
     * @test
     */
    public function it_only_reads_from_the_decorated_reader_once_and_returns_a_cached_xsd_afterwards()
    {
        $this->decoratedReader->expects($this->exactly(1))
            ->method('read')
            ->willReturn($this->xsd);

        $firstXsd = $this->cachedReader->read();
        $secondXsd = $this->cachedReader->read();

        $this->assertEquals($this->xsd, $firstXsd);
        $this->assertEquals($this->xsd, $secondXsd);
    }
}
