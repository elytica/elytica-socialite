# Elytica Socialite Driver
This package ass elytica socialite driver.
To install
```
composer require elytica/elytica-socialite
php artisan vendor:publish --provider=Elytica\Socialite\ElyticaServiceProvider
```

Here is an example to use in laravel:

```
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/auth/redirect', function () {
        return Socialite::driver('elytica_service')->redirect();
    })->name('elytica_service.auth');

    Route::get('/auth/callback', function () {
       $user = Socialite::driver('elytica_service')->user();
       $authUser = User::updateOrCreate(
           ['email' => $user->getEmail()],
           [
            'name' => $user->getName(),
            'elytica_service_id' => $user->getId(),
            'elytica_service_token' => $user->token,
            'elytica_service_expires_in' => $user->expiresIn,
            'elytica_service_refreshToken' => $user->refreshToken,
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
    Route::post('logout', function(Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

```
