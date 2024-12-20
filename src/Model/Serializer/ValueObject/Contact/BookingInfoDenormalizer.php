<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\DateTimeFactory;
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

        if (!empty($data['phone'])) {
            $phone = new TelephoneNumber($data['phone']);
        }

        if (!empty($data['email'])) {
            $email = new EmailAddress($data['email']);
        }

        if (!empty($data['url']) && !empty($data['urlLabel'])) {
            $url = new Url($data['url']);

            /* @var TranslatedWebsiteLabel $label */
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
            $starts = DateTimeFactory::fromISO8601($data['availabilityStarts']);
        }

        $ends = null;
        if (isset($data['availabilityEnds'])) {
            $ends = DateTimeFactory::fromISO8601($data['availabilityEnds']);
        }

        if ($starts || $ends) {
            // Avoid crashes when the start date is after the end date for legacy data inside the event store.
            if ($starts && $ends && $starts > $ends) {
                $starts = $ends;
            }
            $availability = new BookingAvailability($starts, $ends);
        }

        return new BookingInfo(
            $website,
            $phone,
            $email,
            $availability
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BookingInfo::class;
    }
}
