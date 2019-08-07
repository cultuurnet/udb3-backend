<?php

namespace CultuurNet\UDB3\UDB2\XSD;

use PHPUnit\Framework\TestCase;

class FileGetContentsXSDReaderTest extends TestCase
{
    /**
     * @test
     */
    public function it_reads_the_xsd_from_a_provided_file_location()
    {
        $file = __DIR__ . '/samples/CdbXSD.3.3.xsd.xml';
        $reader = new FileGetContentsXSDReader($file);

        $expected = new XSD(file_get_contents($file));
        $actual = $reader->read();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_it_failed_to_get_the_contents_of_the_provided_file()
    {
        $file = __DIR__ . '/does/not/exist.xsd.xml';
        $reader = new FileGetContentsXSDReader($file);

        $this->setExpectedException(\RuntimeException::class, 'Could not read XSD file from ' . $file);

        $reader->read();
    }
}
