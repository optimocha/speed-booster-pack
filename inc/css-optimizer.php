<?php

/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Generate Styles List
---------------------------------------------------------------------------------------------------------*/

function sbp_generate_styles_list() {

	global $wp_styles;

	$list = array();
	if ( isset( $wp_styles->queue ) && is_array( $wp_styles->queue ) ) {
		foreach ( $wp_styles->queue as $style ) {
			if ( is_css_excluded( $style ) ) {
				//  load excluded stylesheet in render-blocking manner
			} else {
				$list[] = array(
					'src'	=> $wp_styles->registered[$style]->src,
					'media'	=> $wp_styles->registered[$style]->args
					);
			}
		}
	}
	return $list;

}	//	END function sbp_generate_styles_list


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Deregister all styles
---------------------------------------------------------------------------------------------------------*/

function sbp_unregister_styles() {

	global $wp_styles;

	if ( isset( $wp_styles->queue ) && is_array( $wp_styles->queue ) ) {

		foreach ( $wp_styles->queue as $style ){
			if ( is_css_excluded( $style )) {
				continue;
			}

			wp_dequeue_style( $style );
			wp_deregister_style( $style );
		}
	}

}	//	END function sbp_unregister_styles


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Generate inline styles
---------------------------------------------------------------------------------------------------------*/

function sbp_inline_css( $url, $minify = true ) {

	$base_url 	= get_bloginfo( 'wpurl' );
	$path 		= false;

	if ( strpos( $url, $base_url ) !== FALSE ) {

		$path = str_replace( $base_url,rtrim(ABSPATH,'/'),$url );

	} elseif ( $url[0]=='/' && $url[1]!='/' ) {

		$path 	= rtrim( ABSPATH,'/' ).$url;
		$url 	= $base_url.$url;
	}

	if ( $path && file_exists( $path ) ){

		$css = file_get_contents( $path );

		if ( $minify ){
			$css = sbp_minify_css( $css );
		}

		$css = sbp_rebuilding_css_urls( $css, $url );

		echo $css;
		return true;

	} else {

		return false;
	}

}	//	END function sbp_inline_css


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Rebuilding CSS URLs
---------------------------------------------------------------------------------------------------------*/

function sbp_rebuilding_css_urls($css,$url){
	$css_dir 	= substr($url,0,strrpos($url,'/'));
	$css 		= preg_replace("/url\((?!data:)['\"]?([^\/][^'\"\)]*)['\"]?\)/i","url({$css_dir}/$1)",$css);

	return $css;
}


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Minify All CSS
---------------------------------------------------------------------------------------------------------*/


function sbp_minify_css( $css ) {

	$css = sbp_remove_multiline_comments( $css );
	$css = str_replace(array("\t","\n","\r"),' ',$css);
	$cnt = 1;

	while ($cnt>0) {
		$css = str_replace('  ',' ',$css,$cnt);
	}

	$css = str_replace(array(' {','{ '),'{',$css);
	$css = str_replace(array(' }','} ',';}'),'}',$css);
	$css = str_replace(': ',':',$css);
	$css = str_replace('; ',';',$css);
	$css = str_replace(', ',',',$css);

	return $css;
}


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - Remove multi-line comments from CSS
---------------------------------------------------------------------------------------------------------*/

function sbp_remove_multiline_comments( $code,$method=0 ) {

	switch ( $method ) {
		case 1:{

			$code = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/' , '' , $code );
			break;
		}

		case 0:

		default :{

			$open_pos = strpos($code,'/*');
			while ( $open_pos !== FALSE ){
				$close_pos = strpos($code,'*/',$open_pos)+2;
				if ($close_pos){
					$code = substr($code,0,$open_pos) . substr($code,$close_pos);
				} else {
					$code = substr($code,0,$open_pos);
				}

				$open_pos = strpos($code,'/*',$open_pos);
			}

			break;
		}
	}

	return $code;
}


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - get stylesheets exception list
---------------------------------------------------------------------------------------------------------*/

function sbp_style_exceptions() {

	$array = explode("\n",get_option( 'sbp_css_exceptions' ));
	$css_exceptions = array();
	foreach ($array as $key=>$ex) {
		if (trim($ex)!=''){
			$css_exceptions[$key] = trim($ex);
		}
	}

	return $css_exceptions;
}


/*--------------------------------------------------------------------------------------------------------
    CSS OPTIMIZER - get stylesheets exception names
---------------------------------------------------------------------------------------------------------*/

function is_css_excluded( $file ) {
	global $wp_styles;
	$css_exceptions = sbp_style_exceptions();

if( is_string( $file ) && isset( $wp_styles->registered[$file] ) ) {
		$filename = $file;
		$file = $wp_styles->registered[$file];
	}

	foreach ( $css_exceptions as $ex ){
		if ( $file->handle==$ex || (strpos($ex,'.')!==FALSE && strpos($file->src,$ex)!==FALSE) ){
			return true;
		}
	}

	return false;
}