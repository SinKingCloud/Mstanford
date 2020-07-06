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
class Info extends Check
{
	public function index()
	{
        $uid = Session::get('uid');
        if (empty($uid)) {
            $this->json(['code'=>0,'msg'=>"您还未登陆！"]);
        }
        $id = intval(Request::GetData('id'));
        if ($id<=0) {
            $this->json(['code'=>0,'msg'=>"参数无效！"]);
        }
        $mhys = new Mstanford();
        $courseinfo = $mhys->CourseInfo($id);
        if ($courseinfo) {
            $data = array();
            foreach ($courseinfo as $key => $value) {
                $data[] = array(
                    'id'=>$value['id'],
                    'name' => $value['dir_name']
                );
            }
            $this->json(['code'=>1,'msg'=>"获取成功！",'data'=>$data]);
        }else {
            $this->json(['code'=>0,'msg'=>"信息拉取失败！"]);
        }
	}
}