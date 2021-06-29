<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\TestCase;

final class Udb3TokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_uid_claim_as_id_if_present(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'uid' => new Basic('uid', '6e3ef9b3-e37b-428e-af30-05f3a96dbbe4'),
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('6e3ef9b3-e37b-428e-af30-05f3a96dbbe4', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_uitid_v1_claim_as_id_if_present(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('b55f041e-5c5e-4850-9fb8-8cf73d538c56', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_sub_claim_as_id(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_trimmed_clients_sub_claim_as_id(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'ce6abd8f-b1e2-4bce-9dde-08af64438e87@clients'),
                ]
            )
        );

        $this->assertEquals('client|ce6abd8f-b1e2-4bce-9dde-08af64438e87', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_client_id_from_azp_claim_if_present(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'azp' => new Basic('azp', 'jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ'),
                ]
            )
        );

        $this->assertEquals('jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ', $token->getClientId());
        $this->assertTrue($token->isAccessToken());
    }

    /**
     * @test
     */
    public function it_returns_null_as_client_id_if_azp_claim_is_missing(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertNull($token->getClientId());
        $this->assertFalse($token->isAccessToken());
    }

    /**
     * @test
     */
    public function it_can_check_if_the_aud_claim_contains_a_specific_value(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'aud' => new Basic('aud', ['vsCe0hXlLaR255wOrW56Fau7vYO5qvqD']),
                ]
            )
        );

        $this->assertTrue($token->audienceContains('vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'));
        $this->assertFalse($token->audienceContains('bla'));
    }

    /**
     * @test
     */
    public function it_returns_false_if_the_aud_claim_is_missing(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                []
            )
        );

        $this->assertFalse($token->audienceContains('vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'));
    }

    /**
     * @test
     */
    public function it_can_handle_string_as_aud_claim(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'aud' => new Basic('aud', 'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'),
                ]
            )
        );

        $this->assertTrue($token->audienceContains('vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'));
        $this->assertFalse($token->audienceContains('bla'));
    }

    /**
     * @test
     */
    public function it_can_check_if_a_token_can_be_used_on_entry_api(): void
    {
        $tokenWithoutApis = new Udb3Token(new Token());
        $tokenWithOnlyEntry = $this->createTokenForPubliqApis('entry');
        $tokenWithMultipleIncludingEntry = $this->createTokenForPubliqApis('ups entry sapi');
        $tokenWithMultipleExcludingEntry = $this->createTokenForPubliqApis('ups sapi');
        $tokenWithMultipleAndTooManySpaces = $this->createTokenForPubliqApis(' ups  entry  sapi ');

        $this->assertFalse($tokenWithoutApis->canUseEntryAPI());
        $this->assertTrue($tokenWithOnlyEntry->canUseEntryAPI());
        $this->assertTrue($tokenWithMultipleIncludingEntry->canUseEntryAPI());
        $this->assertFalse($tokenWithMultipleExcludingEntry->canUseEntryAPI());
        $this->assertTrue($tokenWithMultipleAndTooManySpaces->canUseEntryAPI());
    }

    private function createTokenForPubliqApis(string $publiqApis): Udb3Token
    {
        return new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'https://publiq.be/publiq-apis' => new Basic('https://publiq.be/publiq-apis', $publiqApis),
                ]
            )
        );
    }
}
