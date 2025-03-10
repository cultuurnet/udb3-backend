<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractTranslatePropertyCommandTest extends TestCase
{
    /**
     * @var AbstractTranslatePropertyCommand&MockObject
     */
    protected $translatePropertyCommand;

    protected string $itemId;

    protected Language $language;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->language = new Language('en');

        $this->translatePropertyCommand = $this->getMockForAbstractClass(
            AbstractTranslatePropertyCommand::class,
            [$this->itemId, $this->language]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $language = $this->translatePropertyCommand->getLanguage();
        $expectedLanguage = new Language('en');

        $this->assertEquals($expectedLanguage, $language);

        $itemId = $this->translatePropertyCommand->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
