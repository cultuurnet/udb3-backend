<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAdded;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use InvalidArgumentException;

final class NewsArticleProcessManager implements EventListener
{
    /**
     * @var LabelFactory
     */
    private $labelFactory;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        LabelFactory $labelFactory,
        CommandBus $commandBus
    ) {
        $this->labelFactory = $labelFactory;
        $this->commandBus = $commandBus;
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
            $this->commandBus->dispatch(
                new AddLabel(
                    $newsArticleAboutEventAdded->getEventId(),
                    $label
                )
            );
        }
    }
}
