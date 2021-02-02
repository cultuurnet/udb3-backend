<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use PHPUnit\Framework\TestCase;

class TelephoneNumberTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_reject_empty_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelephoneNumber('');
    }
}
