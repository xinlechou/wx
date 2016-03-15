<?php
/**
 * wechat Config
 * @author: beibei.li@xinlechou.com
 */
include_once '../system/common.php';
class wxConfig{
    /**
     * 微信接口前缀
     * @var string
     */
    private static $Interface_prefix = 'https://api.weixin.qq.com/';
    private static $Interface_prefix_open = 'https://open.weixin.qq.com/';
    /**
     * 微信配置
     * @var array
     */
    private static $_wxconfig = null;
    /**
     * 微信配置参数
     * @return multitype:
     */
    public static function &config() {
        if (!wxConfig::$_wxconfig) {
            wxConfig::$_wxconfig = array(
                'TOKEN'=>'8a939c157e',
                'APPID'=>'wxd0efb676fbe5a8ed',
                'SECRET'=>'cbe473ad7b19f99432a4a162ab772b5a',
                'TABLE_PREFIX'=>DB_PREFIX.'wechat_'
            );
        }
        return wxConfig::$_wxconfig;
    }
    /**
     * 设置为订阅号配置
     * @param array
     */
    public static function setDing(){
        $a = &wxConfig::config();
        $a = array(
            'TOKEN'=>'xinlechou2015',
            'APPID'=>'wx206777fafd7f9f84',
            'SECRET'=>'21a38d35b688687acf8a11751d2f9883',
            'TABLE_PREFIX'=>DB_PREFIX.'wechat_ding_'
        );
    }
    /**
     * 设置为订阅号配置
     * @param array
     */
    public static function setHuan(){
        $a = &wxConfig::config();
        $a = array(
            'TOKEN'=>'huan2016',
            'APPID'=>'wxf952196d018405ff',
            'SECRET'=>'bb71f661470edd7ccbe6ec62cc774a46',
            'TABLE_PREFIX'=>DB_PREFIX.'wechat_huan_'
        );
    }
    /**
     * 设置为网页应用配置
     * @param array
     */
    public static function setWebApp(){
        $a = &wxConfig::config();
        $a = array(
            'TOKEN'=>'',
            'APPID'=>'wx12b1f2142f7e8e7a',
            'SECRET'=>'1379f9f0eb1285e251516357e915a30d',
            'TABLE_PREFIX'=>DB_PREFIX.'wechat_'
        );
    }
    /**
     * 获取微信用户信息接口
     * @return string
     */
    public static function getUserInfoUrl($openid) {
        return wxConfig::$Interface_prefix."cgi-bin/user/info?access_token=".wxConfig::getToken()."&openid=".$openid."&lang=zh_CN";
    }
    /**
     * 自定义菜单创建接口
     * @return string
     */
    public static function creatMenuUrl() {
        return wxConfig::$Interface_prefix."cgi-bin/menu/create?access_token=".wxConfig::getToken();
    }
    /**
     * 自定义菜单删除接口
     * @return string
     */
    public static function deleteMenuUrl() {
        return wxConfig::$Interface_prefix."cgi-bin/menu/delete?access_token=".wxConfig::getToken();
    }
    /**
     * 自定义菜单获取接口
     * @return string
     */
    public static function getMenuUrl() {
        return wxConfig::$Interface_prefix."cgi-bin/menu/get?access_token=".wxConfig::getToken();
    }
    /**
     * 发送模版消息接口
     * @return string
     */
    public static function getSendTemplateUrl() {
        return wxConfig::$Interface_prefix."cgi-bin/message/template/send?access_token=".wxConfig::getToken();
    }
    /**
     * 微信授权接口：
     * 第一步：获取code
     * @param $callbackUrl 回调地址
     * @param $scope 授权类型
     *      ‘snsapi_base’	    /sns/oauth2/access_token	通过code换取access_token、refresh_token和已授权scope
     *                          /sns/oauth2/refresh_token	刷新或续期access_token使用
     *                          /sns/auth	检查access_token有效性
     *      ‘snsapi_userinfo’	/sns/userinfo	获取用户个人信息
     * @return string
     */
    public static function getAuthUrl($callbackUrl,$scope,$state) {
        $config = wxConfig::config();
        return wxConfig::$Interface_prefix_open."connect/oauth2/authorize?appid=".$config['APPID'].'&redirect_uri='.urlencode($callbackUrl)."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
    }
    /**
     * 微信页面授权第二步：根据code获取token
     * @return string
     */
    public static function getTokenByCodeUrl($code) {
        $config = wxConfig::config();
        return wxConfig::$Interface_prefix."sns/oauth2/access_token?appid=".$config['APPID']."&secret=".$config['SECRET']."&code=".$code."&grant_type=authorization_code";;
    }
    /**
     * 微信页面授权第三步：根据token拉取用户信息
     * @return string
     */
    public static function getUserinfoByTokenUrl() {
        return wxConfig::$Interface_prefix."sns/userinfo?";
    }
    /**
     * 微信页面授权第三步：根据refresh_token拉取token
     * @return string
     */
    public static function getRefreshTokenUrl($refresh_token){
        $config = wxConfig::config();
        return wxConfig::$Interface_prefix."sns/oauth2/refresh_token?appid=".$config['APPID']."&grant_type=refresh_token&refresh_token=".$refresh_token;
    }
    /**
     * 微信页面授权第三步：检验token有效性
     * @return string
     */
    public static function checkTokenByRefresh($token,$openid){
        $config = wxConfig::config();
        $url =  wxConfig::$Interface_prefix."sns/auth?access_token=".$token."&openid=".$openid;
        $result = wxConfig::getCurl($url);
        $result= json_decode($result,true);
        return $result['errcode']==0;
    }
    /**
     * 自定义菜单类型
     * @return string
     */
    public static function getMenuType($type=1){
        /*后台说明：
          <option value="1">自动回复内容</option>
		  <option value="2">自动打开连接</option>
		  <option value="3">自动回复文章</option>
        微信说明：
        1、click：点击推事件
用户点击click类型按钮后，微信服务器会通过消息接口推送消息类型为event	的结构给开发者（参考消息接口指南），并且带上按钮中开发者填写的key值，开发者可以通过自定义的key值与用户进行交互；
        2、view：跳转URL
用户点击view类型按钮后，微信客户端将会打开开发者在按钮中填写的网页URL，可与网页授权获取用户基本信息接口结合，获得用户基本信息。
        3、media_id：下发消息（除文本消息）
用户点击media_id类型按钮后，微信服务器会将开发者填写的永久素材id对应的素材下发给用户，永久素材类型可以是图片、音频、视频、图文消息。请注意：永久素材id必须是在“素材管理/新增永久素材”接口上传后获得的合法id。
        P.S. 目前先以这三个类型做测试
        */
        $result = 'click';
        switch($type){
            case 1:
                break;
            case 2:
                $result = 'view';
                break;
            /*case 3:
                $result = 'media_id';
                break;*/
        }
        return $result;
    }

