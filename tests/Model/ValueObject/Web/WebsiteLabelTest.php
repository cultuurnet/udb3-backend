<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

class WebsiteLabelTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_reject_empty_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        new WebsiteLabel('');
    }
}
