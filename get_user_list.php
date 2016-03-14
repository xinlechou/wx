<?php

require '../system/common.php';


define("TOKEN", "8a939c157e");
define("APPID", "wxd0efb676fbe5a8ed");
define("SECRET", "cbe473ad7b19f99432a4a162ab772b5a");
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


//根据access_token查询用户信息  
$access_token = $json_obj['access_token'];
$get_user_list_url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token.'&next_openid=';


$ch = curl_init();  
curl_setopt($ch,CURLOPT_URL,$get_user_list_url);  
curl_setopt($ch,CURLOPT_HEADER,0);  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  
$res = curl_exec($ch);  
curl_close($ch);
//解析json  
//total关注该公众账号的总用户数
//count拉取的OPENID个数，最大值为10000
//data列表数据，OPENID的列表
//next_openid拉取列表的最后一个用户的OPENID
$user_list = json_decode($res,true);


//$sql = 'select * from hp_weixin_qrcode limit 1';
//var_dump($db->query($sql)->fetchAll());
//var_dump($db->errorMsg());

//把查询到的所有用户插入到数据表中
foreach($user_list['data']['openid'] as $k=>$v){
	$sql = "select * from xlc_wechat_user_info where openid = '".$v."' limit 1";
	if($GLOBALS['db']->getRow($sql)){
		echo $v.'已经添加过了。<br>';
	}else{
		$tmp_insert_sql = "INSERT INTO xlc_wechat_user_info (`openid`) VALUES ('".$v."')";
		echo $tmp_insert_sql.'<br>';
		$GLOBALS['db']->query($tmp_insert_sql);
	}
}


?>