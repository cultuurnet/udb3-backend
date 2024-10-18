<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContactPointDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("ContactPointDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('ContactPoint data should be an associative array.');
        }

        $phones = null;
        $emails = null;
        $urls = null;

        if (isset($data['phone'])) {
            $phones = array_map(
                function ($value) {
                    return new TelephoneNumber($value);
                },
                array_filter($data['phone'])
            );
            $phones = count($phones) > 0 ? new TelephoneNumbers(...$phones) : null;
        }

        if (isset($data['email'])) {
            $emails = array_map(
                function ($value) {
                    try {
                        return new EmailAddress($value);
                    } catch (\InvalidArgumentException $e) {
                        return null;
                    }
                },
                array_filter($data['email'])
            );
            $emails = array_filter($emails);
            $emails = count($emails) > 0 ? new EmailAddresses(...$emails) : null;
        }

        if (isset($data['url'])) {
            $urls = array_map(
                function ($value) {
                    try {
                        return new Url($value);
                    } catch (InvalidUrl $e) {
                        return null;
                    }
                },
                array_filter($data['url'])
            );
            $urls = array_filter($urls);
            $urls = count($urls) > 0 ? new Urls(...$urls) : null;
        }

        return new ContactPoint(
            $phones,
            $emails,
            $urls
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === ContactPoint::class;
    }
}
