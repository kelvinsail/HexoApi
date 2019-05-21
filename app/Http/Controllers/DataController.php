<?php

namespace App\Http\Controllers;

use App\Model\MarkdownModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * 数据获取相关路由
 *
 * Class DataController
 * @package App\Http\Controllers
 */
class DataController extends Controller
{
    protected $post_list = array("_discarded", "_drafts", "_posts", "_trash");
    protected $page_list = array("about", "categories", "tags");
    protected $res_list = array("images");

    protected $dir_list = array("posts", "pages");


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //检查目录是否已创建
        foreach ($this->dir_list as $dir) {
            $path = base_path('public/' . $dir);
            if (!file_exists($path)) {
                mkdir($path);
            }
        }
        //检查软连接是否已创建
        self::checkLink($this->post_list, 'posts');
        self::checkLink($this->page_list, 'pages');
        self::checkLink($this->res_list);
    }

    /**
     * 检查软连接
     *
     * @param $list
     */
    public static function checkLink($list, $dir = null)
    {
        foreach ($list as $item) {
            $public_path = base_path('public/' . (empty($dir) ? '' : $dir . '/') . $item);
            //创建软连接，指向hexo文件夹
            if (!file_exists($public_path)) {
                $source_path = env('SOURCE_PATH') . '/' . $item;
                if (file_exists($source_path)) {
                    exec('ln -s ' . $source_path . ' ' . $public_path);
                } else {
                    Log::error("文件夹：$source_path ，不存在");
                }
            }
        }
    }

    /**
     * 获取文件夹下的文件列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDirList(Request $request)
    {
        $path = $request->get('path');
        if ($path === "/" || empty($path)) {
            $data = array();
            foreach ($this->dir_list as $item) {
                $data[] = self::getFileInfo($item);
            }
            return response()->json(['code' => 201, 'data' => $data, 'msg' => 'success']);
        } else if (file_exists($path)) {
            $data = self::explodeList($path);
            return response()->json(['code' => 200, 'data' => array('path' => $path, 'list' => $data), 'msg' => 'success']);
        }

    }

    /**
     * 获取文件内容
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileContent(Request $request)
    {
        //验证参数
        $validate = $this->paramsValidator($request, [
            'path' => 'required',//路径
        ]);

        if ($validate) {
            //参数非法
            return response()->json(['code' => 401, 'data' => $validate, 'msg' => 'fail']);
        }
        $path = $request->get('path');
        if (!file_exists($path)) {
            return response()->json(['code' => 200, 'data' => [], 'msg' => '文件不存在']);
        }
        if (is_dir($path)) {
            return response()->json(['code' => 200, 'data' => [], 'msg' => '非文件路径']);
        }
        //获取数据并解析
        $data = MarkdownModel::analysisFile($path);
        return response()->json(['code' => 200, 'data' => $data, 'msg' => 'success']);
    }

    /**
     * 列出path路径下的文件列表信息，拆分exec输出结果为list
     *
     * @param $path
     * @return array
     */
    private static function explodeList($path)
    {
        $dir_list = shell_exec('ls ' . base_path('public/' . $path));
        $data = explode("\n", $dir_list);
        $result = array();
        foreach ($data as $item) {
            if ($item === "") {
                continue;
            }
            $result[] = self::getFileInfo($path . '/' . $item);
        }
        return $result;
    }

    /**
     * 统一列表数据格式
     *
     * @param $path
     * @return array
     */
    public static function getFileInfo($path)
    {
        $data = array();
        $data['file_path'] = $path;
        Log::info($path);
        if (file_exists($path)) {
            $data['is_dir'] = is_dir($path);
            $data['file_name'] = basename($path);
            $data['file_size'] = $data['is_dir'] ? 0 : round(filesize($path) / 1024, 2);
        }
        return $data;
    }
}
