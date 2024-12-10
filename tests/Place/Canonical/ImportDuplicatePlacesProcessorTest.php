<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class ImportDuplicatePlacesProcessorTest extends TestCase
{
    /** @var DuplicatePlaceRepository&MockObject */
    private $duplicatePlaceRepository;
    /** @var DuplicatePlaceRemovedFromClusterRepository&MockObject */
    private DuplicatePlaceRemovedFromClusterRepository $duplicatePlacesRemovedFromClusterRepository;
    private ImportDuplicatePlacesProcessor $importDuplicatePlacesProcessor;

    protected function setUp(): void
    {
        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);
        $this->duplicatePlacesRemovedFromClusterRepository = $this->createMock(DuplicatePlaceRemovedFromClusterRepository::class);

        $this->importDuplicatePlacesProcessor = new ImportDuplicatePlacesProcessor(
            $this->duplicatePlaceRepository,
            $this->duplicatePlacesRemovedFromClusterRepository
        );
    }

    /**
     * @dataProvider syncDataProviderDeleteOldClusters
     */
    public function test_delete_old_clusters(array $placesNoLongerInCluster, array $clustersToBeRemoved): void
    {
        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getPlacesNoLongerInCluster')
            ->willReturn($placesNoLongerInCluster);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getClustersToBeRemoved')
            ->willReturn($clustersToBeRemoved);

        $this->duplicatePlacesRemovedFromClusterRepository->expects($this->exactly(count($placesNoLongerInCluster)))
            ->method('addPlace')
            ->willReturnCallback(function ($placeUuid) use ($placesNoLongerInCluster) {
                $this->assertContains($placeUuid, $placesNoLongerInCluster);
            });

        $this->duplicatePlaceRepository->expects($this->exactly(count($clustersToBeRemoved)))
            ->method('deleteCluster')
            ->willReturnCallback(function ($clusterUuid) use ($clustersToBeRemoved) {
                $this->assertContains($clusterUuid, $clustersToBeRemoved);
            });

        $this->importDuplicatePlacesProcessor->sync();
    }

    public function test_insert_new_clusters(): void
    {
        $places = [
            new PlaceWithCluster('cluster_1', UUID::uuid4()->toString()),
            new PlaceWithCluster('cluster_1', UUID::uuid4()->toString()),
            new PlaceWithCluster('cluster_2', UUID::uuid4()->toString()),
        ];

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getPlacesWithCluster')
            ->willReturn($places);

        $this->duplicatePlaceRepository->expects($this->exactly(count($places)))
            ->method('addToDuplicatePlaces')
            ->willReturnCallback(function (PlaceWithCluster $clusterRecordRow) use ($places) {
                $this->assertContains($clusterRecordRow, $places);
            });

        $this->importDuplicatePlacesProcessor->sync();
    }

    public function syncDataProviderDeleteOldClusters(): array
    {
        return [
            'no places and no clusters' => [
                'placesNoLongerInCluster' => [],
                'clustersToBeRemoved' => [],
            ],
            'some places and some clusters' => [
                'placesNoLongerInCluster' => ['place-1', 'place-2'],
                'clustersToBeRemoved' => ['cluster-1', 'cluster-2'],
            ],
        ];
    }
}
