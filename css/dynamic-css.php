<style type="text/css">

/*progress-bar-time*/

@-webkit-keyframes progress-bar-time {
	from { }
	to { width: 5<?php echo $page_time; ?>%  }
}

@-moz-keyframes progress-bar-time {
	from { }
	to { width: 5<?php echo $page_time; ?>%  }
}

@-ms-keyframes progress-bar-time {
	from { }
	to { width: 5<?php echo $page_time; ?>%  }
}

@-o-keyframes progress-bar-time {
	from { }
	to { width: 5<?php echo $page_time; ?>%  }
}

@keyframes progress-bar-time {
	from { }
	to { width: 5<?php echo $page_time; ?>%  }
}

/*progress-bar-queries*/

@-webkit-keyframes progress-bar-queries {
	from { }
	to { width: <?php echo $page_queries; ?>%  }
}

@-moz-keyframes progress-bar-queries {
	from { }
	to { width: <?php echo $page_queries; ?>%  }
}

@-ms-keyframes progress-bar-queries {
	from { }
	to { width: <?php echo $page_queries; ?>%  }
}

@-o-keyframes progress-bar-queries {
	from { }
	to { width: <?php echo $page_queries; ?>%  }
}

@keyframes progress-bar-queries {
	from { }
	to { width: <?php echo $page_queries; ?>% }
}

<?php

if ( $page_time >=1.00 and $page_time <=2.00 ) { ?>

.wrap .sbp-progress.time > span	{
	background-color: #f1a165;
	background-image: -webkit-gradient(linear,left top,left bottom,color-stop(0, #f1a165),color-stop(1, #f36d0a));
	background-image: -webkit-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -moz-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -ms-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -o-linear-gradient(top, #f1a165, #f36d0a);
}

<?php }

if ( $page_time >=2.00 ) { ?>
.wrap .sbp-progress.time > span {
	background-color: #FB8A88;
	background-image: -webkit-gradient(linear,left top,left bottom,color-stop(0, #FB8A88),color-stop(1, #FF4136));
	background-image: -webkit-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -moz-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -ms-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -o-linear-gradient(top, #f1a165, #f36d0a);
}

<?php }

if ( $page_queries >=100 and $page_queries <=200 ) { ?>

.wrap .sbp-progress.queries > span {
	background-color: #f1a165;
	background-image: -webkit-gradient(linear,left top,left bottom,color-stop(0, #f1a165),color-stop(1, #f36d0a));
	background-image: -webkit-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -moz-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -ms-linear-gradient(top, #f1a165, #f36d0a);
    background-image: -o-linear-gradient(top, #f1a165, #f36d0a);
}

<?php }

if ( $page_queries >=200 ) { ?>
.wrap .sbp-progress.queries > span {
	background-color: #FB8A88;
	background-image: -webkit-gradient(linear,left top,left bottom,color-stop(0, #FB8A88),color-stop(1, #FF4136));
	background-image: -webkit-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -moz-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -ms-linear-gradient(top, #FB8A88, #FF4136);
    background-image: -o-linear-gradient(top, #f1a165, #f36d0a);
}

<?php }

?>

</style>