<?php

namespace CultuurNet\UDB3\Event\ReadModel;

use PHPUnit\Framework\TestCase;

class DocumentGoneExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_HTTP_GONE_status_code_by_default()
    {
        $exception = new DocumentGoneException();

        $this->assertEquals(410, $exception->getCode());
    }
}
