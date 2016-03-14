<?php
/**
 * wechat Utils
 * 公众号导航管理工具
 * 授权登录管理工具
 * @author: beibei.li@xinlechou.com
 */

include_once 'wx.ini.php';
$wx = new wxUtil();
class wxUtil {
    private $config = null;
    private $callbackUrl;//回调地址
    private $bindUrl = '../?ctl=user&act=user_bind_mobile';//回调页面
    public function __construct(){
        //先设定配置信息
        if(isset($_GET['ding'])){
            wxConfig::setDing();
        }
        $this->setConfig();
        if (isset($_GET['act'])) {
            $action = $_GET['act'];
            $this->$action();
        }

    }
    /*******MENU********/
    /**
     * 请求设置微信菜单
     * @param json
     */
    public function setNav(){
        $url = wxConfig::creatMenuUrl();
        $data = $this->getPostParam();
//        print_r($data);
        if($data){
            //Php5.4才支持JSON_UNESCAPED_UNICODE
            $data = json_encode($data, JSON_UNESCAPED_UNICODE)?json_encode($data, JSON_UNESCAPED_UNICODE):wxConfig::json_encode($data);
            $r = wxConfig::postCurl($data,$url);
            $obj = json_decode($r,true);
            print_r($data);
            if($obj['errcode']=='40033') {
                //不合法的请求字符，不能包含\uxxxx格式的字符
                $data = wxConfig::json_encode($data);
                $r = wxConfig::postCurl($data,$url);
            }
            echo $r;
        }else{
            $r = array('errcode'=>-2,'errmsg'=>'没菜单阿设什么玩意，个数超了也不好使');
            echo json_encode($r);
        }
    }

