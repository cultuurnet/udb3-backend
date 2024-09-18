<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class GetMediaRequestHandlerTest extends TestCase
{
    private GetMediaRequestHandler $getMediaRequestHandler;

    protected function setUp(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $mapping = [
            'udb2' => [
                'enabled' => true,
                'legacy_url' => 'https://media.uitdatabank.be/',
                'url' => 'https://images.uitdatabank.be/',
            ],
        ];

        $mediaManager = $this->createMock(MediaManager::class);
        $mediaManager->expects($this->once())
            ->method('get')
            ->with(new UUID($id))
            ->willReturn(
                MediaObject::create(
                    new UUID($id),
                    MIMEType::fromSubtype('jpeg'),
                    new Description('UDB2 image'),
                    new CopyrightHolder('publiq'),
                    new Url('https://media.uitdatabank.be/123/5624b810-c340-40a4-8f38-0393eca59bfe.jpg'),
                    new Language('nl')
                )
            );

        $iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $iriGenerator->expects($this->once())
            ->method('iri')
            ->with($id)
            ->willReturn('https://io.uitdatabank.be/images/5624b810-c340-40a4-8f38-0393eca59bfe');

        $this->getMediaRequestHandler = new GetMediaRequestHandler(
            $mediaManager,
            new MediaObjectSerializer($iriGenerator),
            new MediaUrlMapping($mapping)
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_media_object(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('id', '5624b810-c340-40a4-8f38-0393eca59bfe')
            ->build('GET');

        $response = $this->getMediaRequestHandler->handle($request);

        $this->assertEquals(
            Json::encode([
                '@id' => 'https://io.uitdatabank.be/images/5624b810-c340-40a4-8f38-0393eca59bfe',
                '@type' => 'schema:ImageObject',
                'id' => '5624b810-c340-40a4-8f38-0393eca59bfe',
                'contentUrl' => 'https://images.uitdatabank.be/123/5624b810-c340-40a4-8f38-0393eca59bfe.jpg',
                'thumbnailUrl' => 'https://images.uitdatabank.be/123/5624b810-c340-40a4-8f38-0393eca59bfe.jpg',
                'description' => 'UDB2 image',
                'copyrightHolder' => 'publiq',
                'inLanguage' => 'nl',
            ]),
            $response->getBody()->getContents()
        );
    }
}
