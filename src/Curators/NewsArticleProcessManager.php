<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAdded;
use InvalidArgumentException;

final class NewsArticleProcessManager implements EventListenerInterface
{
    /**
     * @var OfferEditingServiceInterface
     */
    private $offerEditingService;

    /**
     * @var LabelFactory
     */
    private $labelFactory;

    public function __construct(
        OfferEditingServiceInterface $offerEditingService,
        LabelFactory $labelFactory
    ) {
        $this->offerEditingService = $offerEditingService;
        $this->labelFactory = $labelFactory;
    }

    /**
     * @param DomainMessage $domainMessage
     *
     * @uses handleNewsArticleAboutEventAdded
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $map = [
            NewsArticleAboutEventAdded::class => 'handleNewsArticleAboutEventAdded',
        ];

        $payload = $domainMessage->getPayload();
        $className = get_class($payload);
        if (isset($map[$className])) {
            $handlerMethodName = $map[$className];
            call_user_func([$this, $handlerMethodName], $payload);
        }
    }

    private function handleNewsArticleAboutEventAdded(NewsArticleAboutEventAdded $newsArticleAboutEventAdded): void
    {
        try {
            $label = $this->labelFactory->forPublisher($newsArticleAboutEventAdded->getPublisher());
        } catch (InvalidArgumentException $e) {
            $label = null;
        }

        if ($label) {
            $this->offerEditingService->addLabel(
                $newsArticleAboutEventAdded->getEventId(),
                $label
            );
        }
    }
}
