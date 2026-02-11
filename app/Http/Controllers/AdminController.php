<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Venue;

class AdminController extends Controller
{
    public function dashboard()
    {
        //Passando contagem para o dashboard
        $totalUsers = User::count();
        $totalVenues = Venue::count();

        return view('admin.dashboard', compact('totalUsers','totalVenues'));
    }

    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }
}
