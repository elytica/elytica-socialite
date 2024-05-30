<?php
namespace Elytica\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\ProviderInterface;

class ElyticaProvider extends AbstractProvider implements ProviderInterface
{
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://service.elytica.com/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://service.elytica.com/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://service.elytica.com/api/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => null,
            'name'  => $user['name'],
            'email' => $user['email'],
            'avatar' => null,
        ]);
    }

}
