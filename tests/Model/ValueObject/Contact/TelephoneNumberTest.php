<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use PHPUnit\Framework\TestCase;

class TelephoneNumberTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_reject_empty_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelephoneNumber('');
    }
}
