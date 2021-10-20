<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\Validation\ValidatorTestCase;
use CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject\VideoValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use Respect\Validation\Validator;

final class VideoValidatorTest extends ValidatorTestCase
{
    private VideoValidator $videoValidator;

    protected function setUp(): void
    {
        $this->videoValidator = new VideoValidator();
    }

    protected function getValidator(): Validator
    {
        return $this->videoValidator;
    }

    /**
     * @dataProvider validVideos
     * @test
     */
    public function it_allows_valid_properties(array $video): void
    {
        $this->assertTrue($this->videoValidator->validate($video));
    }

    public function validVideos(): array
    {
        return [
            'all properties' => [
                [
                    'id' => 'd46a9fc1-fdba-4d37-98ab-26937be61845',
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                    'copyrightHolder' => 'publiq',
                ],
            ],
            'no id' => [
                [
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                    'copyrightHolder' => 'publiq',
                ],
            ],
            'no copyright holder' => [
                [
                    'id' => 'd46a9fc1-fdba-4d37-98ab-26937be61845',
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_on_required_properties(): void
    {
        $expectedErrors = [
            'Key url must be present',
            'Key language must be present',
        ];

        $video = [];

        $this->assertValidationErrors($video, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_id_format(): void
    {
        $expectedErrors = [
            'id must validate against "/' . addslashes(UUID::BC_REGEX) . '/"',
        ];

        $video = [
            'id' => '123',
            'url' => 'https://www.youtube.com/watch?v=123',
            'language' => 'nl',
        ];

        $this->assertValidationErrors($video, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_url_format(): void
    {
        $expectedErrors = [
            'url must validate against "' . addslashes(Video::REGEX) . '"',
        ];

        $video = [
            'id' => 'd46a9fc1-fdba-4d37-98ab-26937be61845',
            'url' => 'ftp://www.youtube.com/watch?v=123',
            'language' => 'nl',
        ];

        $this->assertValidationErrors($video, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_copyright_holder_format(): void
    {
        $expectedErrors = [
            'copyrightHolder must be a string',
        ];

        $video = [
            'id' => 'd46a9fc1-fdba-4d37-98ab-26937be61845',
            'url' => 'https://www.youtube.com/watch?v=123',
            'language' => 'nl',
            'copyrightHolder' => 123,
        ];

        $this->assertValidationErrors($video, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_on_too_small_copyright_holder_format(): void
    {
        $expectedErrors = [
            'copyrightHolder must have a length greater than 3',
        ];

        $video = [
            'id' => 'd46a9fc1-fdba-4d37-98ab-26937be61845',
            'url' => 'https://www.youtube.com/watch?v=123',
            'language' => 'nl',
            'copyrightHolder' => 'ab',
        ];

        $this->assertValidationErrors($video, $expectedErrors);
    }
}
