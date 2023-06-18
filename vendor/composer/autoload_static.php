<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc77a129bcd35be69c439b7d915bd8e6b
{
    public static $files = array (
        '7e5e2f7a37e15b506027dd81f8d0a938' => __DIR__ . '/../..' . '/freemius.php',
        '2dcc1fe700145c8f64875eb0ae323e56' => __DIR__ . '/../..' . '/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Optimocha\\SpeedBooster\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Optimocha\\SpeedBooster\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'WP_Async_Request' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-async-request.php',
        'WP_Background_Process' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-background-process.php',
        'simplehtmldom\\Debug' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/Debug.php',
        'simplehtmldom\\HtmlDocument' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlDocument.php',
        'simplehtmldom\\HtmlElement' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlElement.php',
        'simplehtmldom\\HtmlNode' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlNode.php',
        'simplehtmldom\\HtmlWeb' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlWeb.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc77a129bcd35be69c439b7d915bd8e6b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc77a129bcd35be69c439b7d915bd8e6b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc77a129bcd35be69c439b7d915bd8e6b::$classMap;

        }, null, ClassLoader::class);
    }
}
