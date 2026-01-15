<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UploadedFile;
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

    protected Url $onlineImageUrl;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->maxFileSize = 10000;
        $this->imageDownloader = new ImageDownloaderService($this->client, $this->maxFileSize);

        $this->onlineImageUrl = new Url('https://foobar.com/someImage.png');
    }

    /**
     * @test
     */
    public function it_should_return_an_uploaded_file(): void
    {
        $content = str_repeat('X', $this->maxFileSize);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->exactly(2))
            ->method('eof')
            ->willReturnOnConsecutiveCalls(false, true);

        $stream->expects($this->once())
            ->method('read')
            ->with(8192)
            ->willReturn($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $this->client->expects($this->once())->method('sendRequest')
            ->with(new Request('GET', $this->onlineImageUrl->toString()))->willReturn($response);

        $uploadedFile = $this->imageDownloader->download($this->onlineImageUrl);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_uploaded_file_is_not_an_image(): void
    {
        $this->expectException(InvalidFileSize::class);
        $this->expectExceptionMessage('The file size of the uploaded image is too big. Max size (bytes):10000');

        $content = str_repeat('X', $this->maxFileSize * 2);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->exactly(1))
            ->method('eof')
            ->willReturnOnConsecutiveCalls(false, true);

        $stream->expects($this->once())
            ->method('read')
            ->with(8192)
            ->willReturn($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
                ->method('getBody')
                ->willReturn($stream);

        $this->client->expects($this->once())->method('sendRequest')
            ->with(new Request('GET', $this->onlineImageUrl->toString()))->willReturn($response);

        $this->imageDownloader->download($this->onlineImageUrl);
    }
}
