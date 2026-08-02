<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;

class LogoutResponse extends \Filament\Http\Responses\Auth\LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->to("/portals");
    }
}
