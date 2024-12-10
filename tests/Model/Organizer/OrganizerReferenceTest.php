<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class OrganizerReferenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_using_a_organizer_id(): void
    {
        $id = new Uuid('38d78529-29b8-4635-a26e-51bbb2eba535');
        $reference = OrganizerReference::createWithOrganizerId($id);

        $this->assertEquals($id, $reference->getOrganizerId());
    }
}
