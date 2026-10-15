<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingDateRange;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BookingInfoDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $websiteLabelDenormalizer;

    private bool $skipsInvalidEmailAndUrl = false;

    public function __construct(DenormalizerInterface $websiteLabelDenormalizer = null)
    {
        if (!$websiteLabelDenormalizer) {
            $websiteLabelDenormalizer = new TranslatedWebsiteLabelDenormalizer();
        }

        $this->websiteLabelDenormalizer = $websiteLabelDenormalizer;
    }

    /**
     * For data from the event store or a read model, which can hold an email or url that is no longer valid.
     * Requests and imports stay strict, because the JSON schema accepts urls that Url does not.
     * Once the RDF read side is gone, this can move into the events that deserialize the stored data.
     */
    public static function forStoredData(): self
    {
        $denormalizer = new self();
        $denormalizer->skipsInvalidEmailAndUrl = true;

        return $denormalizer;
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
        $url = null;
        $website = null;
        $bookingDateRange = null;

        if (!empty($data['phone'])) {
            $phone = new TelephoneNumber($data['phone']);
        }

        if (!empty($data['email'])) {
            try {
                $email = new EmailAddress($data['email']);
            } catch (InvalidEmailAddress $invalidEmailAddress) {
                if (!$this->skipsInvalidEmailAndUrl) {
                    throw $invalidEmailAddress;
                }
            }
        }

        if (!empty($data['url']) && !empty($data['urlLabel'])) {
            try {
                $url = new Url($data['url']);
            } catch (InvalidUrl $invalidUrl) {
                if (!$this->skipsInvalidEmailAndUrl) {
                    throw $invalidUrl;
                }
            }
        }

        if ($url !== null) {
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
            $bookingDateRange = new BookingDateRange($starts, $ends);
        }

        return new BookingInfo(
            $website,
            $phone,
            $email,
            $bookingDateRange
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BookingInfo::class;
    }
}
