<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels\Helper;

use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\TestCase;

class ExtraUuidFromConstraintTest extends TestCase
{
    /**
     * @test
     * @dataProvider uuidDataProvider
     */
    public function it_should_return_the_uuid_from_a_query(string $queryString, ?string $expectedUuid): void
    {
        $this->assertEquals($expectedUuid, ExtraUuidFromConstraint::extractUuid(new Query($queryString)));
    }

    public function uuidDataProvider(): array
    {
        return [
            ['organisation.id:c3f9278e-228b-4199-8f9a-b9716a17e58f', 'c3f9278e-228b-4199-8f9a-b9716a17e58f'],
            ['id:c3f9278e-228b-4199-8f9a-b9716a17e58f', 'c3f9278e-228b-4199-8f9a-b9716a17e58f'],
            ['id:het-is-kapot', null],
            ['lorum ipsum dolor', null],
            ['organisation.id:', null],
        ];
    }
}
