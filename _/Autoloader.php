<?php
namespace Plainware;

spl_autoload_register( Autoloader::class . '::autoload' );

class Autoloader
{
	public static $dirs = [ __NAMESPACE__ => [__DIR__] ];

	public static function register( $namespace, array $dirs )
	{
		if( ! isset(static::$dirs[$namespace]) ) static::$dirs[$namespace] = [];
		static::$dirs[$namespace] = array_merge( static::$dirs[$namespace], $dirs );
	}

	public static function autoload( $class )
	{
// echo '<pre>';
// print_r( static::$dirs );
// echo '</pre>';

		$pos = strrpos( $class, '\\' );
		$classClass = substr( $class, $pos + 1 );
		$classNamespace = substr( $class, 0, $pos );

		if( ! isset(static::$dirs[$classNamespace]) ) return;

		reset( static::$dirs[$classNamespace] );
		foreach( static::$dirs[$classNamespace] as $dir ){
			$fl = $dir . DIRECTORY_SEPARATOR . $classClass . '.php';

			if( file_exists($fl) ){
// echo "FOR '$class' REQ '$fl'<br/>";
				include_once( $fl );
				// if( class_exists($class) ){
					break;
				// }
			}
			else {
// echo "MISS '$class' -> '$fl'<br/>";
			}
		}
	}
}