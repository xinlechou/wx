<?php
/*
 * wechat callback file
 * @author beibei.li@xinlechou.com
*/
include_once 'wx.ini.php';
include_once 'responseWx.func.inc.php';
//include_once 'wxUtil.php';
traceHttp();
$uri = $_SERVER['REQUEST_URI'];
//logger("uriT \r\n".$uri);
if(strpos($uri,'token_ding')){
    wxConfig::setDing();
};
if(strpos($uri,'token_huan')){
    wxConfig::setHuan();
};
$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}
class wechatCallbackapiTest
{
    private $TABLE_PREFIX;//xlc_wechat_ding_
    public $postObj;
    private $userInfo;
    private $insParam;
    public function __construct(){
        $this->setPostObj();
        $this->setTablePrefix();
    }
    public function setTablePrefix(){
        $config = wxConfig::config();
        $this->TABLE_PREFIX = $config['TABLE_PREFIX'];
    }
    public function getTablePrefix(){
        return $this->TABLE_PREFIX;
    }
    /**
     * 验证服务器地址的有效性
     * @return boolean
     **/
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $config = wxConfig::config();
        $token = $config['TOKEN'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 处理微信回复消息
     * @param xml
     */
    public function responseMsg(){
        //1.获取用户信息
        $postObj = $this->postObj;
        if(!empty($postObj)){
            $openid = trim($postObj->FromUserName);
            $userInfo = wxConfig::getUserInfo($openid);
            $userInfo = json_decode($userInfo,true);

            $this->setUserInfo($userInfo);
            //2.消息分类
            $type = $postObj->MsgType;
            $typeUc = ucwords($type);
            //3.执行该消息动作
            $method = "receive".$typeUc;

            $insDataRest = $this->receiveMsgType($type);
            $this->setInsParam($insDataRest);
            $insData = $this->getInsParam();
            //4.将消息处理结果插入数据库
            $this->insertUserMsg($insData,'user_msg');
            if(method_exists($this,$method)){
                $result = $this->$method($postObj);
                logger("T \r\n".$result);
            }else{
                //默认返回一个文字信息
                $result = $this->receiveOther($postObj,'没有什么能帮助你的。＝。＝');
            }
            echo $result;
        }else{
            echo "";
            exit;
        }
    }
    /**
     * 根据事件保存相应参数
     * @return array
     */
    public function receiveMsgType($type){
        $postObj = $this->postObj;
        $user_info = $this->getUserInfo();
        $insData = array();
        $insData['msg_type'] = $type;
        $insData['description'] = $postObj->MsgId;
        switch ($type){
            //事件推送
            case "event":
                $insData['msg_type'] = $eventType = $postObj->Event;
                switch($eventType){
                    case 'CLICK':
                        $insData['content_text'] = '点击了'.$postObj->EventKey;
                        break;
                    case 'VIEW':
                        $insData['content_text'] = '跳转到'.$postObj->EventKey;
//                        $result = $this->receiveText($postObj);
                        break;
                    case 'subscribe'://关注
                        //判断是否新用户关注
                        $user_list = $this->getLocalUser($user_info['openid']);
                        //更新所有用户信息的State状态为2
                        if(!empty($user_list)){
                            $tmp_update_sql = "UPDATE ".$this->TABLE_PREFIX."user_info SET state=1,type=1 WHERE openid='".$user_info['openid']."'";
//                            $this->insertLocalUser($user_list);
                            $GLOBALS['db']->query($tmp_update_sql);
                            $insData['content_text'] = $user_info['nickname'].'重新关注了新乐筹';
                        }else{
                                //插入一条新数据
                            $this->insertLocalUser($user_info);
                            $insData['content_text'] = $user_info['nickname'].'关注了新乐筹';
                            $insData['headimgurl'] = $user_info['headimgurl'];
                        }
                        break;
                    case 'unsubscribe'://取消关注
                        $user_list = $this->getLocalUser($user_info['openid']);
                        if(!empty($user_list)){
                        $tmp_update_sql = "UPDATE ".$this->TABLE_PREFIX."user_info SET state=2,type=2 WHERE openid='".$user_info['openid']."'";
                            $GLOBALS['db']->query($tmp_update_sql);
                            $insData['nickname'] = $user_list[0]['nickname'];
                            $insData['headimgurl'] = $user_list[0]['headimgurl'];
                            $insData['content_text'] = $user_list[0]['nickname'].'取消关注了新乐筹';
                        }
                        break;

                }
                break;
            //普通消息
            case "text":
                $insData['content_text'] = $postObj->Content;
                break;
            case "image":
                $insData['content_text'] = '图片';
                $insData['picurl_image'] = $postObj->PicUrl;
                break;
            case "voice":
                if (isset($postObj->Recognition) && !empty($postObj->Recognition)){
                    //                $insData['content_text'] = "";

                    $insData['voice'] = $postObj->Recognition;
                }else{
                    $insData['voice'] = $postObj->MediaId;
                }
                break;
            case "video":
                break;
            case "shortvideo":
                break;
            case "location":
                $insData['x_location'] = $postObj->Location_X;
                $insData['y_location'] = $postObj->Location_Y;
                $content = $insData['nickname']."位置:(".$postObj->Location_X.",".$postObj->Location_Y.")；缩放级别：".$postObj->Scale."；位置:".$postObj->Label;
                $insData['content_text'] = $content;
                break;
            case "link":
                $insData['url_link'] = $postObj->Url;

                break;
            default:
                break;
        }
        return $insData;
    }

    /**
     * setter&getter
     */
    public function setPostObj(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->postObj = $postObj;
        }
    }
    public function setUserInfo($info){
        $this->userInfo = $info;
    }
    public function getUserInfo(){
        return $this->userInfo;
    }
    public function setInsParam($arr=array()){
        $user_info = $this->getUserInfo();
        $tmp = array(
            "openid"=> $user_info['openid'],
			"nickname"=> $user_info['nickname'],
			"headimgurl"=> $user_info['headimgurl'],
			"msg_type"=> '',
			"content_text"=> '',
			"picurl_image"=> '',
			"x_location"=> '',
			"y_location"=> '',
			"title"=> '',
			"description"=> '',
			"url_link"=> '',
			"time"=> time(),
			"voice"=> '',
			"video"=> '',
			"shortvideo"=> '',
			"remark"=> json_encode($this->postObj)
        );
        if(!empty($arr)){
           $result =  array_merge($tmp,$arr);
        }
        $this->insParam = $result;
    }
    public function getInsParam(){
        return $this->insParam;
    }
    /******DB Part********/
    /**
     * 获取唯一用户
     * @param openid
     * @return array
     */
    private function getLocalUser($openid){
        $sql = "select * from ".$this->TABLE_PREFIX."user_info where openid='".$openid."'";
        $user_list = $GLOBALS['db']->getAll($sql);
        return $user_list;
    }
    /**
     * 插入新的关注用户
     * @return array
     */
    private function insertLocalUser(){
        $user_list = $this->getUserInfo();
        $tmp_insert_sql = "INSERT INTO ".$this->TABLE_PREFIX."user_info (`openid`,`nickname`,`sex`,`city`,`province`,`country`,`language`,`headimgurl`,`time`,`type`,`state`,`unionid`,`remark`,`groupid`) VALUES ('".$user_list['openid']."','".$user_list['nickname']."','".$user_list['sex']."','".$user_list['city']."','".$user_list['province']."','".$user_list['country']."','".$user_list['language']."','".$user_list['headimgurl']."','".time()."',1,1,'".$user_list['unionid']."','".$user_list['remark']."','".$user_list['groupid']."')";
        $GLOBALS['db']->query($tmp_insert_sql);
        return $user_list;
    }
    /**
     * 保存用户消息数据&输出结果
     * @param xml
     */
    private function insertUserMsg($insData,$tableName){
        logger("T \r\n"."start");
        $tmp_tit_array = array();
        $tmp_val_array = array();
        foreach($insData as $key2=>$val2){
            $tmp_tit_array[] = $key2;
            $tmp_val_array[] = $val2;
        }
        $tmp_tit_str = implode("`,`",$tmp_tit_array);
        $tmp_val_str = implode("','",$tmp_val_array);
//        $tmp_insert_sql = "INSERT INTO ".$this->TABLE_PREFIX."user_msg (`".$tmp_tit_str."`) VALUES ('".$tmp_val_str."')";
        $tmp_insert_sql = "INSERT INTO ".$this->TABLE_PREFIX.$tableName." (`".$tmp_tit_str."`) VALUES ('".$tmp_val_str."')";
        logger("T \r\n".$tmp_insert_sql);
        $GLOBALS['db']->query($tmp_insert_sql);
    }
    /**
     * 各种类型的处理返回模版结果
     * @return array
     */
    //接收未知事件
    private function receiveOther($object,$content){
            $restult = transmitText($object,$content);
            return $restult;
    }
    //接收事件消息
    private function receiveEvent($object){
        switch($object->Event){
            case 'subscribe':
                $content = wxConfig::getAutoReply();
                $content = $content?$content:"感谢关注新乐筹，祝您在2016年梦想成真！";
                $restult = transmitText($object,$content);
                return $restult;break;
            case 'CLICK':
                //获取事件的key
                $key = $object->EventKey;
                //选择key对应的菜单&子菜单
                $sql = "select * from ".$this->TABLE_PREFIX."menu_list where state=1 and `key`='{$key}'";
                $menu = $GLOBALS['db']->getAll($sql);
                if($menu[0]['type']==1){
                    //自动回复内容
                    $content = $menu[0]['info'];
                    $restult = transmitText($object,$content);
                    return $restult;break;
                }elseif($menu[0]['type']==3){
                    //自动回复图文
                    $newsSql = "select * from ".$this->TABLE_PREFIX."new where state = 1 and menu_id = ".$menu[0]['id'];
                    $newsList = $GLOBALS['db']->getAll($newsSql);
                    if(is_array($newsList)){
                        if($newsList)
                            foreach($newsList as $k=>$v)
                                $arr[$k] = array(
                                    'Title'=>$v['title'],
                                    'Description'=>$v['info'],
                                    'PicUrl'=>$v['pic_url'],
                                    'Url'=>$v['url']
                                );
//                    $news  = array('0'=>$arr);
                        $result = transmitNews($object,$arr);
                        return $result;
                        break;
                    }
                }
            case 'VIEW':
                //跳转链接事件：
                $key = $object->EventKey;
                //选择key对应的菜单
                $sql = "select * from ".$this->TABLE_PREFIX."nav where state=1 and `key`='{$key}'";
                $menu = $GLOBALS['db']->getAll($sql);
                $content = $menu[0]['info'];
                $restult = transmitText($object,$content);
                return $restult;break;
        }
    }
    //接收文本消息
    private function receiveText($object){
        $content = wxConfig::getAutoReply();
        $content = $content?$content:"感谢您的信赖，祝您在2016年梦想成真！";
        /*$allKeywordList = wxConfig::getKeyWords();
        if(is_array($allKeywordList)){
            foreach($allKeywordList as $item){
                $content.="‘".$item['keyword'].'’，';
            }
            $content.='查看更多内容！';
        }*/

        //处理关键字
        $keywordList = wxConfig::getKeyWords($object->Content);
        $user_info = $this->getUserInfo();
        //接收到文本消息时插入xlc_wechat_msg表中
        $msgData = array(
            'openid'=>$user_info['openid'],
            'name'=>$user_info['nickname'],
            'info'=>$object->Content,
            'type'=>0,//是关键词的1；不是关键词的0
            'time'=>time(),
            'unionid'=>$user_info['unionid']
        );

        if(is_array($keywordList)&&!empty($keywordList)){
            $msgData['type']=1;
            $this->insertUserMsg($msgData,'msg');
            //查询时按词频排序，这里默认取第一条数据
            switch($keywordList[0]['type']){
                case 1:
                    /*预期type有如下种:
                     *  1、回复文字：在填出的对话框中输入要回复的文字信息（最多300个字）
                        2、回复图片：回复的图片需要提前添加，大小不超过2M，格式可以是bmp, png, jpeg, jpg, gif的。
                        3、回复语音：回复的语音需要提前添加，大小:不超过5M,    长度:不超过60s,    格式: mp3, wma, wav, amr
                        4、回复视频：回复的视频也需要提前添加，大小没有明确要求。
                        5、回复图文：图片和文字的混合，也需要提前添加好，图文信息可以是单图文信息（一张图片和文字描述）和多图文信息（多张图片和文字描述）。
                    目前 type：1  回复文字
                        type：2  回复图文 */
                    $content = $keywordList[0]['info'];
                    $result = transmitText($object,$content);
                    return $result;
                    break;
                case 2:
                    $arr = array(
                        'Title'=>$keywordList[0]['title'],
                        'Description'=>$keywordList[0]['info'],
                        'PicUrl'=>$keywordList[0]['pic_url'],
                        'Url'=>$keywordList[0]['url']
                    );
                    $news  = array('0'=>$arr);
                    $result = transmitNews($object,$news);
                    return $result;
                    break;
            }
//            $content = $keywordList;
        }else{
            $this->insertUserMsg($msgData,'msg');
            if(wxConfig::getIsAutoReply($user_info['openid'])){
                $restult = transmitText($object,$content);
                return $restult;
            }else{
                ob_clean();
                echo '';
                exit;

            }
        }
    }
    //接收图片消息
    private function receiveImage($object)
    {
//        $content = array("MediaId"=>$object->MediaId);
//        $result = transmitImage($object, $content);

        $content = "已收到您的图片！";
        $result = transmitText($object,$content);
        return $result;
    }
    //接收链接消息
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
        $result = transmitText($object, $content);
        return $result;
    }
    //接受语音消息
    private function receiveVoice($object){
        //开启语音识别后的语音:Recognition为语音识别结果，使用UTF8编码
        if (isset($object->Recognition) && !empty($object->Recognition)){
            $content = $object->Recognition;
            $result = transmitText($object, $content);
        }else{
//            $content = array("MediaId"=>$object->MediaId);
//            $result = transmitVoice($object, $content);
            $content = "你说神马，我没听懂！";
            $result = transmitText($object, $content);

        }
        return $result;
    }
}

?>