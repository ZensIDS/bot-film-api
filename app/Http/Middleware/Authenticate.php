<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Halaman TWA (customer) tidak pakai login form, jadi hanya area /admin
        // yang diarahkan ke halaman login. Selain itu, biarkan null (tidak redirect)
        // supaya tidak error karena route('login') belum tentu ada di app ini.
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        return null;
    }
}
