<?php
/*
* Title:沉沦云MVC开发框架
* Project:首页控制器
* Author:流逝中沉沦
* QQ：1178710004
*/

namespace app\Index\Controller;

use Systems\Request;
use Systems\Session;

class Index extends Check
{
	public function index()
	{
		return $this->fetch();
	}
	public function video()
	{
		$uid = Session::get('uid');
		$id = intval(Request::GetData('id'));
		$courseid = intval(Request::GetData('courseid'));
		$type = Request::GetData("type") == "pc" ? "video2" : "video";
		$this->assign("uid", $uid);
		$this->assign("id", $id);
		$this->assign("courseid", $courseid);
		return $this->fetch($type);
	}
}
