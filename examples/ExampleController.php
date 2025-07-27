<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ExampleController extends Controller
{
    public function dashboard()
    {
        // This will trigger an error if Dashboard.vue doesn't exist
        return Inertia::render('Dashboard');
    }

    public function profile()
    {
        // This will trigger an error if Profile/Edit.vue doesn't exist
        return Inertia::render('Profile/Edit');
    }

    public function login()
    {
        // Using helper function
        return inertia('Auth/Login');
    }

    public function settings()
    {
        // Using method call
        return $this->inertia('Settings/Index');
    }
}
