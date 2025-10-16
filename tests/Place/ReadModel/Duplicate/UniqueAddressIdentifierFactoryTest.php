<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;
use PHPUnit\Framework\TestCase;

final class UniqueAddressIdentifierFactoryTest extends TestCase
{
    /**
     * @dataProvider hashDataProvider
     */
    public function testHash(
        string $title,
        Address $address,
        string $currentUserId,
        string $expectedHashForUser,
        string $expectedHash
    ): void {
        $actualHash = (new UniqueAddressIdentifierFactory())
            ->createForUser($title, $address, $currentUserId);

        $this->assertEquals($expectedHashForUser, $actualHash);

        $actualHashV2 = (new UniqueAddressIdentifierFactory())
            ->create($title, $address);

        $this->assertEquals($expectedHash, $actualHashV2);
    }


    public function hashDataProvider(): array
    {
        return [
            'Normal address' => [
                'Cafe den uil',
                new Udb3Address(
                    new Udb3Street('Kerkstraat 1'),
                    new Udb3PostalCode('2000'),
                    new Udb3Locality('Antwerpen'),
                    new CountryCode('BE')
                ),
                'user123',
                'cafe_den_uil_kerkstraat_1_2000_antwerpen_be_user123',
                'cafe_den_uil_kerkstraat_1_2000_antwerpen_be',
            ],
            'address with empty location name' => [
                '',
                new Udb3Address(
                    new Udb3Street('Kerkstraat 1'),
                    new Udb3PostalCode('2000'),
                    new Udb3Locality('Antwerpen'),
                    new CountryCode('BE')
                ),
                'user123',
                'kerkstraat_1_2000_antwerpen_be_user123',
                'kerkstraat_1_2000_antwerpen_be',
            ],
            'address with special chars' => [
                '',
                new Udb3Address(
                    new Udb3Street('Kerkstraat 1!'),
                    new Udb3PostalCode('2000'),
                    new Udb3Locality('Antwerpen(Berchem)'),
                    new CountryCode('BE')
                ),
                'user123',
                'kerkstraat_1\!_2000_antwerpen\(berchem\)_be_user123',
                'kerkstraat_1_2000_antwerpen_berchem_be',
            ],
            'address with diacritics' => [
                '\'þ ßnœpŵïñķēłťje',
                new Udb3Address(
                    new Udb3Street('Veldstraat 50'),
                    new Udb3PostalCode('9000'),
                    new Udb3Locality('Gent'),
                    new CountryCode('BE')
                ),
                'user123',
                '\'þ_ßnœpŵïñķēłťje_veldstraat_50_9000_gent_be_user123',
                'th_ssnoepwinkeltje_veldstraat_50_9000_gent_be',
            ],
        ];
    }
}
