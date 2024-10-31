<?php
namespace Plainware\PlainEventCalendar;

class Page
{
	// public static function can( array $x, App $app )
	// {
		// return ! $app->{ __CLASS__ }->isAdmin( $x );
	// }

	public static function title( array $x, App $app )
	{}

	public static function isAdmin( array $x, App $app )
	{
		$ret = false;

		$currentUser = $x[ '$currentUser' ];
		if( ! $currentUser ) return $ret;

		if( $currentUser['is_admin'] > 1 ){
			$ret = true;
		}

		return $ret;
	}

	public static function nav( array $x, App $app )
	{
		$ret = [];
		return $ret;
	}

	public static function asset( array $x, App $app )
	{
		$ret = [];

		$ret[] = 'core.css';

		if( is_admin() ){
			$ret[] = 'wp-admin.css';
		}

		return $ret;
	}
}