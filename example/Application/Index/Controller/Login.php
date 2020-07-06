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

class Login extends Check
{
	public function index()
	{
        $user = Request::GetData("user");
        $pwd = Request::GetData("pwd");
        if (empty($user)||empty($pwd)) {
            $this->json(['code'=>0,'msg'=>"请输入完整！"]);
        }
        $mhys = new Mstanford();
        $userinfo = $mhys->UserLogin($user,$pwd);
        if ($userinfo) {
            Session::set('uid',$userinfo['id']);
            Session::set("user",$user);
            Session::set("pwd",$pwd);
            $this->json(['code'=>1,'msg'=>"亲爱的".$userinfo['name'].",您已登陆成功！"]);
        }else {
            $this->json(['code'=>0,'msg'=>"登陆失败"]);
        }
	}
}