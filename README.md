# Elytica Socialite Driver

Laravel Socialite driver for Elytica authentication.

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [User model](#user-model)
- [Usage](#usage)
- [Scopes](#scopes)
- [Refreshing tokens](#refreshing-tokens)
- [Custom base URL](#custom-base-url)
- [Testing](#testing)

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- `laravel/socialite` ^5.14

The package is auto-discovered — no manual provider registration needed.

## Installation

```bash
composer require elytica/elytica-socialite
```

Publish the config and migrations:

```bash
php artisan vendor:publish --provider="Elytica\Socialite\ElyticaServiceProvider" --tag=config
php artisan vendor:publish --provider="Elytica\Socialite\ElyticaServiceProvider" --tag=migrations
php artisan migrate
```

The migration adds the following nullable columns to your `users` table and makes `password` nullable (users authenticated via Elytica won't have a local password):

| Column                              | Type      |
| ----------------------------------- | --------- |
| `elytica_service_id`                | unsigned bigint |
| `elytica_service_token`             | text      |
| `elytica_service_refresh_token`     | text      |
| `elytica_service_token_expires_at`  | timestamp |

## Configuration

Add the following to your `.env` file:

```env
ELYTICA_SERVICE_CLIENT_ID=your-client-id
ELYTICA_SERVICE_CLIENT_SECRET=your-client-secret
ELYTICA_SERVICE_REDIRECT_URI=https://your-app.com/auth/callback
ELYTICA_SERVICE_BASE_URL=https://service.elytica.com  # optional, defaults to service.elytica.com
```

The package merges its config into Laravel's `config/services.php` under the `elytica_service` key, so you can also override values there:

```php
// config/services.php
'elytica_service' => [
    'client_id'     => env('ELYTICA_SERVICE_CLIENT_ID'),
    'client_secret' => env('ELYTICA_SERVICE_CLIENT_SECRET'),
    'redirect'      => env('ELYTICA_SERVICE_REDIRECT_URI'),
    'base_url'      => env('ELYTICA_SERVICE_BASE_URL'),
],
```

## User model

Add the Elytica columns to your `User` model so they can be mass-assigned and cast properly:

```php
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'elytica_service_id',
        'elytica_service_token',
        'elytica_service_refresh_token',
        'elytica_service_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'elytica_service_token',
        'elytica_service_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'                => 'datetime',
            'password'                         => 'hashed',
            'elytica_service_token_expires_at' => 'datetime',
        ];
    }
}
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

Access tokens expire after 15 days; refresh tokens expire after 30 days. Use the stored refresh token to mint a new access token without prompting the user to log in again:

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

`refreshToken()` returns the decoded JSON response as an array (`access_token`, `refresh_token`, `expires_in`, `token_type`, ...). It throws `JsonException` on a malformed response and `GuzzleHttp\Exception\RequestException` on transport/HTTP errors — handle these in your refresh flow.

### Refreshing proactively

A common pattern is a middleware that refreshes the access token before it expires:

```php
public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    if ($user && $user->elytica_service_token_expires_at?->isPast()) {
        $data = Socialite::driver('elytica_service')
            ->refreshToken($user->elytica_service_refresh_token);

        $user->update([
            'elytica_service_token'            => $data['access_token'],
            'elytica_service_refresh_token'    => $data['refresh_token'],
            'elytica_service_token_expires_at' => now()->addSeconds($data['expires_in']),
        ]);
    }

    return $next($request);
}
```

## Custom base URL

The driver defaults to `https://service.elytica.com`. Override it via `ELYTICA_SERVICE_BASE_URL`, or programmatically:

```php
Socialite::driver('elytica_service')->setBaseUrl('https://staging.elytica.example');
```

The base URL **must** use `https://` — passing an `http://` URL throws `InvalidArgumentException`. Trailing slashes are stripped automatically.

## Testing

```bash
composer install
./vendor/bin/phpunit
```

To collect coverage you need Xdebug or PCOV installed; then:

```bash
./vendor/bin/phpunit --coverage-text
```

CI runs the suite across the supported PHP × Laravel matrix — see `.github/workflows/tests.yml`.
