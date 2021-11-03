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
        $telephoneNumbers = array_map(static fn ($phone) => new TelephoneNumber($phone), $data['phone'] ?? []);
        $emailAddresses = array_map(static fn ($email) => new EmailAddress($email), $data['email'] ?? []);
        $urls = array_map(static fn ($url) => new Url($url), $data['url'] ?? []);

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
