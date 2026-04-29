<?php

declare(strict_types=1);

namespace Elytica\Socialite;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class ElyticaProvider extends AbstractProvider
{
    protected $scopes = ['user:read'];

    protected $scopeSeparator = ' ';

    protected string $baseUrl = 'https://service.elytica.com';

    public function setBaseUrl(string $baseUrl): static
    {
        if (! str_starts_with($baseUrl, 'https://')) {
            throw new InvalidArgumentException('Base URL must use HTTPS.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    public function refreshToken($refreshToken): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
