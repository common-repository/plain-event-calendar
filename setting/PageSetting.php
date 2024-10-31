<?php
namespace Plainware\PlainEventCalendar;

class PageSetting
{
	public static function title( array $x )
	{
		return '__Settings__';
	}

	public static function can( array $x, App $app )
	{
		if( ! is_admin() ) return false;
		if( ! current_user_can('manage_options') ) return false;
	}

	public static function menu( array $x )
	{
		$ret = [];
		$ret[ 'setting-time' ] = '__Date & Time__';
		$ret[ 'setting-about' ] = '__About__';
		return $ret;
	}

	public static function get( array $x )
	{
	}

	public static function render( array $x )
	{
	}

	public static function nav( array $x )
	{
		$ret = [];

		$ret[ '8-time' ] = [ '.setting-time', '__Date & Time__' ];
		$ret[ '9-about' ] = [ '.setting-about', '__About__' ];

		return $ret;
	}
}