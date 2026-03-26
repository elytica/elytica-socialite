# Elytica Socialite Driver

Laravel Socialite driver for Elytica authentication.

## Installation

```bash
composer require elytica/elytica-socialite
php artisan vendor:publish --provider="Elytica\Socialite\ElyticaServiceProvider"
```

## Configuration

Add the following to your `.env` file:

```env
ELYTICA_SERVICE_CLIENT_ID=your-client-id
ELYTICA_SERVICE_CLIENT_SECRET=your-client-secret
ELYTICA_SERVICE_REDIRECT_URI=https://your-app.com/auth/callback
ELYTICA_SERVICE_BASE_URL=https://service.elytica.com  # optional, defaults to service.elytica.com
```

## Usage

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

Route::middleware('guest')->group(function () {
    Route::get('/auth/redirect', function () {
        return Socialite::driver('elytica_service')->redirect();
    })->name('elytica_service.auth');

    Route::get('/auth/callback', function () {
        $user = Socialite::driver('elytica_service')->user();

        $authUser = User::updateOrCreate(
            ['email' => $user->getEmail()],
            [
                'name'                             => $user->getName(),
                'elytica_service_id'               => $user->getId(),
                'elytica_service_token'            => $user->token,
                'elytica_service_refresh_token'    => $user->refreshToken,
                'elytica_service_token_expires_at' => now()->addSeconds($user->expiresIn),
            ]
        );

        Auth::login($authUser, true);

        return redirect('/dashboard');
    });

    Route::get('login', function () {
        return view('welcome');
    })->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
```

## Scopes

The driver requests `user:read` by default. You can request additional scopes using `.scopes()`:

```php
Socialite::driver('elytica_service')->scopes(['user:read', 'projects:read', 'jobs:read'])->redirect();
```

Available scopes: `user:read`, `projects:read`, `projects:write`, `projects:delete`,
`jobs:read`, `jobs:write`, `jobs:delete`, `files:read`, `files:write`, `files:delete`,
`applications:read`, `mcp:use`.

## Refreshing tokens

Access tokens expire after 15 days. Use the stored refresh token to get a new access token without requiring the user to log in again:

```php
use Laravel\Socialite\Facades\Socialite;

$provider = Socialite::driver('elytica_service');
$newTokenData = $provider->refreshToken($user->elytica_service_refresh_token);

$user->update([
    'elytica_service_token'            => $newTokenData['access_token'],
    'elytica_service_refresh_token'    => $newTokenData['refresh_token'],
    'elytica_service_token_expires_at' => now()->addSeconds($newTokenData['expires_in']),
]);
```

Refresh tokens expire after 30 days.
