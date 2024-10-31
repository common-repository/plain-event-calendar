<?php
namespace Plainware\PlainEventCalendar;

return [
	[ App::class . '::__construct',	App_Setting::class . '::construct' ],
	[ Page::class . '::nav', Page_Setting::class . '::nav' ],
];

class App_Setting
{
	public static function construct( App $app )
	{
		$app->TimeFormat->setTimeFormat( $app->ModelSetting->get('time_time_format') );
		$app->TimeFormat->setDateFormat( $app->ModelSetting->get('time_date_format') );
		$app->Time->setWeekStartsOn( $app->ModelSetting->get('time_week_starts') );
	}
}

class Page_Setting
{
	public static function nav( array $ret, array $x, App $app )
	{
		if( ! is_admin() ) return;

		$ret[ '9-setting' ] = [ 'setting', '__Settings__' ];

		return $ret;
	}
}