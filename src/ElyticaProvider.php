<?php

namespace Elytica\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class ElyticaProvider extends AbstractProvider
{
    protected string $baseUrl = 'https://service.elytica.com';

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase("{$this->baseUrl}/oauth/authorize", $state);
    }

    protected function getTokenUrl(): string
    {
        return "{$this->baseUrl}/oauth/token";
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get("{$this->baseUrl}/api/user", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'] ?? null,
            'nickname' => null,
            'name'     => $user['name'] ?? null,
            'email'    => $user['email'] ?? null,
            'avatar'   => null,
        ]);
    }
}
