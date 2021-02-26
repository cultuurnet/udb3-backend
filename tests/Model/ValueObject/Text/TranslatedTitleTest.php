<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class TranslatedTitleTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_only_accept_a_title_as_original_text_value()
    {
        $className = Title::class;
        $invalidClassName = Description::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        new TranslatedTitle(new Language('nl'), new Description('foo'));
    }

    /**
     * @test
     */
    public function it_should_only_accept_a_title_as_translation()
    {
        $nl = new Language('nl');
        $nlValue = new Title('foo');
        $translatedTitle = (new TranslatedTitle($nl, $nlValue));

        $className = Title::class;
        $invalidClassName = Description::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        $translatedTitle->withTranslation(new Language('fr'), new Description('foo'));
    }
}
