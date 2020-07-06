<?php
/*
* Title:沉沦云MVC开发框架
* Project:公共文件
* Author:流逝中沉沦
* QQ：1178710004
*/

namespace app\Common\Controller;

use Systems\Console;
use app\Common\Model\CommonModel;
use Systems\Safe;

class Common extends Console
{
	protected $Web;
	protected $MainSet;
	public function __construct()
	{
		parent::__construct();
		Safe::$HTML = json_encode(array('code'=>0,'msg'=>'检测到恶意攻击,已被拦截[IP:'.getIP().']'));
		Safe::start();
		$this->LoadMainSetConfig();
	}
	/*
	 * 加载系统信息
	 */
	private function LoadMainSetConfig()
	{
		$this->MainSet = CommonModel::GetWMainSetConfig();
		define("SYSTEMKEY", $this->MainSet['SystemsKey']);
		$this->assign('MainSet', $this->MainSet);
	}
}
