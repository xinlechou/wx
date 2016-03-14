<?php

	$appid = "wxd0efb676fbe5a8ed";
	$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3a%2f%2fwww.xinlechou.com%2fwxpay_web%2ffn_callback.php&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
	header("Location:".$url);

?>