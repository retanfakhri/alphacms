<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // Dynamic redirection based on role
        if ($user && $user->hasAnyRole(['admin', 'editor'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Default redirect for Writers/Users
        return redirect()->intended(route('home'));
    }
}
