<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
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
                new Address(
                    new Street('Kerkstraat 1'),
                    new PostalCode('2000'),
                    new Locality('Antwerpen'),
                    new CountryCode('BE')
                ),
                'user123',
                'cafe_den_uil_kerkstraat_1_2000_antwerpen_be_user123',
                'cafe_den_uil_kerkstraat_1_2000_antwerpen_be',
            ],
            'address with empty location name' => [
                '',
                new Address(
                    new Street('Kerkstraat 1'),
                    new PostalCode('2000'),
                    new Locality('Antwerpen'),
                    new CountryCode('BE')
                ),
                'user123',
                'kerkstraat_1_2000_antwerpen_be_user123',
                'kerkstraat_1_2000_antwerpen_be',
            ],
            'address with special chars' => [
                '',
                new Address(
                    new Street('Kerkstraat 1!'),
                    new PostalCode('2000'),
                    new Locality('Antwerpen(Berchem)'),
                    new CountryCode('BE')
                ),
                'user123',
                'kerkstraat_1\!_2000_antwerpen\(berchem\)_be_user123',
                'kerkstraat_1_2000_antwerpen_berchem_be',
            ],
            'address with various special chars' => [
                'S.M.A.K.-Stedelijk*Museum*voor*@ctuele*kunst*',
                new Address(
                    new Street('Jan Hoetplein 1!'),
                    new PostalCode('9000'),
                    new Locality('Gent'),
                    new CountryCode('BE')
                ),
                'user123',
                's.m.a.k.-stedelijk\*museum\*voor\*@ctuele\*kunst\*_jan_hoetplein_1\!_9000_gent_be_user123',
                's_m_a_k_stedelijk_museum_voor_ctuele_kunst_jan_hoetplein_1_9000_gent_be',
            ],
            'address with diacritics' => [
                '\'þ ßnœpŵïñķēłťje',
                new Address(
                    new Street('Veldstraat 50'),
                    new PostalCode('9000'),
                    new Locality('Gent'),
                    new CountryCode('BE')
                ),
                'user123',
                '\'þ_ßnœpŵïñķēłťje_veldstraat_50_9000_gent_be_user123',
                'th_ssnoepwinkeltje_veldstraat_50_9000_gent_be',
            ],
        ];
    }
}
