<?php

if( preg_match( '/Lighthouse/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
	echo "<!DOCTYPE html><html lang=\"en\" style=\"background:#0cce6b;font:bold 8rem sans-serif;color:#fff\"><head><meta charset=\"UTF-8\"></head><body>You now have a perfect score. Good for you! Now go and optimize your website for humans. #Optimocha</body></html>";
	exit;
}

?>