<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class GodUserReadRepositoryDecoratorTest extends TestCase
{
    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $mockRepository;

    /**
     * @var string[]
     */
    private $godUserIds;

    /**
     * @var GodUserReadRepositoryDecorator
     */
    private $repository;

    /**
     * @var StringLiteral
     */
    private $godUserId;

    /**
     * @var StringLiteral
     */
    private $userId;

    /**
     * @var array
     */
    private $labels;

    /**
     * @var StringLiteral
     */
    private $privateLabel;

    /**
     * @var StringLiteral
     */
    private $publicLabel;

    public function setUp()
    {
        $this->labels = [
            'c7a73397-a210-4126-8fa0-a9f822c2a356' => new Entity(
                new UUID('c7a73397-a210-4126-8fa0-a9f822c2a356'),
                new StringLiteral('foo'),
                Visibility::VISIBLE(),
                Privacy::PRIVACY_PRIVATE()
            ),
            'fa285cf6-314c-42cc-99ee-94030127954d' => new Entity(
                new UUID('fa285cf6-314c-42cc-99ee-94030127954d'),
                new StringLiteral('bar'),
                Visibility::VISIBLE(),
                Privacy::PRIVACY_PUBLIC()
            ),
        ];

        $this->mockRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->mockRepository->expects($this->any())
            ->method('getByUuid')
            ->willReturnCallback(
                function (UUID $uuid) {
                    $uuid = $uuid->toNative();

                    if (isset($this->labels[$uuid])) {
                        return $this->labels[$uuid];
                    }

                    return null;
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                function (StringLiteral $name) {
                    $labels = array_filter(
                        $this->labels,
                        function (Entity $label) use ($name) {
                            return $label->getName()->sameValueAs($name);
                        }
                    );

                    $label = reset($labels);
                    return $label ? $label : null;
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (StringLiteral $userId, StringLiteral $name) {
                    $label = $this->mockRepository->getByName($name);

                    if (!$label) {
                        return true;
                    }

                    return $label->getPrivacy()->sameValueAs(Privacy::PRIVACY_PUBLIC());
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('search')
            ->willReturnCallback(
                function (Query $query) {
                    return $this->labels;
                }
            );

        $this->mockRepository->expects($this->any())
            ->method('searchTotalLabels')
            ->willReturnCallback(
                function (Query $query) {
                    $count = count($this->labels);
                    return new Natural($count);
                }
            );

        $this->godUserIds = [
            '720ba243-9b1f-44bd-82c1-729012b2aef4',
            '7f91831c-bdd4-4124-a3f0-df7183cdfcc7',
            '88272ef3-0add-47df-b40e-1eaaa509b1c8',
        ];

        $this->godUserId = new StringLiteral($this->godUserIds[0]);
        $this->userId = new StringLiteral('50793168-2667-44f1-9a78-bf8548d7810d');

        $this->privateLabel = new StringLiteral('foo');
        $this->publicLabel = new StringLiteral('bar');

        $this->repository = new GodUserReadRepositoryDecorator($this->mockRepository, $this->godUserIds);
    }

    /**
     * @test
     */
    public function it_should_return_a_label_by_uuid_using_the_injected_repository()
    {
        $uuid = new UUID('c7a73397-a210-4126-8fa0-a9f822c2a356');
        $expected = $this->labels['c7a73397-a210-4126-8fa0-a9f822c2a356'];
        $actual = $this->repository->getByUuid($uuid);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_label_by_name_using_the_injected_repository()
    {
        $name = new StringLiteral('foo');
        $expected = $this->labels['c7a73397-a210-4126-8fa0-a9f822c2a356'];
        $actual = $this->repository->getByName($name);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_check_if_a_user_can_use_a_label_using_the_injected_repository()
    {
        $this->assertTrue($this->repository->canUseLabel($this->userId, $this->publicLabel));
        $this->assertFalse($this->repository->canUseLabel($this->userId, $this->privateLabel));
    }

    /**
     * @test
     */
    public function it_should_let_a_god_user_use_any_label()
    {
        $this->assertTrue($this->repository->canUseLabel($this->godUserId, $this->publicLabel));
        $this->assertTrue($this->repository->canUseLabel($this->godUserId, $this->privateLabel));
    }

    /**
     * @test
     */
    public function it_should_return_search_results_for_a_query_using_the_injected_repository()
    {
        $query = new Query(new StringLiteral('test'));
        $expected = $this->labels;
        $actual = $this->repository->search($query);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_result_count_for_a_query_using_the_injected_repository()
    {
        $query = new Query(new StringLiteral('test'));
        $expected = new Natural(count($this->labels));
        $actual = $this->repository->searchTotalLabels($query);
        $this->assertEquals($expected, $actual);
    }
}
