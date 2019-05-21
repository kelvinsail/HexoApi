<?php


namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller
{
    protected $jwt;


    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function postLogin(Request $request)
    {
        if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
            return response()->json(['code' => 201, 'data' => [], 'msg' => 'user_not_found']);
        }

        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $validator = $this->paramsValidator($request,
            array(
                'name' => 'required',
                'email' => 'required|unique:user,email',
                'password' => 'required'
            ));
        if ($validator) {
            return response()->json(['code' => 400, 'data' => $validator, 'msg' => 'fail']);
        }
        $user = new User();
        $user->name = $request->input('name');
        $user->password = Hash::make($request->input('password'));
        $user->email = $request->input('email');
        $user->save();
        return response()->json(['code' => 200, 'data' => $user, 'msg' => 'success']);
    }
}