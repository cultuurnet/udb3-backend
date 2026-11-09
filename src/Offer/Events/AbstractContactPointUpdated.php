<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

abstract class AbstractContactPointUpdated extends AbstractEvent
{
    protected ContactPoint $contactPoint;

    final public function __construct(string $id, ContactPoint $contactPoint)
    {
        parent::__construct($id);
        $this->contactPoint = $contactPoint;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'contactPoint' => (new ContactPointNormalizer())->normalize($this->contactPoint),
        ];
    }

    public static function deserialize(array $data): AbstractContactPointUpdated
    {
        return new static(
            $data['item_id'],
            (new ContactPointDenormalizer())->denormalize(
                self::withoutInvalidEmailsAndUrls($data['contactPoint']),
                ContactPoint::class
            )
        );
    }

    private static function withoutInvalidEmailsAndUrls(array $contactPoint): array
    {
        if (isset($contactPoint['email'])) {
            $contactPoint['email'] = array_values(array_filter(
                $contactPoint['email'],
                function ($email): bool {
                    if (!is_string($email)) {
                        return false;
                    }

                    try {
                        new EmailAddress($email);
                        return true;
                    } catch (InvalidEmailAddress) {
                        return false;
                    }
                }
            ));
        }

        if (isset($contactPoint['url'])) {
            $contactPoint['url'] = array_values(array_filter(
                $contactPoint['url'],
                function ($url): bool {
                    if (!is_string($url)) {
                        return false;
                    }

                    try {
                        new Url($url);
                        return true;
                    } catch (InvalidUrl) {
                        return false;
                    }
                }
            ));
        }

        return $contactPoint;
    }
}
