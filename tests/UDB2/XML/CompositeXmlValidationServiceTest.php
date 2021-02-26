<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XML;

use PHPUnit\Framework\TestCase;

class CompositeXmlValidationServiceTest extends TestCase
{
    /**
     * @test
     * @dataProvider errorDataProvider
     *
     * @param XMLValidationError[] $errors1
     * @param XMLValidationError[] $errors2
     * @param XMLValidationError[] $expected
     */
    public function it_returns_validation_errors_of_all_injected_validation_services(
        $errors1,
        $errors2,
        $expected
    ) {
        $xmlValidationService1 = $this->createMock(XMLValidationServiceInterface::class);
        $xmlValidationService2 = $this->createMock(XMLValidationServiceInterface::class);

        $combinedXmlValidationService = new CompositeXmlValidationService(
            $xmlValidationService1,
            $xmlValidationService2
        );

        $xmlValidationService1->expects($this->once())
            ->method('validate')
            ->with('xml')
            ->willReturn($errors1);

        $xmlValidationService2->expects($this->once())
            ->method('validate')
            ->with('xml')
            ->willReturn($errors2);

        $actual = $combinedXmlValidationService->validate('xml');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function errorDataProvider()
    {
        return [
            'it_combines_errors_from_multiple_validation_services' => [
                'errors_1' => [
                    new XMLValidationError(
                        'Error 1',
                        10,
                        6
                    ),
                    new XMLValidationError(
                        'Error 2',
                        17,
                        8
                    ),
                ],
                'errors_2' => [
                    new XMLValidationError(
                        'Error 3',
                        2,
                        90
                    ),
                ],
                'expected' => [
                    new XMLValidationError(
                        'Error 1',
                        10,
                        6
                    ),
                    new XMLValidationError(
                        'Error 2',
                        17,
                        8
                    ),
                    new XMLValidationError(
                        'Error 3',
                        2,
                        90
                    ),
                ],
            ],
            'it_returns_errors_from_any_validator_if_one_returns_any_errors' => [
                'errors_1' => [
                    new XMLValidationError(
                        'Error 1',
                        10,
                        6
                    ),
                ],
                'errors_2' => [],
                'expected' => [
                    new XMLValidationError(
                        'Error 1',
                        10,
                        6
                    ),
                ],
            ],
            'it_returns_no_errors_if_the_injected_validators_do_not_return_any_errors' => [
                'errors_1' => [],
                'errors_2' => [],
                'expected' => [],
            ],
        ];
    }
}
