<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Traits;

trait Headers
{
    private string $acceptHeader = '';
    private string $contentTypeHeader = '';

    /**
     * @Given I send and accept :arg1
     */
    public function iSendAndAccept($arg1)
    {
        $this->acceptHeader = $arg1;
        $this->contentTypeHeader = $arg1;
    }
}