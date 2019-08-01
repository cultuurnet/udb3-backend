<?php

namespace CultuurNet\UDB3\UDB2;

use CultuurNet\Auth\ConsumerCredentials;

final class Consumer
{
    private $targetUrl;
    private $consumerCredentials;

    public function __construct(
        $targetUrl,
        ConsumerCredentials $consumerCredentials
    ) {
        $this->targetUrl = $targetUrl;
        $this->consumerCredentials = $consumerCredentials;
    }

    /**
     * @return ConsumerCredentials
     */
    public function getConsumerCredentials()
    {
        return $this->consumerCredentials;
    }

    /**
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }
}
