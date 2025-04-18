<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cultuurkuur;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

class GetRegionsRequestHandlerTest extends TestCase
{
    public function testReturnsJsonResponse(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/regions/')
            ->build('GET');

        $response = (new GetRegionsRequestHandler())->handle($request);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $json = Json::decodeAssociatively($response->getBody()->getContents());

        $this->assertIsArray($json);

        $this->assertArrayHasKey('label', $json[0]);
        $this->assertArrayHasKey('value', $json[0]);
        $this->assertArrayHasKey('children', $json[0]);
        $this->assertEquals('nis-01000', $json[0]['value']);
        $this->assertEquals('Brussels Hoofdstedelijk Gewest', $json[0]['label']);
        $this->assertIsArray($json[0]['children']);
    }
}
