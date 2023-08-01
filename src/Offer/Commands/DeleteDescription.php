<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\OfferType;

final class DeleteDescription extends AbstractCommand
{
    private Language $language;
    private OfferType $offerType;

    public function __construct(string $offerId, OfferType $offerType, Language $language)
    {
        parent::__construct($offerId);

        $this->offerType = $offerType;
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getOfferType(): OfferType
    {
        return $this->offerType;
    }
}