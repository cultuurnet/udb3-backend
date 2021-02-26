<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\MediaObject;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\MediaObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;
use ValueObjects\Identity\UUID;

class MediaObjectsExistValidatorTest extends TestCase
{
    /**
     * @var MediaManagerInterface|MockObject
     */
    private $mediaManager;

    /**
     * @var MediaObjectsExistValidator
     */
    private $validator;

    public function setUp()
    {
        $this->mediaManager = $this->createMock(MediaManagerInterface::class);
        $this->validator = new MediaObjectsExistValidator($this->mediaManager);
    }

    /**
     * @test
     */
    public function it_should_pass_if_all_media_objects_exist()
    {
        $this->expectIdsToExist(
            [
                '17a786e4-e168-4f24-877a-0035eddc2053',
                '0514738f-7e29-4d6f-b6fb-640c8558f587',
            ]
        );

        $mediaObjects = [
            ['@id' => 'https://io.uitdatabank.be/images/17a786e4-e168-4f24-877a-0035eddc2053'],
            ['@id' => 'https://io.uitdatabank.be/images/0514738f-7e29-4d6f-b6fb-640c8558f587'],
        ];

        $this->assertTrue($this->validator->validate($mediaObjects));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_any_of_the_given_media_objects_do_not_exist()
    {
        $this->expectIdsToExist(
            [
                '17a786e4-e168-4f24-877a-0035eddc2053',
                '0514738f-7e29-4d6f-b6fb-640c8558f587',
            ]
        );

        $mediaObjects = [
            ['@id' => 'https://io.uitdatabank.be/images/b3640a72-eba6-47bf-b486-89511021a3ee'],
            ['@id' => 'https://io.uitdatabank.be/images/17a786e4-e168-4f24-877a-0035eddc2053'],
            ['@id' => 'https://io.uitdatabank.be/images/2d3b42b2-da63-4a18-9759-1d044e922850'],
            ['@id' => 'https://io.uitdatabank.be/images/0514738f-7e29-4d6f-b6fb-640c8558f587'],
        ];

        $expected = [
            'Each item in mediaObject must be valid',
            'mediaObject with @id https://io.uitdatabank.be/images/b3640a72-eba6-47bf-b486-89511021a3ee does not exist',
            'mediaObject with @id https://io.uitdatabank.be/images/2d3b42b2-da63-4a18-9759-1d044e922850 does not exist',
        ];

        try {
            $this->validator->assert($mediaObjects);
            $errors = [];
        } catch (GroupedValidationException $e) {
            $errors = $e->getMessages();
        }

        $this->assertEquals($expected, $errors);
    }


    private function expectIdsToExist(array $ids)
    {
        $this->mediaManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function (UUID $id) use ($ids) {
                    if (in_array($id->toNative(), $ids)) {
                        return new MediaObject(
                            'https://mocked-image.jpg',
                            'https://mocked-image-thumbnail.jpg',
                            'description',
                            'copyright holder',
                            $id->toNative()
                        );
                    }
                    throw new MediaObjectNotFoundException();
                }
            );
    }
}
