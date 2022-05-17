<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands\Moderation;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class PublishTest extends TestCase
{
    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-4700
     */
    public function it_has_permission_aanbodBewerken(): void
    {
        $this->assertEquals(
            Permission::aanbodBewerken(),
            (new Publish('0eda3f6c-e719-45f1-8644-8faa1103c938'))->getPermission()
        );
    }
}
