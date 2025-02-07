<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class ImageLDProjectorTest extends TestCase
{
    private InMemoryDocumentRepository $repository;

    private ImageLDProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryDocumentRepository();
        $this->projector = new ImageLDProjector(
            $this->repository,
            new CallableIriGenerator(
                function (string $id) {
                    return 'https://io.uitdatabank.local/images/' . $id;
                }
            ),
            new CallableIriGenerator(
                function (string $filename) {
                    return 'https://images.uitdatabank.local/' . $filename;
                }
            )
        );
    }

    /**
     * @test
     */
    public function it_projects_media_object_created(): void
    {
        $mediaObjectCreated = new MediaObjectCreated(
            new Uuid('d1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d'),
            new MIMEType('image/jpeg'),
            new Description('A description'),
            new CopyrightHolder('John Doe'),
            new Url('https://images.uitdatabank.local/d1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d.jpeg'),
            new Language('en')
        );

        $this->projector->handle(new DomainMessage(
            $mediaObjectCreated->getMediaObjectId()->toString(),
            0,
            new Metadata(),
            $mediaObjectCreated,
            DateTime::now()
        ));

        $this->assertEquals(
            new JsonDocument(
                'd1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d',
                json_encode(
                    [
                        '@id' => 'https://io.uitdatabank.local/images/d1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d',
                        '@type' => 'schema:ImageObject',
                        'encodingFormat' => 'image/jpeg',
                        'id' => 'd1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d',
                        'contentUrl' => 'https://images.uitdatabank.local/d1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d.jpeg',
                        'thumbnailUrl' => 'https://images.uitdatabank.local/d1b3d1f4-1b4d-4e5e-8b8d-6b7d1d7d1d7d.jpeg',
                        'description' => 'A description',
                        'copyrightHolder' => 'John Doe',
                        'inLanguage' => 'en',
                    ]
                )
            ),
            $this->repository->fetch($mediaObjectCreated->getMediaObjectId()->toString()),
        );
    }
}
