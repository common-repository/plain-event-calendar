<?php
namespace Plainware\PlainEventCalendar;

return [
	[ ModelInstall::class . '::modules', ModelInstall_Event::class . '::modules' ],
	[ Page::class . '::nav', Page_Event::class . '::nav' ],
];

class ModelInstall_Event
{
	public static function modules( array $ret )
	{
		$ret[] = [ 'event', 1, [ModelEvent::class, 'up1'], [ModelEvent::class, 'down1'] ];
		return $ret;
	}
}

class Page_Event
{
	public static function nav( array $ret, array $x, App $app )
	{
		if( ! is_admin() ) return;

		$ret[ '1-event' ] = [ 'event', '__Events__' ];

		return $ret;
	}
}