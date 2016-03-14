<?php

if(empty($_SESSION['user'])){
	
	header("Location:./fn_wx_login.php");
}else{
	print_r($_SESSION['user']);
}

?>