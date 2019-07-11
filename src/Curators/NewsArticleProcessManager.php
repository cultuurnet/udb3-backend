<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Silex\Curators\Events\NewsArticleAboutEventAdded;

final class NewsArticleProcessManager implements EventListenerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
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
        $this->commandBus->dispatch(
            new AddLabel(
                $newsArticleAboutEventAdded->getEventId(),
                new Label('curatoren', false)
            )
        );
    }
}
