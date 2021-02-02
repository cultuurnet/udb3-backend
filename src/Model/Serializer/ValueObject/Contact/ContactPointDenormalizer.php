<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContactPointDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = array())
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
                $data['phone']
            );
            $phones = new TelephoneNumbers(...$phones);
        }

        if (isset($data['email'])) {
            $emails = array_map(
                function ($value) {
                    return new EmailAddress($value);
                },
                $data['email']
            );
            $emails = new EmailAddresses(...$emails);
        }

        if (isset($data['url'])) {
            $urls = array_map(
                function ($value) {
                    return new Url($value);
                },
                $data['url']
            );
            $urls = new Urls(...$urls);
        }

        return new ContactPoint(
            $phones,
            $emails,
            $urls
        );
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === ContactPoint::class;
    }
}
