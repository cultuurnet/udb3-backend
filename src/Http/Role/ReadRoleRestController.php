<?php

namespace CultuurNet\UDB3\Http\Role;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\Services\RoleReadingServiceInterface;
use CultuurNet\UDB3\Http\HttpFoundation\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class ReadRoleRestController
{
    /**
     * @var RepositoryInterface
     */
    private $roleSearchRepository;

    /**
     * @var EntityServiceInterface
     */
    private $service;

    /**
     * @var RoleReadingServiceInterface
     */
    private $roleService;

    /**
     * @var \CultureFeed_User
     */
    private $currentUser;

    /**
     * @var UserPermissionsReadRepositoryInterface
     */
    private $permissionsRepository;

    /**
     * ReadRoleRestController constructor.
     * @param EntityServiceInterface $service
     * @param RoleReadingServiceInterface $roleService
     * @param \CultureFeed_User $currentUser
     * @param $authorizationList
     * @param RepositoryInterface $roleSearchRepository
     * @param UserPermissionsReadRepositoryInterface $permissionsRepository
     */
    public function __construct(
        EntityServiceInterface $service,
        RoleReadingServiceInterface $roleService,
        \CultureFeed_User $currentUser,
        $authorizationList,
        RepositoryInterface $roleSearchRepository,
        UserPermissionsReadRepositoryInterface $permissionsRepository
    ) {
        $this->service = $service;
        $this->roleService = $roleService;
        $this->currentUser = $currentUser;
        $this->authorizationList = $authorizationList;
        $this->roleSearchRepository = $roleSearchRepository;
        $this->permissionsRepository = $permissionsRepository;
    }

    /**
     * @param string $id
     * @return JsonResponse
     */
    public function get($id)
    {
        $response = null;

        $role = $this->service->getEntity($id);

        if ($role) {
            $response = JsonResponse::create()
                ->setContent($role);

            $response->headers->set('Vary', 'Origin');
        } else {
            $apiProblem = new ApiProblem('There is no role with identifier: ' . $id);
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);

            return new ApiProblemJsonResponse($apiProblem);
        }

        return $response;
    }

    /**
     * @param string $roleId
     * @return Response
     */
    public function getRoleUsers($roleId)
    {
        $document = $this->roleService->getUsersByRoleUuid(new UUID($roleId));

        $body = json_decode($document->getRawBody(), true);

        $response = JsonResponse::create()
            ->setContent(
                json_encode(
                    array_values(
                        $body
                    )
                )
            );

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    /**
     * @param $userId
     * @return Response
     */
    public function getUserRoles($userId)
    {
        $userId = new StringLiteral((string) $userId);
        $document = $this->roleService->getRolesByUserId($userId);

        // It's possible the document does not exist if the user exists but has
        // no roles, since we don't have a "UserCreated" event to listen to and
        // we can't create an empty document of roles in the projector.
        // @todo Should we check if the user exists using culturefeed?
        // @see https://jira.uitdatabank.be/browse/III-1292
        if ($document) {
            $body = json_decode($document->getRawBody(), true);
        } else {
            $body = [];
        }

        $response = JsonResponse::create()
            ->setContent(
                json_encode(
                    array_values(
                        $body
                    )
                )
            );

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    /**
     * @return Response
     */
    public function getCurrentUserRoles()
    {
        return $this->getUserRoles($this->currentUser->id);
    }

    /**
     * @return Response
     */
    public function getUserPermissions()
    {
        $userId = new StringLiteral($this->currentUser->id);

        if (in_array((string) $userId, $this->authorizationList['allow_all'])) {
            $list = $this->createPermissionsList(Permission::getConstants());
        } else {
            $list = array_map(
                function (Permission $permission) {
                    return $permission->getName();
                },
                $this->permissionsRepository->getPermissions($userId)
            );
        }

        return (new JsonResponse())
            ->setData($list)
            ->setPrivate();
    }

    /**
     * @param array $permissions
     * @return array
     */
    private function createPermissionsList(array $permissions)
    {
        $list = [];

        foreach ($permissions as $key => $name) {
            $list[] = $key;
        }

        return $list;
    }

    /**
     * @param $roleId
     * @return Response
     */
    public function getRoleLabels($roleId)
    {
        $document = $this->roleService->getLabelsByRoleUuid(new UUID($roleId));
        $body = json_decode($document->getRawBody(), true);
        $response = JsonResponse::create()
            ->setContent(
                json_encode(
                    array_values(
                        $body
                    )
                )
            );

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    /**
     * @return JsonResponse
     */
    public function getPermissions()
    {
        $list = $this->createPermissionsList(Permission::getConstants());

        return (new JsonResponse())
            ->setData($list)
            ->setPrivate();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function search(Request $request)
    {
        $query = $request->query->get('query') ?: '';
        $itemsPerPage = $request->query->get('limit') ?: 10;
        $start = $request->query->get('start') ?: 0;

        $result = $this->roleSearchRepository->search($query, $itemsPerPage, $start);

        $data = (object) array(
            'itemsPerPage' => $result->getItemsPerPage(),
            'member' => $result->getMember(),
            'totalItems' => $result->getTotalItems(),
        );

        return (new JsonResponse())
            ->setData($data)
            ->setPrivate();
    }
}
