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

    /**
     * 登录
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
            return response()->json(['code' => 201, 'data' => [], 'msg' => '邮箱或密码错误']);
        }

        return response()->json(['code' => 200, 'data' => ['token' => $token], 'msg' => 'success']);
    }

    /**
     * 注册
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        //判断是否允许多人注册
        if (!env('ALLOW_REGISTER') && User::query()->count() > 0) {
            return response()->json(['code' => 500, 'data' => [], 'msg' => '未开放注册']);
        }
        //验证参数
        $validator = $this->paramsValidator($request,
            array(
                'name' => 'required',
                'email' => 'required|unique:user,email',
                'password' => 'required'
            ));
        if ($validator) {
            return response()->json(['code' => 400, 'data' => $validator, 'msg' => 'fail']);
        }

        //保存账号信息
        $user = new User();
        $user->name = $request->input('name');
        $user->password = Hash::make($request->input('password'));
        $user->email = $request->input('email');
        $user->save();
        return response()->json(['code' => 200, 'data' => $user, 'msg' => 'success']);
    }
}