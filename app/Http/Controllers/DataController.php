<?php

namespace App\Http\Controllers;

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
        //获取数据
        $data = array('path' => $path);
        $content = file_get_contents($path);
        //分离信息与内容
        $content_list = explode("---\n", $content, 2);
        //填充内容
        $data['content'] = $content_list[1];
        //解析信息
        //title: CentOS Lnmp更改PHP版本 tags: [] categories: [] date: 2019-05-09 09:19:00

        //title: 测试tag author: yifan tags: - test categories: - test'c date: 2019-05-20 18:21:00
        //title: test 2 author: yifan date: 2019-05-20 18:22:14 tags:
        //替换信息
        $content_list[0] = str_replace(":\n  - ", ": ", $content_list[0]);
        $content_list[0] = str_replace("\n  - ", ",", $content_list[0]);


        //拆分
        $info_list = explode("\n", $content_list[0]);
        foreach ($info_list as $item) {
            if (empty($item)) {
                continue;
            }
            Log::info(json_encode($item));

            $sub = explode(': ', $item);
            if ($sub[0] === 'tags' || $sub[0] === 'categories') {
                //需要判断格式、数据
                if (isset($sub[1]) && !empty($sub[1])) { //数组识别
                    if (substr($sub[1], 0, 1) !== '[') {
                        $sub[1] = '[' . $sub[1] . ']';
                    }
                    $sub[1] = str_replace('[', '["', $sub[1]);
                    $sub[1] = str_replace(']', '"]', $sub[1]);
                    $sub[1] = str_replace(',', '","', $sub[1]);
                    if (!empty($sub[1]) && $sub[1] !== "[\"\"]") {
                        $data[$sub[0]] = json_decode($sub[1]);
                    } else {
                        $data[$sub[0]] = [];
                    }
                }
                continue;
            }
            if (substr($sub[0], strlen($sub[0]) - 1, strlen($sub[0])) === ':') {
                //admin中没有编辑过的tag，表示为"tag:"
                $data[substr($sub[0], 0, strlen($sub[0]) - 1)] = [];
                continue;
            }
            $data[$sub[0]] = $sub[1];
        }
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