    /**
     * 请求删除微信菜单
     * @param json
     */
    public function delNav(){
        $url = wxConfig::deleteMenuUrl();
        $r = wxConfig::getCurl($url);
        if($r){echo $r;exit;}
    }
    /**
     * 请求获取微信菜单
     * @param json
     */
    public function getNav(){
        $url = wxConfig::getMenuUrl();
        $r = wxConfig::getCurl($url);
        if($r){echo $r;exit;}
    }
    /**
     * 最终菜单数组
     * @return array
     */
    public function getPostParam(){
        $tmpNavData = $this->getAllLocalNav();
//        print_r($tmpNavData);
        if(count($tmpNavData)>3|| count($tmpNavData)==0) return false;
        $tmpSubNavData = $this->getAllLocalSubnav();
        $tmp = array();

        if($tmpSubNavData){
            //存在二级菜单
            foreach($tmpNavData as $k=>$v){
                $i=0;
                foreach($tmpSubNavData as $subkey=>$subvalue){
                    $tmp[$k]['name']=$v['title'];
                    if($subvalue['nav_id']==$v['id']){
                        $tmp[$k]['sub_button'][$i] = $this->filterSubNav($subvalue);
                        $i++;
                    }
                }
            }
        }else{
            //不存在二级菜单
            foreach($tmpNavData as $k=>$v){
                $tmp[$k] = $this->filterSubNav($v);
            }
        }

        $result = array(
            'button'=>$tmp
        );
        return $result;
//        print_r(json_encode($result));
        //微信api不支持中文转义的json结构
//        return wxConfig::json_encode($result);
        //php 5.4支持保留unicode
//        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 将二级菜单拼成微信所需格式
     * @return array
     */
    private function filterSubNav($subvalue){
        $tmp = array(
            'name'=>$subvalue['title']
        );
        $tmp['type'] = wxConfig::getMenuType($subvalue['type']);
        //目前只做了这两种类型的判断
        if($tmp['type'] =='view'){
            $tmp['url']=$subvalue['url'];
        }else{
            $tmp['key']=$subvalue['key'];
        }
        return $tmp;
    }
    /**
     * 获取所有一级菜单
     * @return array
     */
    public function getAllLocalNav(){
        $config = $this->getConfig();
        $tmpNavData = $GLOBALS['db']->getAll("select * from ".$config['TABLE_PREFIX']."nav where state = 1");
        return $tmpNavData;
    }
    /**
     * 获取所有二级菜单
     * @return array
     */
    public function getAllLocalSubnav(){
        $config = $this->getConfig();
        $tmpNavDate = $GLOBALS['db']->getAll("select * from ".$config['TABLE_PREFIX']."menu_list order by sort");
        return $tmpNavDate;
    }
    /***************************************微信授权******************************************************/
    /**
     * 微信页面授权第一步：获取code
     * @href
     */
    public function getWechatAuthBase(){
        //跳转到微信授权页面
        $bindurl = '../?ctl=user&act=user_bind_mobile';//增加HUAN的回调地址;我知道这样不好，但是着急＝。＝
        $this->setBindUrl($bindurl);
        $this->callbackUrl = $callBackUrl = 'http://www.xinlechou.com/wxpay_web/wxUtil.php?act=autoWechatLogin';
        $base_url =  wxConfig::getAuthUrl($callBackUrl,'snsapi_userinfo',time());
//        echo $base_url;
        app_redirect($base_url);
    }
    /**
     * 微信页面授权第一步：获取code----- HUAN
     * @href
     */
    public function getWechatAuthBaseHUAN(){
        //跳转到微信授权页面
        $bindurl = '../?ctl=olduser&act=user_bind_mobile';//增加HUAN的回调地址;我知道这样不好，但是着急＝。＝
        $this->setBindUrl($bindurl);
        $this->callbackUrl = $callBackUrl = 'http://www.xinlechou.com/wxpay_web/wxUtil.php?act=autoWechatLoginHUAN';
        $base_url =  wxConfig::getAuthUrl($callBackUrl,'snsapi_userinfo',time());
//        echo $base_url;
        app_redirect($base_url);
    }
    /**
     * 微信页面授权第一步：微信授权回调方法
     * @param
     */
    public function autoWechatLogin(){
        wxLoginTools::cleanWxInfo();
        $wx_info=$this->getWechatUserInfo($_REQUEST['code']);
        es_session::set("wx_user_info",$wx_info);
        $bindurl = $this->getBindUrl();
        app_redirect($bindurl);
        exit;
    }
    /**
     * 微信页面授权第一步：微信授权回调方法
     * @param
     */
    public function autoWechatLoginHUAN(){
        wxLoginTools::cleanWxInfo();
        $wx_info=$this->getWechatUserInfo($_REQUEST['code']);
        $bindurl = '../?ctl=olduser&act=user_bind_mobile';//增加HUAN的回调地址;我知道这样不好，但是着急＝。＝
        es_session::set("wx_user_info",$wx_info);
        app_redirect($bindurl);
        exit;
    }

    /**
     * 微信页面授权第二步：根据code拉取用户信息
     * @return
     */

    public function getWechatUserInfo($code){
        $token_info = wxConfig::getTokenByCode($code);
        $openid=$token_info['openid'];
        $get_userinfo=wxConfig::getUserinfoByTokenUrl()."access_token=".$token_info['access_token']."&openid=".$openid."&lang=zh_CN";
        $user_info=wxConfig::getCurl($get_userinfo);
        $user_info=json_decode($user_info,true);
        return $user_info;
    }
    /***************************************微信扫码登录******************************************************/

    /**
     * 微信扫码登录第一步：根据code拉取用户信息
     * @return
     */
    public function getWechatAuthLogin(){
        wxConfig::setWebApp();
        wxLoginTools::cleanWxInfo();
        $wx_info=$this->getWechatUserInfo($_REQUEST['code']);
        es_session::set("wx_login_user_info",$wx_info);
        $bindurl = $this->getBindUrl();
        app_redirect($bindurl);
        exit;
    }
    //
    public function getWechatAuthLoginHUAN(){
        wxConfig::setWebApp();
        wxLoginTools::cleanWxInfo();
        $bindurl = '../?ctl=olduser&act=user_bind_mobile';//增加HUAN的回调地址
        $this->setBindUrl($bindurl);
        $wx_info=$this->getWechatUserInfo($_REQUEST['code']);
        es_session::set("wx_login_user_info",$wx_info);
        app_redirect($bindurl);
        exit;
    }
    public function getConfig(){
        return $this->config;
    }
    public function setConfig(){
        $this->config = wxConfig::config();
    }
    public function getBindUrl(){
        return $this->bindUrl;
    }
    public function setBindUrl($url){
        $this->bindUrl = $url;
    }

}
class wxLoginTools{
    /**
     * 清除用户临时信息
     */
    public static function cleanWxInfo(){
        es_session::delete('wx_user_info');
        es_session::delete('wx_login_user_info');
    }
}