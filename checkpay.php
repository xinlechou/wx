<?php
/**
 * 检测订单状态
 * ====================================================
 * 检测某一订单当前支付状态。返回结果给ajax。
 * 
 * 
*/
	require '../system/common.php';
	$notice_id = intval($_REQUEST['notice_id']);
	$notice_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id=".$notice_id);
	if($notice_info['is_paid']==1){
		echo 'is_paid';
	}
?>