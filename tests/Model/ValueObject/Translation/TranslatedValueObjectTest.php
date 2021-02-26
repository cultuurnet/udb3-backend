<?php

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use PHPUnit\Framework\TestCase;

class TranslatedValueObjectTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_only_accept_the_supported_value_object_type_as_original_value()
    {
        $className = MockValueObjectString::class;
        $invalidClassName = MockValueObjectInteger::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        new TranslatedMockValueObjectString(new Language('nl'), new MockValueObjectInteger(10));
    }

    /**
     * @test
     */
    public function it_should_start_with_one_language_and_value_and_be_translatable()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');
        $en = new Language('en');

        $nlValue = new MockValueObjectString('foo');
        $frValue = new MockValueObjectString('bar');
        $enValue = new MockValueObjectString('lorem');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue))
            ->withTranslation($fr, $frValue)
            ->withTranslation($en, $enValue);

        $this->assertEquals($nlValue, $translated->getTranslation($nl));
        $this->assertEquals($frValue, $translated->getTranslation($fr));
        $this->assertEquals($enValue, $translated->getTranslation($en));
    }

    /**
     * @test
     */
    public function it_should_only_accept_the_supported_value_object_type_as_translation()
    {
        $nl = new Language('nl');
        $nlValue = new MockValueObjectString('foo');
        $translated = (new TranslatedMockValueObjectString($nl, $nlValue));

        $className = MockValueObjectString::class;
        $invalidClassName = MockValueObjectInteger::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given object is a {$invalidClassName}, expected {$className}.");

        $translated->withTranslation(new Language('fr'), new MockValueObjectInteger(10));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_remove_a_translation()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');
        $en = new Language('en');

        $nlValue = new MockValueObjectString('foo');
        $frValue = new MockValueObjectString('bar');
        $enValue = new MockValueObjectString('lorem');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue))
            ->withTranslation($fr, $frValue)
            ->withTranslation($en, $enValue)
            ->withoutTranslation($fr);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No translation found for language fr');

        $translated->getTranslation($fr);
    }

    /**
     * @test
     */
    public function it_should_not_be_able_to_remove_the_original_language()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');
        $en = new Language('en');

        $nlValue = new MockValueObjectString('foo');
        $frValue = new MockValueObjectString('bar');
        $enValue = new MockValueObjectString('lorem');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue))
            ->withTranslation($fr, $frValue)
            ->withTranslation($en, $enValue);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not remove translation of the original language.');

        $translated->withoutTranslation($nl);
    }

    /**
     * @test
     */
    public function it_should_return_the_original_language()
    {
        $nl = new Language('nl');
        $nlValue = new MockValueObjectString('foo');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue));

        $this->assertEquals($nl, $translated->getOriginalLanguage());
    }

    /**
     * @test
     */
    public function it_should_return_all_languages()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');
        $en = new Language('en');

        $nlValue = new MockValueObjectString('foo');
        $frValue = new MockValueObjectString('bar');
        $enValue = new MockValueObjectString('lorem');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue))
            ->withTranslation($fr, $frValue)
            ->withTranslation($en, $enValue);

        $expectedLanguages = new Languages($nl, $fr, $en);
        $this->assertEquals($expectedLanguages, $translated->getLanguages());
    }

    /**
     * @test
     */
    public function it_should_return_all_languages_without_the_original_language()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');
        $en = new Language('en');

        $nlValue = new MockValueObjectString('foo');
        $frValue = new MockValueObjectString('bar');
        $enValue = new MockValueObjectString('lorem');

        $translated = (new TranslatedMockValueObjectString($nl, $nlValue))
            ->withTranslation($fr, $frValue)
            ->withTranslation($en, $enValue);

        $expectedLanguages = new Languages($fr, $en);
        $this->assertEquals($expectedLanguages, $translated->getLanguagesWithoutOriginal());
    }
}
