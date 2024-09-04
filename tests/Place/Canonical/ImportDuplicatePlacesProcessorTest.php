<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @dataProvider syncDataProvider
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

    public function syncDataProvider(): array
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
