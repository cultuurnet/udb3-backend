<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\Validator\OfferCommandValidator;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;

final class DeleteOfferHandler implements CommandHandler
{
    private OfferRepository $offerRepository;
    private OfferCommandValidator $validator;

    public function __construct(OfferRepository $offerRepository, OfferCommandValidator $validator)
    {
        $this->offerRepository = $offerRepository;
        $this->validator = $validator;
    }

    /**
     * @throws ApiProblem
     */
    public function handle($command): void
    {
        if (!$command instanceof DeleteOffer) {
            return;
        }

        if (!$this->validator->isValid($command->getItemId())) {
            throw $this->validator->getApiProblem();
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->delete();
        $this->offerRepository->save($offer);
    }
}
