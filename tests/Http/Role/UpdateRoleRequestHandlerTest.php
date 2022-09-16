<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Role\MissingContentTypeException;
use PHPUnit\Framework\TestCase;

class UpdateRoleRequestHandlerTest extends TestCase
{
    private UpdateRoleRequestHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new UpdateRoleRequestHandler();
    }

    /**
     * @test
     */
    public function it_throws_when_content_type_header_is_not_given(): void
    {
        $request = (new Psr7RequestBuilder())
            ->build('POST');

        $this->expectException(MissingContentTypeException::class);
        $this->handler->handle($request);
    }
}
