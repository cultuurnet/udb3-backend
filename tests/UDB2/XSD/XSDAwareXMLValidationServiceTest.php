<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XSD;

use CultuurNet\UDB3\UDB2\XML\XMLValidationError;
use PHPUnit\Framework\TestCase;

class XSDAwareXMLValidationServiceTest extends TestCase
{
    /**
     * @var FileGetContentsXSDReader
     */
    private $xsdReader;

    /**
     * @var XSDAwareXMLValidationService
     */
    private $validationService;

    public function setUp()
    {
        $this->xsdReader = new FileGetContentsXSDReader(__DIR__ . '/samples/CdbXSD.3.3.xsd.xml');
        $this->validationService = new XSDAwareXMLValidationService($this->xsdReader);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_for_valid_xml_that_conforms_to_the_xsd()
    {
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_valid.xml');
        $errors = $this->validationService->validate($xml);
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_for_valid_prefixed_xml_that_conforms_to_the_xsd()
    {
        $xml = file_get_contents(__DIR__ . '/samples/actor_3.3_valid_prefixed.xml');
        $errors = $this->validationService->validate($xml);
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_xml_validation_errors()
    {
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_invalid_format.xml');

        // We assert the error messages here instead of the complete errors
        // with line and column numbers, because the line number of the second
        // error could either be -1 or 0 depending on the environment.
        $expectedErrorMessages = [
            'Opening and ending tag mismatch: cdbxml line 2 and oops',
            'The document has no document element.',
        ];

        $actualErrors = $this->validationService->validate($xml);

        $this->assertEquals($expectedErrorMessages, $this->getErrorMessages($actualErrors));
    }

    /**
     * @test
     */
    public function it_returns_xml_validation_errors_for_prefixed_xml()
    {
        $xml = file_get_contents(__DIR__ . '/samples/actor_3.3_invalid_format_prefixed.xml');

        // We assert the error messages here instead of the complete errors
        // with line and column numbers, because the line number of the second
        // error could either be -1 or 0 depending on the environment.
        $expectedErrorMessages = [
            'Opening and ending tag mismatch: cdbxml line 2 and foo',
            'The document has no document element.',
        ];

        $actualErrors = $this->validationService->validate($xml);

        $this->assertEquals($expectedErrorMessages, $this->getErrorMessages($actualErrors));
    }

    /**
     * @test
     */
    public function it_returns_xsd_validation_errors()
    {
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_missing_contactinfo.xml');

        // @codingStandardsIgnoreStart
        // The following error message is not very clear, but seems to be
        // consistent with other XSD validator services that use libxml.
        $expectedMessage = "Element '{http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}event': Missing child element(s). Expected is one of ( {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}activities, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}agefrom, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}bookingperiod, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}comments, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}contactinfo, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}eventrelations, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}keywords, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}languages, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}maxparticipants, {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}organiser ).";
        // @codingStandardsIgnoreEnd

        $expectedErrors = [
            new XMLValidationError($expectedMessage, 3, 0),
        ];

        $actualErrors = $this->validationService->validate($xml);

        $this->assertEquals($expectedErrors, $actualErrors);
    }

    /**
     * @test
     */
    public function it_returns_xsd_validation_errors_for_prefixed_xml()
    {
        $xml = file_get_contents(__DIR__ . '/samples/actor_3.3_missing_categories_prefixed.xml');

        // @codingStandardsIgnoreStart
        $expectedMessage = "Element '{http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}contactinfo': This element is not expected. Expected is ( {http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}categories ).";
        // @codingStandardsIgnoreEnd

        $expectedErrors = [
            new XMLValidationError($expectedMessage, 24, 0),
        ];

        $actualErrors = $this->validationService->validate($xml);

        $this->assertEquals($expectedErrors, $actualErrors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_for_xml_with_errors_that_do_not_exceed_the_configured_error_level()
    {
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_invalid_non_fatal.xml');

        // @codingStandardsIgnoreStart
        $errorMessage = "Element '{http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL}event', attribute 'foo': The attribute 'foo' is not allowed.";
        // @codingStandardsIgnoreEnd

        $expectedErrors = [
            new XMLValidationError($errorMessage, 3, 0),
        ];
        $actualErrors = $this->validationService->validate($xml);

        $fatalErrorValidationService = new XSDAwareXMLValidationService($this->xsdReader, LIBXML_ERR_FATAL);
        $fatalErrors = $fatalErrorValidationService->validate($xml);

        $this->assertEquals($expectedErrors, $actualErrors);
        $this->assertEmpty($fatalErrors);
    }

    /**
     * @test
     */
    public function it_returns_no_previous_libxml_errors()
    {
        // First we trigger some errors using libxml directly.
        libxml_use_internal_errors(true);
        $invalidXml = file_get_contents(__DIR__ . '/samples/event_3.3_invalid_format.xml');
        $domDocument = new \DOMDocument($invalidXml);
        $domDocument->schemaValidate(__DIR__ . '/samples/CdbXSD.3.3.xsd.xml');
        $previousErrors = libxml_get_errors();
        libxml_use_internal_errors(false);

        // Next we validate some other xml using the
        // XSDAwareXMLValidationService.
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_valid.xml');
        $errors = $this->validationService->validate($xml);

        // Make sure the last validation did not return any errors,
        // and the first one did.
        $this->assertNotEmpty($previousErrors);
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_clears_any_libxml_errors_after_validation()
    {
        // Enable internal errors manually, otherwise the
        // XSDAwareXMLValidationService will disable this after
        // validation and all errors would be cleared as a side effect instead
        // of on purpose. (See next test as well.)
        libxml_use_internal_errors(true);

        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_invalid_format.xml');
        $errors = $this->validationService->validate($xml);

        $this->assertNotEmpty($errors);
        $this->assertEmpty(libxml_get_errors());
    }

    /**
     * @test
     */
    public function it_resets_the_libxml_internal_errors_flag_to_its_previous_state_after_validation()
    {
        $xml = file_get_contents(__DIR__ . '/samples/event_3.3_valid.xml');

        libxml_use_internal_errors(false);
        $this->validationService->validate($xml);
        $this->assertFalse(libxml_use_internal_errors());

        libxml_use_internal_errors(true);
        $this->validationService->validate($xml);
        $this->assertTrue(libxml_use_internal_errors());
    }

    /**
     * @return string[]
     */
    private function getErrorMessages(array $xmlValidationErrors)
    {
        return array_map(
            function (XMLValidationError $xmlValidationError) {
                return $xmlValidationError->getMessage();
            },
            $xmlValidationErrors
        );
    }
}
