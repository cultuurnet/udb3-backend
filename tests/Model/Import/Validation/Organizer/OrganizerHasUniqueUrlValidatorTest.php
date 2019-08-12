<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Organizer;

use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Organizer\WebsiteLookupServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\CallbackException;
use ValueObjects\Web\Url;

class OrganizerHasUniqueUrlValidatorTest extends TestCase
{
    /**
     * @var WebsiteLookupServiceInterface|MockObject
     */
    private $lookupService;

    /**
     * @var OrganizerHasUniqueUrlValidator
     */
    private $validator;

    public function setUp()
    {
        $this->lookupService = $this->createMock(WebsiteLookupServiceInterface::class);

        $this->validator = new OrganizerHasUniqueUrlValidator(
            new OrganizerIDParser(),
            $this->lookupService
        );
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_data_is_not_an_array()
    {
        // This is handled by another validator.
        $organizer = 'foo';
        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_no_id_is_present()
    {
        // This is handled by another validator.
        $organizer = [];
        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_no_url_is_present()
    {
        // This is handled by another validator.
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/f3597251-3a51-4e39-806b-a91110af3a65',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_id_is_invalid()
    {
        // This is handled by another validator.
        $organizer = [
            '@id' => 'foo',
            'url' => 'https://www.publiq.be',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_url_is_invalid()
    {
        // This is handled by another validator.
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/f3597251-3a51-4e39-806b-a91110af3a65',
            'url' => 'foo',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_url_is_available()
    {
        $this->lookupService->expects($this->once())
            ->method('lookup')
            ->with(Url::fromNative('https://www.publiq.be'))
            ->willReturn(null);

        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/f3597251-3a51-4e39-806b-a91110af3a65',
            'url' => 'https://www.publiq.be',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_url_is_linked_to_the_same_organizer()
    {
        $this->lookupService->expects($this->once())
            ->method('lookup')
            ->with(Url::fromNative('https://www.publiq.be'))
            ->willReturn('f3597251-3a51-4e39-806b-a91110af3a65');

        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/f3597251-3a51-4e39-806b-a91110af3a65',
            'url' => 'https://www.publiq.be',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_url_is_linked_to_a_different_organizer()
    {
        $this->lookupService->expects($this->once())
            ->method('lookup')
            ->with(Url::fromNative('https://www.publiq.be'))
            ->willReturn('5a9c7219-0a50-4f27-9a13-0a74579f3e61');

        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/f3597251-3a51-4e39-806b-a91110af3a65',
            'url' => 'https://www.publiq.be',
        ];

        $this->expectException(CallbackException::class);
        $this->expectExceptionMessage('A different organizer with the same url already exists.');

        $this->validator->assert($organizer);
    }
}
