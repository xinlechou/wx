<?php

require '../system/common.php';
define("TOKEN", "8a939c157e");
define("APPID", "wxd0efb676fbe5a8ed");
define("SECRET", "cbe473ad7b19f99432a4a162ab772b5a");

$sql = 'select * from xlc_wechat_user_info where 1';
$user_list = $GLOBALS['db']->getAll($sql);
//var_dump($db->errorMsg());

//获取access_token
$get_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.APPID.'&secret='.SECRET;

$ch = curl_init();  
curl_setopt($ch,CURLOPT_URL,$get_token_url);  
curl_setopt($ch,CURLOPT_HEADER,0);  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  
$res = curl_exec($ch);  
curl_close($ch);
$json_obj = json_decode($res,true);  

$access_token = $json_obj['access_token'];

foreach($user_list as $k=>$v){
	//根据access_token查询用户信息  
	$get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$v['openid'].'&lang=zh_CN';
	
	$ch = curl_init();  
	curl_setopt($ch,CURLOPT_URL,$get_user_info_url);  
	curl_setopt($ch,CURLOPT_HEADER,0);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  
	$res = curl_exec($ch);  
	curl_close($ch);
	//解析json  
	//subscribe	 用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息。
	//openid	 用户的标识，对当前公众号唯一
	//nickname	 用户的昵称
	//sex	 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
	//city	 用户所在城市
	//country	 用户所在国家
	//province	 用户所在省份
	//language	 用户的语言，简体中文为zh_CN
	//headimgurl	 用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
	//subscribe_time	 用户关注时间，为时间戳。如果用户曾多次关注，则取最后关注时间
	//unionid	 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。详见：获取用户个人信息（UnionID机制）
	//remark	 公众号运营者对粉丝的备注，公众号运营者可在微信公众平台用户管理界面对粉丝添加备注
	//groupid	 用户所在的分组ID
	$tmp_user_info = json_decode($res,true);
	$tmp_update_sql = "UPDATE xlc_wechat_user_info SET nickname = '".$tmp_user_info['nickname']."', sex = '".$tmp_user_info['sex']."', city = '".$tmp_user_info['city']."', country = '".$tmp_user_info['country']."', province = '".$tmp_user_info['province']."', language = '".$tmp_user_info['language']."', headimgurl = '".$tmp_user_info['headimgurl']."', time = '".$tmp_user_info['subscribe_time']."', unionid = '".$tmp_user_info['unionid']."', remark = '".$tmp_user_info['remark']."', groupid = '".$tmp_user_info['groupid']."', type=1, state=".$tmp_user_info['subscribe']." WHERE id = ".$v['id'];
	$GLOBALS['db']->query($tmp_update_sql);
	echo $v['openid'].'已更新<br>';
}

?>