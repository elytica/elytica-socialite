<?php

declare(strict_types=1);

namespace Elytica\Socialite\Tests;

use Elytica\Socialite\ElyticaProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use InvalidArgumentException;
use JsonException;
use Laravel\Socialite\Two\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ElyticaProviderTest extends TestCase
{
    private ElyticaProvider $provider;

    protected function setUp(): void
    {
        $request = Request::create('https://example.com/callback', 'GET', [
            'code'  => 'test-code',
            'state' => 'test-state',
        ]);

        $this->provider = new ElyticaProvider(
            $request,
            'test-client-id',
            'test-client-secret',
            'https://example.com/callback'
        );
    }

    public function test_auth_url_uses_default_base(): void
    {
        $url = $this->callProtected('getAuthUrl', ['test-state']);

        $this->assertStringContainsString('https://service.elytica.com/oauth/authorize', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
    }

    public function test_auth_url_includes_default_scope(): void
    {
        $url = $this->callProtected('getAuthUrl', ['test-state']);

        $this->assertStringContainsString('user%3Aread', $url);
    }

    public function test_token_url_uses_default_base(): void
    {
        $this->assertSame(
            'https://service.elytica.com/oauth/token',
            $this->callProtected('getTokenUrl')
        );
    }

    public function test_custom_base_url_is_applied(): void
    {
        $this->provider->setBaseUrl('https://sandbox.elytica.com');

        $this->assertSame(
            'https://sandbox.elytica.com/oauth/token',
            $this->callProtected('getTokenUrl')
        );

        $authUrl = $this->callProtected('getAuthUrl', ['test-state']);
        $this->assertStringContainsString('https://sandbox.elytica.com/oauth/authorize', $authUrl);
    }

    public function test_set_base_url_strips_trailing_slash(): void
    {
        $this->provider->setBaseUrl('https://sandbox.elytica.com/');

        $this->assertSame(
            'https://sandbox.elytica.com/oauth/token',
            $this->callProtected('getTokenUrl')
        );
    }

    public function test_set_base_url_rejects_non_https(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->provider->setBaseUrl('http://sandbox.elytica.com');
    }

    public function test_get_user_by_token_returns_decoded_response(): void
    {
        $payload = ['id' => 1, 'name' => 'Test', 'email' => 'test@example.com'];
        $this->mockHttpResponse(200, $payload);

        $result = $this->callProtected('getUserByToken', ['some-token']);

        $this->assertSame($payload, $result);
    }

    public function test_get_user_by_token_throws_on_invalid_json(): void
    {
        $this->mockHttpResponse(200, null, 'not-json');

        $this->expectException(JsonException::class);

        $this->callProtected('getUserByToken', ['some-token']);
    }

    public function test_maps_user_to_object(): void
    {
        $user = $this->callProtected('mapUserToObject', [[
            'id'    => 42,
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(42, $user->getId());
        $this->assertSame('Test User', $user->getName());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertNull($user->getNickname());
        $this->assertNull($user->getAvatar());
    }

    public function test_maps_user_with_missing_fields(): void
    {
        $user = $this->callProtected('mapUserToObject', [['id' => 1]]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getName());
        $this->assertNull($user->getEmail());
    }

    public function test_refresh_token_returns_new_token_data(): void
    {
        $payload = [
            'access_token'  => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in'    => 1296000,
            'token_type'    => 'Bearer',
        ];
        $this->mockHttpResponse(200, $payload);

        $result = $this->provider->refreshToken('old-refresh-token');

        $this->assertSame('new-access-token', $result['access_token']);
        $this->assertSame('new-refresh-token', $result['refresh_token']);
        $this->assertSame(1296000, $result['expires_in']);
    }

    public function test_refresh_token_throws_on_invalid_json(): void
    {
        $this->mockHttpResponse(200, null, 'bad-json');

        $this->expectException(JsonException::class);

        $this->provider->refreshToken('old-refresh-token');
    }

    private function mockHttpResponse(int $status, ?array $body, ?string $rawBody = null): void
    {
        $responseBody = $rawBody ?? json_encode($body);
        $mock = new MockHandler([new Response($status, [], $responseBody)]);
        $this->provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));
    }

    private function callProtected(string $method, array $args = []): mixed
    {
        $reflection = new ReflectionClass($this->provider);
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->provider, $args);
    }
}
