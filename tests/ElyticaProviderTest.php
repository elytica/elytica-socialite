<?php

namespace Elytica\Socialite\Tests;

use Elytica\Socialite\ElyticaProvider;
use Illuminate\Http\Request;
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

    public function test_auth_url_contains_correct_base(): void
    {
        $url = $this->callProtected('getAuthUrl', ['test-state']);

        $this->assertStringContainsString('https://service.elytica.com/oauth/authorize', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
    }

    public function test_token_url(): void
    {
        $url = $this->callProtected('getTokenUrl');

        $this->assertSame('https://service.elytica.com/oauth/token', $url);
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

    private function callProtected(string $method, array $args = []): mixed
    {
        $reflection = new ReflectionClass($this->provider);
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->provider, $args);
    }
}
