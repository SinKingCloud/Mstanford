<?php
/*
 * Title:美和易思信息获取插件
 * Author：流逝中沉沦
 * Date：2020/01/01 20:00:00
 * LastEdit: 2020/06/03 23:00:00
 */

namespace SinKingCloud;

class Mstanford
{
    private $ApiUrl = array('pc' => 'https://www.51moot.net/', 'mobile' => 'http://api.51moot.cn/');
    private $sign; //签名
    private $UserInfo; //账户信息
    public $cookies = null; //pc cookie
    /**
     *构造参数
     */
    function __construct($ApiUrl = false, $sign = false)
    {
        if ($ApiUrl) {
            $this->ApiUrl = $ApiUrl;
        }
        if ($sign) {
            $this->sign = $sign;
        } else {
            $this->sign = $this->get_sign();
        }
    }
    /**
     * 获取sign
     * @return String 签名
     */
    private function get_sign()
    {
        $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/sign");
        $arr = json_decode($res, true);
        if (!array_key_exists('code', $arr)) {
            return false;
        }
        if ($arr['code'] == 0) {
            return $arr['data'];
        } else {
            return false;
        }
    }
    /**
     * 账户登陆(app协议)
     * @param String $user 账户
     * @param String $pwd 密码
     * @return Array 数据集
     */
    public function UserLogin($user, $pwd)
    {
        if (!empty($user) && !empty($pwd)) {
            if (empty($this->sign)) {
                $this->get_sign();
            }
            $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/web_user?login_name=" . $user . "&login_pass=" . $pwd . "&sign=" . $this->sign);
            $arr = json_decode($res, true);
            if (!array_key_exists('code', $arr)) {
                return false;
            }
            if ($arr['code'] == 0) {
                $this->UserInfo = $arr['data'];
                return $arr['data'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 账户登陆(pc协议)
     * @param String $user 账户
     * @param String $pwd 密码
     * @return Array 数据集
     */
    public function UserPcLogin($user, $pwd)
    {
        if (!empty($user) && !empty($pwd)) {
            if (empty($this->sign)) {
                $this->get_sign();
            }
            $res = $this->get_curl($this->ApiUrl['pc'] . "/main/login_validate", "login_name=" . $user . "&login_pass=" . $pwd . "&auto_login=true", 0, 0, 1);
            $arr = explode("\n", $res);
            $data = json_decode(end($arr), true);
            if ($data['code'] == 'success') {
                //取cookie
                preg_match_all('/Set-Cookie: (.*?);/', $res, $arr);
                $this->cookies = implode(";", $arr[1]);
                //二次登陆获取用户信息
                return $this->UserLogin($user, $pwd);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 账户信息查询(app协议)
     * @param Int $uid 账户ID
     * @return Array 数据集
     */
    public function UserQuery($uid)
    {
        if (!empty($uid)) {
            if (empty($this->sign)) {
                $this->get_sign();
            }
            $res = $this->get_curl($this->ApiUrl['mobile'] . '/api/v1/web_user?id=' . $uid . '&sign=' . $this->sign);
            $arr = json_decode($res, true);
            if (!array_key_exists('code', $arr)) {
                return false;
            }
            if ($arr['code'] == 0) {
                $this->UserInfo = $arr['data'];
                return $arr['data'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 课程信息查询(app协议)
     * @param Array $id 课程ID
     * @return Array 数据集
     */
    public function CourseQuery($id = array(), $uid = 6518)
    {
        if (empty($id) || !is_array($id) || empty($this->sign)) {
            return false;
        } else {
            $list = implode(',', $id);
            $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/course_info?page_index=0&page_size=9999&id_list=" . $list . "&type=1&is_progress=true&user_id=" . $uid . "&assort_id=1&sign=" . $this->sign);
            $arr = json_decode($res, true);
            if (!array_key_exists('code', $arr)) {
                return false;
            }
            if ($arr['code'] == 0) {
                return $arr['data']['rows'];
            } else {
                return false;
            }
        }
    }
    /**
     * 课程详情(app协议)
     * @param Int $id 课程ID
     * @return Array
     */
    public function CourseInfo($id)
    {
        if (empty($id)) {
            return false;
        }
        if (empty($this->sign)) {
            $this->get_sign();
        }
        $res = $this->get_curl($this->ApiUrl['mobile'] . "/api//v1/course_dirctory?course_id=" . $id . "&sign=" . $this->sign);
        $arr = json_decode($res, true);
        if (!array_key_exists('code', $arr)) {
            return false;
        }
        if ($arr['code'] == 0) {
            return $arr['data'];
        } else {
            return false;
        }
    }
    /**
     * 视频详情(app协议)
     * @param Array $id 课程ID
     * @return Array 数据集
     */
    public function DirctoryInfo($id)
    {
        if (empty($id)) {
            return false;
        } else {
            if (empty($this->sign)) {
                $this->get_sign();
            }
            $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/course_dirctory?id=" . $id . "&is_dirctory=true&sign=" . $this->sign);
            $arr = json_decode($res, true);
            if (!array_key_exists('code', $arr)) {
                return false;
            }
            if ($arr['code'] == 0) {
                return $arr['data'];
            } else {
                return false;
            }
        }
    }
    /**
     * 视频详情(pc协议)
     * @param Array $id 课程ID
     * @return Array 数据集
     */
    public function DirctoryPcInfo($id)
    {
        if (empty($id)) {
            return false;
        } else {
            if (empty($this->cookies)) {
                return false;
            }
            $res = $this->get_curl($this->ApiUrl['pc'] . "/server_hall_2/server_hall_2/video_play?dir_id=" . $id . "&do=_do", 0, 0, $this->cookies);
            //取视频加密信息
            preg_match_all('/polyvPlayer([\s\S]*?);/i', $res, $arr);
            if ($arr[0][0]) {
                $res = substr(substr($arr[0][0], 12), 0, -2);
                $arr = json_decode(str_replace(array("\n", "\t", "wrap", "false", "'"), array("", "", "'wrap'", "'false'", '"'), $res), true);
                return array(
                    'vid' => $arr['vid'],
                    'ts' => $arr['ts'],
                    'sign' => $arr['sign'],
                    'session_id' => $arr['session_id'],
                    'playsafe' => $arr['playsafe']
                );
            } else {
                return false;
            }
        }
    }
    /**
     * 试卷评测(app协议)
     * @param Int $id 试卷ID
     * @param Int $uid 用户ID
     * @param Int $num 错误的个数
     * @return Array 数据集
     */
    public function CourseTest($id, $uid, $num = 0)
    {
        if (empty($id) || empty($uid)) {
            return false;
        }
        if (empty($this->sign)) {
            $this->get_sign();
        }
        $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/example_subject?example_id=" . $id . "&is_random=false&sign=" . $this->sign);
        $arr = json_decode($res, true);
        if (!array_key_exists('code', $arr)) {
            return false;
        }
        if ($arr['code'] == 0) {
            $data1 = "api_action=evaluating_check&example_id=" . $id . "&user_id=" . $uid . "&sign=" . $this->sign;
            $ids = array();
            $ids2 = array();
            $errors = array();
            for ($i = 0; $i < $num; $i++) {
                $errors[] = rand(0, count($arr['data']));
            }
            $i = 0;
            foreach ($arr['data'] as $key => $value) {
                $ids[] = $value['id'];
                if (in_array($i, $errors)) {
                    $value['answer_list'] = rand(0, 3);
                }
                $ids2[] = '&answer_list' . $value['id'] . '=' . $value['answer_list'];
                $i++;
            }
            $data2 = "&subject_id_list=" . implode("[{@}]", $ids);
            $data3 = implode("", $ids2);
            $data = $data1 . $data2 . $data3;
            $res2 = $this->put_curl($this->ApiUrl['mobile'] . "/api/v1/example_subject", $data);
            $arr2 = json_decode($res2, true);
            if ($arr2['code'] == 0) {
                return $arr2['data'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 试卷评测结果(app协议)
     * @param Int $id 试卷ID
     * @param Int $uid 用户ID
     * @return Array 数据集
     */
    public function GetTestResault($id, $uid)
    {
        if (empty($id) || empty($uid)) {
            return false;
        }
        if (empty($this->sign)) {
            $this->get_sign();
        }
        $res = $this->get_curl($this->ApiUrl['mobile'] . "/api/v1/example_result?example_id=" . $id . "&user_id=" . $uid . "&is_ext=true&sign=" . $this->sign);
        $arr = json_decode($res, true);
        if (!array_key_exists('code', $arr)) {
            return false;
        }
        return $arr['data'];
    }
    /**
     * Curl get post请求
     * @param String $url 网址
     * @param String $post POST参数
     * @param String $referer refer地址
     * @param String $cookie 携带COOKIE
     * @param String $header 请求头
     * @param String $ua User-agent
     * @param String $nobaody 重定向
     * @return String 数据
     */
    private function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $clwl[] = "Accept:*/*";
        $clwl[] = "Accept-Encoding:gzip,deflate,sdch";
        $clwl[] = "Accept-Language:zh-CN,zh;q=0.8";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $clwl);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($referer) {
            if ($referer == 1) {
                curl_setopt($ch, CURLOPT_REFERER, $this->ApiUrl .  $url);
            } else {
                curl_setopt($ch, CURLOPT_REFERER, $referer);
            }
        }
        if ($ua) {
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0');
        }
        if ($nobaody) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            //主要头部
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //跟随重定向
        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
    /**
     * Curl PUT请求
     * @param String $url 网址
     * @param String $data 参数
     * @param Array $header 请求头
     * @return String 数据
     */
    private function put_curl($url, $data = "", $header = array())
    {
        $ch = curl_init();
        $header[] = "Content-type:application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}