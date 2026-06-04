<?php

namespace App\Http\Controllers;

use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect(AuthRedirect::homeFor(auth()->user()));
    }
}
