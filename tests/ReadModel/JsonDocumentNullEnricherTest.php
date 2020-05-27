<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;
use PHPUnit\Framework\TestCase;

class JsonDocumentNullEnricherTest extends TestCase
{
    /**
     * @var JsonDocumentNullEnricher
     */
    private $enricher;

    public function setUp()
    {
        $this->enricher = new JsonDocumentNullEnricher();
    }

    /**
     * @test
     */
    public function it_should_return_the_same_json_document()
    {
        $document = new JsonDocument('68ec37bf-9d1f-412b-81c6-af26ee4cb10a', '{}');
        $this->assertEquals($document, $this->enricher->enrich($document, new Metadata()));
    }
}
