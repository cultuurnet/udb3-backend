<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ImageDownloaderServiceTest extends TestCase
{
    private ImageDownloader $imageDownloader;

    protected ClientInterface&MockObject $client;

    protected int $maxFileSize;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->maxFileSize = 10000;
        $this->imageDownloader = new ImageDownloaderService($this->client, $this->maxFileSize);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_uploaded_file_is_not_an_image(): void
    {
        $tooLargeUrl = new Url('https://foobar/tooLarge.png');
        $this->expectException(InvalidFileSize::class);
        $this->expectExceptionMessage('The file size of the uploaded image is too big.');

        $content = str_repeat('X', $this->maxFileSize + 1);
        $stream = Utils::streamFor($content);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects($this->exactly(1))
            ->method('eof')
            ->willReturnOnConsecutiveCalls(false, true);

        $streamMock->expects($this->once())
            ->method('read')
            ->with(8192)
            ->willReturn($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
                ->method('getBody')
                ->willReturn($streamMock);

        $this->client->expects($this->once())->method('sendRequest')
            ->with(new Request('GET', $tooLargeUrl->toString()))->willReturn($response);

        $this->imageDownloader->download($tooLargeUrl);
    }
}
