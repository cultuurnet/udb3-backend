<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey;
use CultureFeed_Uitpas_DistributionKey_Condition as Condition;
use PHPUnit\Framework\TestCase;

class KansentariefDiscountSpecificationTest extends TestCase
{
    protected KansentariefDiscountSpecification $specification;

    protected DistributionKeyConditionFactory $conditionFactory;

    protected DistributionKeyFactory $keyFactory;

    protected array $cardSystems;

    public function setUp(): void
    {
        $this->specification = new KansentariefDiscountSpecification();
    }

    /**
     * @test
     * @dataProvider satisfyingDistributionKeyProvider
     */
    public function it_is_satisfied_by_a_key_with_kansarm_condition(CultureFeed_Uitpas_DistributionKey $key): void
    {
        $this->assertTrue($this->specification->isSatisfiedBy($key));
    }

    public function satisfyingDistributionKeyProvider(): array
    {
        $data = [];

        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        $cardSystems = [
            Condition::VALUE_MY_CARDSYSTEM,
            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM,
        ];

        foreach ($cardSystems as $cardSystem) {
            $data += [
                [
                    $keyFactory->buildKey(
                        '1.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                        ]
                    ),
                ],
                [
                    $keyFactory->buildKey(
                        '0.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                        ]
                    ),
                ],
                [
                    $keyFactory->buildKey(
                        '0.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_PRICE,
                                Condition::OPERATOR_LESS_THAN,
                                '7'
                            ),
                        ]
                    ),
                ],
            ];
        }

        return $data;
    }

    /**
     * @test
     * @dataProvider unsatisfyingDistributionKeyProvider
     */
    public function it_is_unsatisfied_by_a_key_without_kansarm_condition(CultureFeed_Uitpas_DistributionKey $key): void
    {
        $this->assertFalse($this->specification->isSatisfiedBy($key));
    }

    public function unsatisfyingDistributionKeyProvider(): array
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        return [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            '7'
                        ),
                    ]
                ),
            ],
            [
                $keyFactory->buildKey(
                    '0.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            '7'
                        ),
                    ]
                ),
            ],
        ];
    }
}
