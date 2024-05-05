<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Validator,Redirect,Response;
Use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Session;
 
class AuthController extends Controller
{
     
    // public function dashboard()
    // {
    //   if( Auth::check() && auth()->user()->verified ){
    //     return view('inventory');
    //   }
    //    return Redirect::to("login")->withSuccess('Opps! You do not have access');
    // }
 
    // public function create(array $data)
    // {
    //   return User::create([
    //     'name' => $data['name'],
    //     'email' => $data['email'],
    //     'password' => Hash::make($data['password'])
    //   ]);
    // }
    
    protected $redirectTo = '/tickets';

    public function logout() {
        Session::flush();
        Auth::logout();
        return Redirect('login');
    }

}