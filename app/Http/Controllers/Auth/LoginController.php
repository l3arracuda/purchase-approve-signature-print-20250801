<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // ใช้ username แทน email
    public function username()
    {
        return 'username';
    }

    // ปรับแต่งการ validation
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    // เพิ่มการตรวจสอบ is_active
    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true]
        );
    }

    // Override redirectTo method
    protected function redirectTo()
    {
        return '/dashboard';
    }
}