<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\EventBus\Middleware\DisableMailsMiddleware;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Ownership\Commands\ApproveOwnership;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class AddOwnershipCommand extends AbstractCommand
{
    private const ITEM_TYPE = 'item-type';

    private const ITEM_ID = 'item-id';

    private const USER = 'user';

    private const ENABLE_MAILS = 'enable-mails';

    private UuidFactory $uuidFactory;

    private UserIdentityResolver $userIdentityResolver;

    private OwnershipSearchRepository $ownershipSearchRepository;

    private DocumentRepository $organizerRepository;

    public function __construct(
        CommandBus $commandBus,
        UuidFactory $uuidFactory,
        UserIdentityResolver $userIdentityResolver,
        OwnershipSearchRepository $ownershipSearchRepository,
        DocumentRepository $organizerRepository
    ) {
        parent::__construct($commandBus);
        $this->uuidFactory = $uuidFactory;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerRepository = $organizerRepository;
    }

    public function configure(): void
    {
        $this->setName('ownership:add-ownership')
            ->setDescription('Add ownership via an UiTID or email')
            ->addArgument(
                self::ITEM_TYPE,
                InputOption::VALUE_REQUIRED,
                'The type(at present only organizer is allowed) of the item to which you want to add the ownership.'
            )
            ->addArgument(
                self::ITEM_ID,
                InputOption::VALUE_REQUIRED,
                'The id of the item to which you want to add the ownership.'
            )
            ->addArgument(
                self::USER,
                InputOption::VALUE_REQUIRED,
                'The id or email of a user give ownership to.'
            )->AddOption(
                self::ENABLE_MAILS,
                null,
                InputOption::VALUE_NONE,
                'Option to enable mailing during the CLI Command, default is not sending mails.'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $itemType = $input->getArgument(self::ITEM_TYPE);
        $itemId = $input->getArgument(self::ITEM_ID);
        $user = $input->getArgument(self::USER);
        $sendMails = $input->getOption(self::ENABLE_MAILS);

        if (!$this->itemExists($itemId)) {
            $output->writeln('Organizer does not exist.');
            return self::FAILURE;
        }

        $userId = $this->getUserId($user);
        if ($userId === null) {
            $output->writeln('No user found for ' . $user);
            return self::FAILURE;
        }

        $ownershipItemCollection = $this->ownershipExist($itemId, $userId);

        if ($ownershipItemCollection->count() > 0) {
            /** @var OwnershipItem $ownershipItem */
            $ownershipItem = $ownershipItemCollection->getFirst();
            $output->writeln('Ownership already exists: ' . $ownershipItem->getId());
            return self::FAILURE;
        }

        $ownerShipId = new Uuid($this->uuidFactory->uuid4()->toString());

        $requestOwnership = new RequestOwnership(
            $ownerShipId,
            new Uuid($itemId),
            new ItemType($itemType),
            $userId,
            new UserId(UUID::NIL)
        );

        if (!$sendMails) {
            DisableMailsMiddleware::disableMails();
        }

        $this->commandBus->dispatch($requestOwnership);

        $approveOwnership = new ApproveOwnership($ownerShipId);

        $this->commandBus->dispatch($approveOwnership);

        if (!$sendMails) {
            DisableMailsMiddleware::enableMails();
        }

        return self::SUCCESS;
    }

    private function ownershipExist(string $itemId, UserId $ownerId): OwnershipItemCollection
    {
        return $this->ownershipSearchRepository->search(
            new SearchQuery([
                new SearchParameter('itemId', $itemId),
                new SearchParameter('ownerId', $ownerId->toString()),
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ])
        );
    }

    private function getUserId(string $user): ?UserId
    {
        try {
            $userEmail = new EmailAddress($user);
            $userDetails = $this->userIdentityResolver->getUserByEmail($userEmail);
        } catch (InvalidEmailAddress $exception) {
            $userDetails = $this->userIdentityResolver->getUserById($user);
        }
        return $userDetails !== null ? new UserId($userDetails->getUserId()) : null;
    }

    private function itemExists(string $itemId): bool
    {
        try {
            $this->organizerRepository->fetch($itemId);
            return true;
        } catch (DocumentDoesNotExist $e) {
            return false;
        }
    }
}
