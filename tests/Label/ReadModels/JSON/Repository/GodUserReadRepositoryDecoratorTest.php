<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GodUserReadRepositoryDecoratorTest extends TestCase
{
    /**
     * @var ReadRepositoryInterface&MockObject
     */
    private $mockRepository;

    private GodUserReadRepositoryDecorator $repository;

    private string $godUserId;

    private string $userId;

    private array $labels;

    private string $privateLabel;

    private string $publicLabel;

    public function setUp(): void
    {
        $this->labels = [
            'c7a73397-a210-4126-8fa0-a9f822c2a356' => new Entity(
                new Uuid('c7a73397-a210-4126-8fa0-a9f822c2a356'),
                'foo',
                Visibility::VISIBLE(),
                Privacy::private()
            ),
            'fa285cf6-314c-42cc-99ee-94030127954d' => new Entity(
                new Uuid('fa285cf6-314c-42cc-99ee-94030127954d'),
                'bar',
                Visibility::VISIBLE(),
                Privacy::public()
            ),
        ];

        $this->mockRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->mockRepository->expects($this->any())
            ->method('getByUuid')
            ->willReturnCallback(
                function (Uuid $uuid) {
                    $uuid = $uuid->toString();

                    return $this->labels[$uuid] ?? null;
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                function (string $name) {
                    $labels = array_filter(
                        $this->labels,
                        function (Entity $label) use ($name) {
                            return $label->getName() === $name;
                        }
                    );

                    $label = reset($labels);
                    return $label ?: null;
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (string $userId, string $name) {
                    $label = $this->mockRepository->getByName($name);

                    if (!$label) {
                        return true;
                    }

                    return $label->getPrivacy()->sameAs(Privacy::public());
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('search')
            ->willReturnCallback(fn () => $this->labels);

        $this->mockRepository->expects($this->any())
            ->method('searchTotalLabels')
            ->willReturnCallback(
                function (Query $query) {
                    return count($this->labels);
                }
            );

        $godUserIds = [
            '720ba243-9b1f-44bd-82c1-729012b2aef4',
            '7f91831c-bdd4-4124-a3f0-df7183cdfcc7',
            '88272ef3-0add-47df-b40e-1eaaa509b1c8',
        ];

        $this->godUserId = $godUserIds[0];
        $this->userId = '50793168-2667-44f1-9a78-bf8548d7810d';

        $this->privateLabel = 'foo';
        $this->publicLabel = 'bar';

        $this->repository = new GodUserReadRepositoryDecorator($this->mockRepository, $godUserIds);
    }

    /**
     * @test
     */
    public function it_should_return_a_label_by_uuid_using_the_injected_repository(): void
    {
        $uuid = new Uuid('c7a73397-a210-4126-8fa0-a9f822c2a356');
        $expected = $this->labels['c7a73397-a210-4126-8fa0-a9f822c2a356'];
        $actual = $this->repository->getByUuid($uuid);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_label_by_name_using_the_injected_repository(): void
    {
        $expected = $this->labels['c7a73397-a210-4126-8fa0-a9f822c2a356'];
        $actual = $this->repository->getByName('foo');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_check_if_a_user_can_use_a_label_using_the_injected_repository(): void
    {
        $this->assertTrue($this->repository->canUseLabel($this->userId, $this->publicLabel));
        $this->assertFalse($this->repository->canUseLabel($this->userId, $this->privateLabel));
    }

    /**
     * @test
     */
    public function it_should_let_a_god_user_use_any_label(): void
    {
        $this->assertTrue($this->repository->canUseLabel($this->godUserId, $this->publicLabel));
        $this->assertTrue($this->repository->canUseLabel($this->godUserId, $this->privateLabel));
    }

    /**
     * @test
     */
    public function it_should_return_search_results_for_a_query_using_the_injected_repository(): void
    {
        $query = new Query('test');
        $expected = $this->labels;
        $actual = $this->repository->search($query);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_result_count_for_a_query_using_the_injected_repository(): void
    {
        $query = new Query('test');
        $expected = count($this->labels);
        $actual = $this->repository->searchTotalLabels($query);
        $this->assertEquals($expected, $actual);
    }
}
