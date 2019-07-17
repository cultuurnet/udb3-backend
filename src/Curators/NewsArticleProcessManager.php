<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Silex\Curators\Events\NewsArticleAboutEventAdded;

final class NewsArticleProcessManager implements EventListenerInterface
{
    private const LABEL = 'curatoren';
    private const LABEL_VISIBLE = false;

    /**
     * @var OfferEditingServiceInterface
     */
    private $offerEditingService;

    public function __construct(OfferEditingServiceInterface $offerEditingService)
    {
        $this->offerEditingService = $offerEditingService;
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
        $this->offerEditingService->addLabel(
            $newsArticleAboutEventAdded->getEventId(),
            new Label(self::LABEL, self::LABEL_VISIBLE)
        );
    }
}
