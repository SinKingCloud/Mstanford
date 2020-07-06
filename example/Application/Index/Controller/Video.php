<?php
/*
* Title:沉沦云MVC开发框架
* Project:首页控制器
* Author:流逝中沉沦
* QQ：1178710004
*/

namespace app\Index\Controller;

use Systems\Request;
use SinKingCloud\Mstanford;
use Systems\Session;

class Video extends Check
{
    public function index()
    {
        $uid = intval(Session::get('uid'));
        $id = intval(Request::GetData('id'));
        $pc = Request::GetData("type") == "pc" ? true : false;
        $courseid = intval(Request::GetData('courseid'));
        if ($id <= 0 || $uid <= 0 || $courseid <= 0) {
            $this->json(['code' => 0, 'msg' => "参数无效！"]);
        }
        $mhys = new Mstanford();
        if ($pc) {
            $user = Session::get("user");
            $pwd = Session::get("pwd");
            if (empty($user)||empty($pwd)) {
                $this->json(['code' => 0, 'msg' => "您还未登录"]);
            }
            if (!$mhys->UserPcLogin($user,$pwd)) {
                $this->json(['code' => 0, 'msg' => "Cookie获取失败！"]);
            }
        }
        $list = $mhys->CourseInfo($id);
        $lists = array();
        foreach ($list as $key => $value) {
            if ($value['id'] == $courseid) {
                $lists = $value['child'];
                break;
            }
        }
        $data = array();
        foreach ($lists as $key => $value) {
            if (!$pc) {
                $info = $mhys->DirctoryInfo($value['id']);
                $data[] = array(
                    'id' => rand(10000, 99999),
                    'vid' => $info['video_id'],
                    'ts' => $info['ts'],
                    'sign' => $info['sign'],
                    'uid' => $uid
                );
            }else {
                $info = $mhys->DirctoryPcInfo($value['id']);
                $data[] = array(
                    'id' => rand(10000, 99999),
                    'vid' => $info['vid'],
                    'ts' => $info['ts'],
                    'sign' => $info['sign'],
                    'session_id' => $info['session_id'],
                    'playsafe' => $info['playsafe'],
                    'uid' => $uid
                );
            }
        }
        $this->json(['code' => 1, 'msg' => "获取成功！", 'data' => $data]);
    }
}
