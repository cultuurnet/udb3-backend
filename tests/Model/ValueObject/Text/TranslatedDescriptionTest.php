<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class TranslatedDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_only_accept_a_description_as_original_text_value()
    {
        $className = Description::class;
        $invalidClassName = Title::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        new TranslatedDescription(new Language('nl'), new Title('foo'));
    }

    /**
     * @test
     */
    public function it_should_only_accept_a_description_as_translation()
    {
        $nl = new Language('nl');
        $nlValue = new Description('foo');
        $translatedDescription = (new TranslatedDescription($nl, $nlValue));

        $className = Description::class;
        $invalidClassName = Title::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        $translatedDescription->withTranslation(new Language('fr'), new Title('foo'));
    }
}
