<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateContactPointDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateContactPoint
    {
        $telephoneNumbers = [];
        if (isset($data['phone'])) {
            foreach ($data['phone'] as $phone) {
                $telephoneNumbers[] = new TelephoneNumber($phone);
            }
        }

        $emailAddresses = [];
        if (isset($data['email'])) {
            foreach ($data['email'] as $emailAddress) {
                $emailAddresses[] = new EmailAddress($emailAddress);
            }
        }

        $urls = [];
        if (isset($data['url'])) {
            foreach ($data['url'] as $url) {
                $urls[] = new Url($url);
            }
        }

        return new UpdateContactPoint(
            $this->organizerId,
            new ContactPoint(
                new TelephoneNumbers(...$telephoneNumbers),
                new EmailAddresses(...$emailAddresses),
                new Urls(...$urls)
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateContactPoint::class;
    }
}
