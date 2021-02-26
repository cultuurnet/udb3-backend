<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class TranslatedStatusReasonTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_only_accept_an_status_reason_as_original_text_value(): void
    {
        $className = StatusReason::class;
        $invalidClassName = Title::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        new TranslatedStatusReason(new Language('nl'), new Title('foo'));
    }

    /**
     * @test
     */
    public function it_should_only_accept_an_status_reason_as_translation()
    {
        $nl = new Language('nl');
        $nlValue = new StatusReason('foo');
        $translatedDescription = (new TranslatedStatusReason($nl, $nlValue));

        $className = StatusReason::class;
        $invalidClassName = Title::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        $translatedDescription->withTranslation(new Language('fr'), new Title('foo'));
    }
}
