<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Role;

class DashboardController extends Controller
{
    
    public function index()
    {

        $users = User::whereHas('roles', function($a){
                    $a->whereIn('id', [
                        Role::MANAGER,
                        Role::AGENT,
                        Role::AGENT_EBAY,
                        Role::CUSTOMER_SERVICE_SUPPORT,
                    ]);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate(10);

        return view('dashboard.index', compact('users'));
        
    }

}
