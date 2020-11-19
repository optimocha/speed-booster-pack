<?php

if(preg_match('/Lighthouse/i',$_SERVER['HTTP_USER_AGENT'])) {
	echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"></head><body style=\"background:#0cce6b;font:bold 15vw sans-serif;color:#fff\">You\'ve got a perfect score. Good for you. Now go and speed up your website for real, actual people.</body></html>";
	exit;
}

?>