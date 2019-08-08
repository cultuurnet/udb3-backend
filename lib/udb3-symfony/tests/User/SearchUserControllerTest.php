<?php

namespace CultuurNet\UDB3\Symfony\User;

use CultuurNet\UDB3\Symfony\Assert\JsonEquals;
use CultuurNet\UDB3\User\CultureFeedUserIdentityDetailsFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SearchUserControllerTest extends TestCase
{
    /**
     * @var \ICultureFeed|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cultureFeed;

    /**
     * @var SearchUserController
     */
    private $controller;

    /**
     * @var JsonEquals
     */
    private $jsonEquals;

    public function setUp()
    {
        $this->cultureFeed = $this->createMock(\ICultureFeed::class);

        $this->controller = new SearchUserController(
            $this->cultureFeed,
            new SearchQueryFactory(),
            new CultureFeedUserIdentityDetailsFactory()
        );

        $this->jsonEquals = new JsonEquals($this);
    }

    /**
     * @test
     */
    public function it_searches_users()
    {
        $request = new Request(['email' => '*@example.com']);

        $query = new \CultureFeed_SearchUsersQuery();
        $query->start = 0;
        $query->max = 30;
        $query->mbox = '*@example.com';
        $query->mboxIncludePrivate = true;

        $firstUser = new \CultureFeed_SearchUser();
        $firstUser->id = '07e68513-5b59-46a2-aa33-91459c1116f3';
        $firstUser->nick = 'john.doe';
        $firstUser->mbox = 'john.doe@example.com';

        $secondUser = new \CultureFeed_SearchUser();
        $secondUser->id = 'e649f113-f44b-4832-89e3-a3693264f8f8';
        $secondUser->nick = 'jane.doe';
        $secondUser->mbox = 'jane.doe@example.com';

        $mockResults = new \CultureFeed_ResultSet();
        $mockResults->objects = [$firstUser, $secondUser];
        $mockResults->total = 2;

        $this->cultureFeed->expects($this->once())
            ->method('searchUsers')
            ->with($query)
            ->willReturn($mockResults);

        $expectedJson = file_get_contents(__DIR__ . '/samples/search.json');

        $actualJson = $this->controller->search($request)->getContent();

        $this->jsonEquals->assert($expectedJson, $actualJson);
    }
}
