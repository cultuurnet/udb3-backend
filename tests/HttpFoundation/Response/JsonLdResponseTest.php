<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use PHPUnit\Framework\TestCase;

class JsonLdResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_a_jsonld_content_type_header()
    {
        $data = ['@id' => 'http://acme.com/foo'];
        $response = new JsonLdResponse($data);
        $contentType = $response->headers->get('Content-Type', '');

        $this->assertEquals('application/ld+json', $contentType);
    }
}
