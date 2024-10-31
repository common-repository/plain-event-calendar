<?php
namespace Plainware\PlainEventCalendar;

class WidgetEvent
{
	public static function renderWhen( $m, App $app )
	{
		if( $m['date_end'] && ($m['date_end'] != $m['date_start']) ){
			$ret = $app->TimeFormat->formatDateRange( $m['date_start'], $m['date_end'] );
		}
		else {
			$ret = $app->TimeFormat->formatDateWithWeekday( $m['date_start'] );
		}

		return $ret;
	}
}