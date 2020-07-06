<?php
/*
* Title:沉沦云开发框架
* Project:公共模型库
* Author:流逝中沉沦
* QQ：1178710004
*/

namespace app\Common\Model;

use Systems\Db;
use Systems\Cache;

class CommonModel
{
	/*
	 * 加载系统配置
	 */
	public static function GetWMainSetConfig()
	{
		$res = array();
		$res = Cache::get('SinKingCloud_MainSet');
		if (empty($res)) {
			$conf = Db::init()->name('configs')->select();
			foreach ($conf as $key => $value) {
				$res[$conf[$key]['key']] = $conf[$key]['value'];
			}
			Cache::set('SinKingCloud_MainSet',$res);
		}
		if (empty($res['SystemsKey'])) {
			$sys = strtoupper(md5(random(32)));
			Db::init()->name('configs')->insert(['key' => 'SystemsKey', 'value' => $sys]);
			$res['SystemsKey'] = $sys;
		}
		return $res;
	}
}
