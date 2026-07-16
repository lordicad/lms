<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 12 no longer includes this by default, but every teacher-facing controller
    // leans on $this->authorize() to enforce the owner-only policies.
    use AuthorizesRequests;
}
