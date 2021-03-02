<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BookingInfoDenormalizer implements DenormalizerInterface
{
    /**
     * @var DenormalizerInterface
     */
    private $websiteLabelDenormalizer;

    public function __construct(DenormalizerInterface $websiteLabelDenormalizer = null)
    {
        if (!$websiteLabelDenormalizer) {
            $websiteLabelDenormalizer = new TranslatedWebsiteLabelDenormalizer();
        }

        $this->websiteLabelDenormalizer = $websiteLabelDenormalizer;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("BookingInfoDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('BookingInfo data should be an associative array.');
        }

        $phone = null;
        $email = null;
        $website = null;
        $availability = null;

        if (isset($data['phone'])) {
            $phone = new TelephoneNumber($data['phone']);
        }

        if (isset($data['email'])) {
            $email = new EmailAddress($data['email']);
        }

        if (isset($data['url']) && isset($data['urlLabel'])) {
            /* @var TranslatedWebsiteLabel $label */
            $url = new Url($data['url']);
            $label = $this->websiteLabelDenormalizer->denormalize(
                $data['urlLabel'],
                TranslatedWebsiteLabel::class,
                null,
                $context
            );
            $website = new WebsiteLink($url, $label);
        }

        $starts = null;
        if (isset($data['availabilityStarts'])) {
            $starts = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['availabilityStarts']);
        }

        $ends = null;
        if (isset($data['availabilityEnds'])) {
            $ends = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['availabilityEnds']);
        }

        if ($starts || $ends) {
            $availability = new BookingAvailability($starts, $ends);
        }

        return new BookingInfo(
            $website,
            $phone,
            $email,
            $availability
        );
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === BookingInfo::class;
    }
}
