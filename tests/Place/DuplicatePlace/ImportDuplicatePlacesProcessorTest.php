<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Place\DuplicatePlace\Dto\ClusterRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ImportDuplicatePlacesProcessorTest extends TestCase
{
    private MockObject $duplicatePlaceRepository;
    private ImportDuplicatePlacesProcessor $importDuplicatePlacesProcessor;

    protected function setUp(): void
    {
        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);
        $this->importDuplicatePlacesProcessor = new ImportDuplicatePlacesProcessor(
            $this->duplicatePlaceRepository
        );
    }

    /**
     * @dataProvider provideClustersData
     */
    public function test_sync(array $noLongerInClusters, array $notYetInClusters): void
    {
        $this->duplicatePlaceRepository->method('calculateNoLongerInCluster')
            ->willReturn($noLongerInClusters);

        $this->duplicatePlaceRepository->method('calculateNotYetInCluster')
            ->willReturn($notYetInClusters);

        $this->duplicatePlaceRepository->expects($this->exactly(count($noLongerInClusters)))
            ->method('addToDuplicatePlacesRemovedFromCluster')
            ->with($this->callback(function ($clusterId) use ($noLongerInClusters) {
                foreach ($noLongerInClusters as $noLongerInCluster) {
                    if ($clusterId === $noLongerInCluster->getClusterId()) {
                        return true;
                    }
                }
                return false;
            }));

        $this->duplicatePlaceRepository->expects($this->exactly(count($notYetInClusters)))
            ->method('addToDuplicatePlaces')
            ->with($this->callback(function ($clusterId) use ($notYetInClusters) {
                foreach ($notYetInClusters as $notYetInCluster) {
                    if ($clusterId === $notYetInCluster->getClusterId()) {
                        return true;
                    }
                }
                return false;
            }), $this->callback(function ($placeUuid) use ($notYetInClusters) {
                foreach ($notYetInClusters as $notYetInCluster) {
                    if ($placeUuid === $notYetInCluster->getPlaceUuid()->toString()) {
                        return true;
                    }
                }
                return false;
            }));

        $this->importDuplicatePlacesProcessor->sync();
    }

    public function provideClustersData(): array
    {
        $placeUuids = [];
        foreach (range('A', 'E') as $letter) {
            $placeUuids[$letter] = Uuid::uuid4();
        }

        $clusterAB = [
            new ClusterRecord('AB', $placeUuids['A']),
            new ClusterRecord('AB', $placeUuids['B']),
        ];

        $clusterABC = [
            new ClusterRecord('ABC', $placeUuids['A']),
            new ClusterRecord('ABC', $placeUuids['B']),
            new ClusterRecord('ABC', $placeUuids['C']),
        ];

        $clusterCD = [
            new ClusterRecord('CD', $placeUuids['C']),
            new ClusterRecord('CD', $placeUuids['D']),
        ];

        $clusterDE = [
            new ClusterRecord('DE', $placeUuids['D']),
            new ClusterRecord('DE', $placeUuids['E']),
        ];

        return [
            'One cluster no longer in cluster' => [[...$clusterAB], []],
            'One cluster not yet in cluster' => [[], [...$clusterABC]],
            '2 clusters split up in 2 new clusters' => [[...$clusterAB, ...$clusterCD], [...$clusterABC, ...$clusterDE]],
            'One place moved to new cluster' => [[...$clusterABC], [...$clusterAB, ...$clusterCD]],
        ];
    }
}
