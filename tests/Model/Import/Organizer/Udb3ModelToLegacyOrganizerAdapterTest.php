<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Model\Import\Organizer\Udb3ModelToLegacyOrganizerAdapter;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class Udb3ModelToLegacyOrganizerAdapterTest extends TestCase
{
    /**
     * @var ImmutableOrganizer
     */
    private $organizer;

    /**
     * @var Udb3ModelToLegacyOrganizerAdapter
     */
    private $adapter;

    public function setUp()
    {
        $this->organizer = new ImmutableOrganizer(
            new UUID('91060c19-a860-4a47-8591-8a779bfa520a'),
            new Language('nl'),
            (new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')))
                ->withTranslation(new Language('fr'), new Title('Titre example'))
                ->withTranslation(new Language('en'), new Title('Example title')),
            new Url('https://www.publiq.be')
        );

        $this->adapter = new Udb3ModelToLegacyOrganizerAdapter($this->organizer);
    }

    /**
     * @test
     */
    public function it_should_return_the_title_translations()
    {
        $expected = [
            'fr' => new \CultuurNet\UDB3\Title('Titre example'),
            'en' => new \CultuurNet\UDB3\Title('Example title'),
        ];
        $actual = $this->adapter->getTitleTranslations();
        $this->assertEquals($expected, $actual);
    }
}
