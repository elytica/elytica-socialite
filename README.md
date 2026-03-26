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
                'name'                            => $user->getName(),
                'elytica_service_id'              => $user->getId(),
                'elytica_service_token'           => $user->token,
                'elytica_service_refresh_token'   => $user->refreshToken,
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
