<?php

namespace CultuurNet\UDB3\Model\Import\Validation\MediaObject;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\MediaObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;

class MediaObjectsExistValidatorTest extends TestCase
{
    /**
     * @var MediaManagerInterface|\PHPUnit_Framework_MockObject_MockObject
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

    /**
     * @param array $ids
     */
    private function expectIdsToExist(array $ids)
    {
        $this->mediaManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($id) use ($ids) {
                    $exists = in_array($id, $ids);

                    if ($exists) {
                        return $this->createMock(MediaObject::class);
                    } else {
                        throw new MediaObjectNotFoundException();
                    }
                }
            );
    }
}
