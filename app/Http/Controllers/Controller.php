<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{

    /*
     * 验证请求参数
     */
    public function paramsValidator(Request $request, $rules = [], $errorMessge = [])
    {
        $validator = Validator::make($request->all(), $rules, $errorMessge);
        if ($validator->fails()) {
            return $validator->errors()->all();
        } else {
            return false;
        }
    }

}
