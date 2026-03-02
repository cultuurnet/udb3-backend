<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BookingInfoNormalizer implements NormalizerInterface
{
    /**
     * @param BookingInfo $bookingInfo
     */
    public function normalize($bookingInfo, $format = null, array $context = []): array
    {
        $serialized = array_filter(
            [
                'phone' => $bookingInfo->getTelephoneNumber() ? $bookingInfo->getTelephoneNumber()->toString() : null,
                'email' => $bookingInfo->getEmailAddress() ? $bookingInfo->getEmailAddress()->toString() : null,
                'url' => $bookingInfo->getWebsite() ? $bookingInfo->getWebsite()->getUrl()->toString() : null,
            ]
        );

        if ($bookingInfo->getBookingDateRange() && $bookingInfo->getBookingDateRange()->getFrom()) {
            $serialized['availabilityStarts'] = $bookingInfo->getBookingDateRange()->getFrom()->format(\DATE_ATOM);
        }

        if ($bookingInfo->getBookingDateRange() && $bookingInfo->getBookingDateRange()->getTo()) {
            $serialized['availabilityEnds'] = $bookingInfo->getBookingDateRange()->getTo()->format(\DATE_ATOM);
        }

        if ($bookingInfo->getWebsite()) {
            $serialized['urlLabel'] = (new TranslatedWebsiteLabelNormalizer())->normalize($bookingInfo->getWebsite()->getLabel());
        }

        return $serialized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === BookingInfo::class;
    }
}
