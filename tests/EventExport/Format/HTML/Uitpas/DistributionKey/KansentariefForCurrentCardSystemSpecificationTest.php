<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey;
use CultureFeed_Uitpas_DistributionKey_Condition as Condition;
use PHPUnit\Framework\TestCase;

class KansentariefForCurrentCardSystemSpecificationTest extends TestCase
{
    protected DistributionKeyFactory $keyFactory;

    private KansentariefForCurrentCardSystemSpecification $specification;

    public function setUp(): void
    {
        $this->specification =
            new KansentariefForCurrentCardSystemSpecification();
    }

    public function satisfyingDistributionKeysProvider(): array
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        return [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_MY_CARDSYSTEM
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
                            Condition::VALUE_MY_CARDSYSTEM
                        ),
                    ]
                ),
            ],
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_MY_CARDSYSTEM
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

    /**
     * @test
     * @dataProvider satisfyingDistributionKeysProvider
     */
    public function it_is_satisfied_by_a_kansarm_in_my_cardsystem_condition(
        CultureFeed_Uitpas_DistributionKey $key
    ): void {
        $this->assertTrue(
            $this->specification->isSatisfiedBy($key)
        );
    }

    public function nonSatisfyingDistributionKeysProvider(): array
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        return [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
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
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
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
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
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

    /**
     * @test
     * @dataProvider nonSatisfyingDistributionKeysProvider
     */
    public function it_is_not_satisfied_by_other_distribution_key_conditions(
        CultureFeed_Uitpas_DistributionKey $key
    ): void {
        $this->assertFalse(
            $this->specification->isSatisfiedBy($key)
        );
    }
}
