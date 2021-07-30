<?php

// check if request method is GET
if ( ! isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'GET') {
    return false;
}

// Check if user logged in
if ( ! empty($_COOKIE)) {
    $cookies       = ['comment_author_', 'wordpress_logged_in_', 'wp-postpass_', '{{__CACHING_EXCLUDE_COOKIES__}}'];
    $cookies       = array_map('addslashes', $cookies);
    $cookies_regex = '/^('.implode('|', $cookies).')/';

    foreach ($_COOKIE as $key => $value) {
        // Z_TODO: Need to decode each key before match
        if (preg_match($cookies_regex, $key)) {
            return false;
        }
    }
}

// Set default file name
$filename = 'index.html';

// Check for query strings
if ( ! empty($_GET)) {
    // Get included rules
    $include_query_strings = sbp_explode_lines('{{__CACHING_QUERY_STRING_INCLUDES__}}');

    $query_string_file_name = '';
    // Put all query string parameters in order to generate same filename even if parameter order is different
    ksort($_GET);

    foreach ($_GET as $key => $value) {
        if (in_array($key, $include_query_strings)) {
            $query_string_file_name .= "$key-$value-";
        } else {
            return false;
        }
    }

    if ('' !== $query_string_file_name) {
        $filename = md5($query_string_file_name).'.html';
    }
}

// base path
$cache_file_path = get_cache_file_path().$filename;
if ( ! is_readable($cache_file_path)) {
    return false;
}

// Check if cache file is expired
$caching_expiry = '{{__CACHING_EXPIRY__}}' * HOUR_IN_SECONDS;
if ((filemtime($cache_file_path) + $caching_expiry) < time()) {
    return false;
}

$exclude_urls = sbp_explode_lines('{{__CACHING_EXCLUDE_URLS__}}');
$current_url  = rtrim($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '/');
if (count($exclude_urls) > 0 && in_array($current_url, $exclude_urls)) {
    return false;
}

// output cached file
readfile($cache_file_path);
exit;

/**
 * Copied from WordPress wp_is_mobile
 *
 * @return bool
 */
function sbp_is_mobile()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        $is_mobile = false;
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // Many mobile devices (all iPhone, iPad, etc.)
              || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
              || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
              || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
              || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
              || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
              || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
        $is_mobile = true;
    } else {
        $is_mobile = false;
    }

    return $is_mobile;
}


// generate cache path
function get_cache_file_path()
{
    $cache_dir = WP_CONTENT_DIR.'/cache/speed-booster';

    '__SEPARATE_MOBILE_CACHING__';

    $path = sprintf(
        '%s%s%s%s',
        $cache_dir,
        DIRECTORY_SEPARATOR,
        parse_url(
            'http://'.strtolower($_SERVER['HTTP_HOST']),
            PHP_URL_HOST
        ),
        parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        )
    );

    if (is_file($path) > 0) {
        wp_die('Error occured on SBP cache. Please contact you webmaster.');
    }

    return rtrim($path, "/")."/";
}

function sbp_explode_lines($text)
{
    if ( ! $text ) {
        return [];
    }

    return array_map('trim', explode(PHP_EOL, $text));
}