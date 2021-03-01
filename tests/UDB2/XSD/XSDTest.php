<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XSD;

use PHPUnit\Framework\TestCase;

class XSDTest extends TestCase
{
    /**
     * @var string
     */
    private $xsdString;

    /**
     * @var XSD
     */
    private $xsd;

    public function setUp()
    {
        $this->xsdString = file_get_contents(__DIR__ . '/samples/CdbXSD.3.3.xsd.xml');
        $this->xsd = new XSD($this->xsdString);
    }

    /**
     * @test
     */
    public function it_returns_the_xsd_content()
    {
        $this->assertEquals($this->xsdString, $this->xsd->getContent());
    }

    /**
     * @test
     */
    public function it_can_be_cast_to_a_string()
    {
        $this->assertEquals($this->xsdString, (string) $this->xsd);
    }
}
