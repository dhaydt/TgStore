<?php

namespace App\Http\Controllers\Api\Auth;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request){
        $data = $request->only('name', 'password');
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ], [
            'name.required' => 'Username Tidak bisa kosong',
            'password.required' => 'Password tidak bisa kosong',
            // 'password.min:8' => 'Minimal password 8 huruf!',
        ]);

        $fieldType = filter_var($request->name, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if(auth()->attempt(array($fieldType => $request['name'], 'password' => $request['password'])))
        {
            $token = $request->user()->createToken($request['name']);
            $data = [
                'token' => $token->plainTextToken,
                'user' => $request->user(),
            ];

            return response()->json($data);
        }else{
            return response()->json(Helpers::responseApi('fail', 'Nama atau password salah'));
        }

    }
}
