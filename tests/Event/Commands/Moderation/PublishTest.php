<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands\Moderation;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class PublishTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_permission_aanbodBewerken(): void
    {
        $this->assertEquals(
            Permission::aanbodBewerken(),
            (new Publish('0eda3f6c-e719-45f1-8644-8faa1103c938'))->getPermission()
        );
    }
}
