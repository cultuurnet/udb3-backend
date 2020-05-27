<?php

namespace CultuurNet\UDB3\Role\Services;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class LocalRoleReadingServiceTest extends TestCase
{
    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $roleReadRepository;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $roleLabelsReadRepository;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $roleUsersPermissionsReadRepository;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $userRolesPermissionsReadRepository;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $roleWriteRepository;

    /**
     * @var IriGeneratorInterface|MockObject
     */
    private $iriGenerator;

    /**
     * @var LocalRoleReadingService
     */
    private $readingService;

    public function setUp()
    {
        $this->roleReadRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->roleWriteRepository = $this->createMock(RepositoryInterface::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->roleLabelsReadRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->roleUsersPermissionsReadRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->userRolesPermissionsReadRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->readingService = new LocalRoleReadingService(
            $this->roleReadRepository,
            $this->roleWriteRepository,
            $this->iriGenerator,
            $this->roleLabelsReadRepository,
            $this->roleUsersPermissionsReadRepository,
            $this->userRolesPermissionsReadRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_the_details_of_a_role()
    {
        $roleId = 'da114bb4-42bc-11e6-beb8-9e71128cae77';
        $document = new JsonDocument('da114bb4-42bc-11e6-beb8-9e71128cae77');
        $json = $document->getBody();
        $json->{'@id'} = $roleId;
        $json->name = 'administrator';
        $json->query = 'category_flandersregion_name:"Regio Brussel"';
        $expectedRole = $document->withBody($json);

        $this->roleReadRepository->expects($this->once())
            ->method('get')
            ->with($roleId)
            ->willReturn($expectedRole);

        $role = $this->readingService->getEntity($roleId);

        $this->assertEquals($expectedRole->getRawBody(), $role);
    }

    /**
     * @test
     */
    public function it_returns_the_labels_of_a_role()
    {
        $roleId = new UUID();

        $expectedLabels = (new JsonDocument($roleId))
            ->withBody(
                json_encode([])
            );

        $this->roleLabelsReadRepository->expects($this->once())
            ->method('get')
            ->with($roleId)
            ->willReturn($expectedLabels);

        $actualLabels = $this->readingService->getLabelsByRoleUuid($roleId);

        $this->assertEquals($expectedLabels, $actualLabels);
    }
}
