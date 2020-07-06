<?php
/*
* Title:沉沦云MVC开发框架
* Project:Test控制器
* Author:流逝中沉沦
* QQ：1178710004
*/

namespace app\Index\Controller;

use Systems\Request;
use SinKingCloud\Mstanford;
use Systems\Session;

class Test extends Check
{
	public function index()
	{
        $uid = intval(Session::get('uid'));
        $id = intval(Request::GetData('id'));
        $errors = intval(Request::GetData('num'));
        if ($id<=0||$uid<=0) {
            $this->json(['code'=>0,'msg'=>"参数无效！"]);
        }
        $mhys = new Mstanford();
        $info = $mhys->GetTestResault($id,$uid);
        if($info){
            $this->json(['code'=>1,'msg'=>"您的得分:".$info['score']]);
        }
        if ($mhys->CourseTest($id,$uid,$errors)) {
            $this->json(['code'=>1,'msg'=>"答题成功！"]);
        }else {
            $this->json(['code'=>0,'msg'=>"答题失败！"]);
        }
	}
}