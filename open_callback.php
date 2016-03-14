<?php
/**
 * open.weixin.qq.com
 */
include_once 'wx.ini.php';
$appid = "wx12b1f2142f7e8e7a";
$secret = "1379f9f0eb1285e251516357e915a30d";
$code = $_GET["code"];
$get_token_url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$get_token_url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$res = curl_exec($ch);
curl_close($ch);
$json_obj = json_decode($res,true);
//根据openid和access_token查询用户信息
$access_token = $json_obj['access_token'];
$openid = $json_obj['openid'];
$get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$get_user_info_url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$res = curl_exec($ch);
curl_close($ch);

//解析json
$user_obj = json_decode($res,true);
$_SESSION['user'] = $user_obj;

/*$url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxd0efb676fbe5a8ed&redirect_uri=http://www.xinlechou.com/wxpay_web/open_callback.php&response_type=code&scope=snsapi_base&state=111#wechat_redirect";
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$a_res = curl_exec($ch);
curl_close($ch);
print_r($a_res);*/
//header("Location:./");
//$get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?grant_type=authorization_code';
//$get_token_url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
//$weixin=new weixin($appid,$secret,"http://www.xinlechou.com/wap");
//$wx_info=wxConfig::postCurl(array('appid'=>$appid,'secret'=>$secret,'code'=>$code),$get_token_url);
//$wx_info=$weixin->scope_get_userinfo($_REQUEST['code']);
print_r($user_obj);
?>