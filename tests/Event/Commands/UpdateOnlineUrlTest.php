<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class UpdateOnlineUrlTest extends TestCase
{
    private UpdateOnlineUrl $updateOnlineUrl;

    protected function setUp(): void
    {
        $this->updateOnlineUrl = new UpdateOnlineUrl(
            '8066261d-eaf2-4414-8874-e210d665e896',
            new Url('https://www.publiq.be/livestream')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals(
            '8066261d-eaf2-4414-8874-e210d665e896',
            $this->updateOnlineUrl->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_online_url(): void
    {
        $this->assertEquals(
            new Url('https://www.publiq.be/livestream'),
            $this->updateOnlineUrl->getOnlineUrl()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(
            Permission::aanbodBewerken(),
            $this->updateOnlineUrl->getPermission()
        );
    }
}