    /**
     * 获取token接口
     * @return string
     */
    public static function getTokenUrl() {
        $config = wxConfig::config();
        $param = array('appid'=>$config['APPID'],'secret'=>$config['SECRET']);
        return wxConfig::$Interface_prefix."cgi-bin/token?grant_type=client_credential&".http_build_query($param);
    }
    public static function getToken(){
        $config = wxConfig::config();
        $appid = $config['APPID'];
//        print_r($config['TOKEN']);
//        $secret = $config['SECRET'];
        $now = time();
        $sql  = "select * from ".DB_PREFIX."access_token_cache where appid='".$appid."' and expired_at>".$now;
//        echo $sql;
        $access_token_list = $GLOBALS['db']->getAll($sql);
//        print_r($access_token_list);
        $token = "";
        //这儿会出问题
//        if(true){
        if(empty($access_token_list[0]['access_token']) ) {
            //获取access_token为空时更新token
            $url = wxConfig::getTokenUrl();
//            echo $url;
            $str = file_get_contents($url);
            $array = json_decode($str);
            $access_token = $array->access_token;
            $expire = $array->expires_in;
            $expired_at = $expire+time();
            $update = "update ".DB_PREFIX."access_token_cache set access_token='".$access_token."',expired_at='".$expired_at."' where appid='".$appid."'";
            $GLOBALS['db']->query($update);
            $token  = $access_token;
        }else {
            $token  = $access_token_list[0]['access_token'];
        }
//        print_r($token);
        //检查token是否生效
        $res = file_get_contents("https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$token);
        $code = json_decode($res,1);
        if($code['errcode']=='40001') {
            $sql = "update ".DB_PREFIX."access_token_cache set access_token='' where appid='".$appid."'";
            $GLOBALS['db']->query($sql);
            $token  = wxConfig::getToken();
        }
        return $token;
    }
    public static function getRefreshToken($code=''){
        $config = wxConfig::config();
        $appid = $config['APPID'];
        $now = time();
        //每个用户需要对应一个token，所以先不保存refreshtoken了
//        $sql  = "select * from ".DB_PREFIX."access_token_cache where appid='".$appid."' and refresh_invalid_at>".$now;
//        $access_token_list = $GLOBALS['db']->getAll($sql);
//        if(empty($access_token_list)){
            $token_info = wxConfig::getTokenByCode($code);
            $token_info = json_decode($token_info,true);
            $refresh_token=$token_info['refresh_token'];
//            $token = $token_info['access_token'];
//            $sql = "update ".DB_PREFIX."access_token_cache set refresh_token='".$refresh_token."',refresh_invalid_at='".($now+29*24*3600)."' where appid='".$appid."'";
//            $GLOBALS['db']->query($sql);
//        }else {
//            $refresh_token  = $access_token_list[0]['refresh_token'];
//        }
        $url = wxConfig::getRefreshTokenUrl($refresh_token);
        $token_info = wxConfig::getCurl($url);
        $token_info = json_decode($token_info,true);
        return $token_info;
    }
    public static function getTokenByCode($code){
        $get_token_url=wxConfig::getTokenByCodeUrl($code);
        $token_info=wxConfig::getCurl($get_token_url);
        $token_info = json_decode($token_info,true);
        return $token_info;
    }
    /**
     * 获取用户信息
     * @return json
     */
    public static function getUserInfo($openid){
        $url = wxConfig::getUserInfoUrl($openid);
        $r = wxConfig::getCurl($url);
        return $r;
    }
    /**
     * 微信api不支持中文转义的json结构
     * @param array $arr
     */
    public static function json_encode($arr) {
        $parts = array ();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys ( $arr );
        $max_length = count ( $arr ) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ( $arr as $key => $value ) {
            if (is_array ( $value )) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = self::json_encode ( $value ); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
            } else {
                $str = '';
                if (! $is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (is_numeric ( $value ) && $value<2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes ( $value ) . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode ( ',', $parts );
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }
    /**
     * GET-CURL
     * @return array
     */
    public static function getCurl($url=''){
        try {
            // 1. 初始化
            $ch = curl_init();
            // 2. 设置选项，包括URL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // 3. 执行并获取HTML文档内容
            $output = curl_exec($ch);
            $response = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//            $curl_error_no = curl_errno($ch);

            // 4. 释放curl句柄
            curl_close($ch);

            if($output && !empty($output) && $response == '200'){
                return $output;
            }
            else{
                return false;
            }
        }
        catch(Exception $e){
            return false;
        }
    }

    /**
     * POST-CURL
     * @return array
     */
    public static function postCurl($data=array(),$url){
        try{
            // 1. 初始化
            $ch = curl_init();
            // 2. 设置选项，包括URL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data))
            );
            // 3. 执行并获取HTML文档内容
            $tmpInfo = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }

            curl_close($ch);
            return $tmpInfo;
        } catch(Exception $e){
            return false;
        }
    }
    /*******自动回复消息********/
    /**
     * 获取关键字
     * @return array
     */
    public static function getKeyWords($key=""){
        $config = wxConfig::config();
        if($key!==""){
            //首先全匹配
            $sql = "select * from ".$config['TABLE_PREFIX']."keyword where keyword = '".urldecode($key)."' order by num DESC";
            $tmpKeyDate = $GLOBALS['db']->getAll($sql);
            $update = "";
            if(empty($tmpKeyDate)){
                //sec 模糊匹配 按照num（词频）排序
                $sql = "select * from ".$config['TABLE_PREFIX']."keyword";
                $sql .= " where state=1 and keyword like N'%";
                $sql .= urldecode($key)."%' order by num DESC";
                $update = "UPDATE ".$config['TABLE_PREFIX']."keyword  SET num =(num+1) WHERE keyword like N'%".urldecode($key)."%'";
                $tmpKeyDate = $GLOBALS['db']->getAll($sql);
            }else{
                $update = "UPDATE ".$config['TABLE_PREFIX']."keyword  SET num =(num+1) WHERE keyword = '".urldecode($key)."'";
            }
            $GLOBALS['db']->query($update);

        }else{
            $sql = "select * from ".$config['TABLE_PREFIX']."keyword";
            $tmpKeyDate = $GLOBALS['db']->getAll($sql);
        }
//        echo $sql;
        return $tmpKeyDate?$tmpKeyDate:"";
    }
    /**
     * 获取自动回复内容
     * @return string
     */
    public static function getAutoReply(){
        $config = wxConfig::config();
        $sql = "select * from ".$config['TABLE_PREFIX']."abstract where state=1";
        $tmpKeyDate = $GLOBALS['db']->getAll($sql);
        if(is_array($tmpKeyDate)&&!empty($tmpKeyDate)){
            $str = $tmpKeyDate[0]['abstract'];
            return $str?$str:false;
        }
    }
    /**
     * 获取自动回复日志：
     *  判断是否在同一天内需要自动回复此用户
     * @return boolean
     */
    public static function getIsAutoReply($openid){
        $config = wxConfig::config();
        $sql = "select * from ".$config['TABLE_PREFIX']."reply_log where openid='".$openid."'";
        $tmpDate = $GLOBALS['db']->getAll($sql);
        $flag = true;
        $nowTime = time();

        if($tmpDate){
            //存在log时判断是否过时
            $flag = $tmpDate[0]['expire_time']>time()?false:true;
            if($flag){
                $expire_time = $nowTime+60*60*24;//一天过期
                $sql = "UPDATE ".$config['TABLE_PREFIX']."reply_log SET expire_time='".$expire_time."' WHERE openid='".$openid."'";

                $GLOBALS['db']->query($sql);
            }
        }else{
            //不存在时插入新log
            $expire_time = $nowTime+60*60*24;//一天过期
            $sql = "INSERT INTO ".$config['TABLE_PREFIX']."reply_log (`openid`,`expire_time`,`replay_time`) VALUES ('".$openid."','".$expire_time."','".$nowTime."')";
            $GLOBALS['db']->query($sql);
        }
        return $flag;
    }
}
?>