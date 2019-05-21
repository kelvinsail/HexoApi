<?php


namespace App\Model;


use Illuminate\Support\Facades\Log;

/**
 * markdown文件model类
 *
 * Class MDModel
 * @package App\Model
 */
class MarkdownModel
{

    /**
     * 解析路径
     *
     * @param $path
     * @return mixed
     */
    public static function analysisFile($path)
    {
        $data = array('path' => $path);
        $data['content'] = file_get_contents($path);
        return self::analysisContent($data);
    }

    /**
     * 解析data对象中的content，并转化为数据
     *
     * @param $data
     * @return mixed
     */
    public static function analysisContent($data)
    {
        //分离信息与内容
        $content_list = explode("---\n", $data['content'], 2);
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
        return $data;
    }
}