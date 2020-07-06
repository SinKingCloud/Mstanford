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

class Course extends Check
{
	public function index()
	{
        $uid = Session::get('uid');
        if (empty($uid)) {
            $this->json(['code'=>0,'msg'=>"您还未登陆！"]);
        }
        $mhys = new Mstanford();
        $userinfo = $mhys->UserQuery($uid);
        if ($userinfo) {
            $courses = $mhys->CourseQuery($userinfo['course_id_list'],$uid);
            if ($courses) {
                $data = array();
                foreach ($courses as $key => $value) {
                    $data[] = array(
                        'id'=>$value['id'],
                        'name'=>$value['name']
                    );
                }
                $this->json(['code'=>1,'msg'=>"获取成功！","data"=>$data]);
            }else {
                $this->json(['code'=>0,'msg'=>"课程拉取失败！"]);
            }
            
        }else {
            $this->json(['code'=>0,'msg'=>"信息拉取失败！"]);
        }
	}
}