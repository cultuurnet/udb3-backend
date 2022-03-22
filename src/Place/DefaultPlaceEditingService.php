<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;

class DefaultPlaceEditingService extends DefaultOfferEditingService implements PlaceEditingServiceInterface
{
    /**
     * @inheritdoc
     */
    public function updateAddress($id, Address $address, Language $language)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            new UpdateAddress($id, $address, $language)
        );
    }
}
