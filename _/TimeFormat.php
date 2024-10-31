<?php
namespace Plainware;
use \Plainware\Time;

abstract class TimeFormat
{
	public static $t;

	public static $timeFormat = 'g:ia';
	public static $dateFormat = 'j M Y';

	public static $months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
	public static $weekdays = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];

	public static function t()
	{
		if( NULL === self::$t ){
			self::$t = Time::construct();
		}
		return self::$t;
	}

	public static function getTimeFormat(){ return static::$timeFormat; }
	public static function setTimeFormat( $v )
	{
		if( strlen($v) ){
			static::$timeFormat = $v;
		}
	}

	public static function getDateFormat(){ return static::$dateFormat; }
	public static function setDateFormat( $v )
	{
		if( strlen($v) ){
			static::$dateFormat = $v;
		}
	}

	public static function formatDuration( $seconds )
	{
		static $cache = [];

		$seconds = (string) $seconds;

		if( isset($cache[$seconds]) ){
			return $cache[$seconds];
		}

		$hours = floor( $seconds / (60 * 60) );
		$remain = $seconds - $hours * (60 * 60);
		$minutes = floor( $remain / 60 );

		$hoursView = $hours;
		$minutesView = sprintf( '%02d', $minutes );

		$ret = $hoursView . ':' . $minutesView;

		$cache[ $seconds ] = $ret;
		return $ret;
	}

	public static function formatMonthName( $monthNo )
	{
		$ret = static::$months[ $monthNo - 1 ];
		$ret = '__' . $ret . '__';
		return $ret;
	}

	public static function formatTimeInDay( $timeInDay )
	{
		$t = static::t();
		$dateTimeDb = $t->setDateDb( '20200204' )->modify( '+ ' . $timeInDay . ' seconds' )
			->getDateTimeDb()
			;
		return static::formatTime( $dateTimeDb );
	}

	public static function formatTime( $dateTimeDb )
	{
		if( NULL === $dateTimeDb ){
			return;
		}

		$t = static::t();

		if( $dateTimeDb < 24*60*60 ){
			$dateTimeDb = $t->setNow()->setStartDay()->modify( '+ ' . $dateTimeDb . ' seconds' )
				->getDateTimeDb()
				;
		}

		$t->setDateTimeDb( $dateTimeDb );

		$format = static::$timeFormat;

		if( '12short' === $format ){
			$ret = $t->format( 'g:ia' );
			$ret = str_replace( ':00', '', $ret );
		}
		elseif( '12xshort' === $format ){
			$ret = $t->format( 'g:ia' );
			$ret = str_replace( 'am', 'a', $ret );
			$ret = str_replace( 'pm', 'p', $ret );
			$ret = str_replace( ':00', '', $ret );
		}
		elseif( '24short' === $format ){
			$ret = $t->format( 'H:i' );
			$ret = preg_replace( '/0(\d\:)/', '${1}', $ret );
			$ret = str_replace( ':00', '', $ret );
		}
		else {
			$ret = $t->format( $format );
		}

		return $ret;
	}

	public static function formatTimeRange( $startDateTime, $endDateTime )
	{
		$start = static::formatTime( $startDateTime );
		$end = static::formatTime( $endDateTime );
		$return = $start . ' - ' . $end;
		return $return;
	}

	public static function formatTimeTimeRange( $startTime, $endTime )
	{
		static $cache = array(); 

		$key = $startTime . '-' . $endTime;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$t = static::t();

		$startDateTime = $t
			->setDateDb('20190210')
			->modify( '+' . $startTime . ' seconds' )
			->getDateTimeDb()
			;
		$endDateTime = $t
			->setDateDb('20190210')
			->modify( '+' . $endTime . ' seconds' )
			->getDateTimeDb()
			;

		$start = static::formatTime( $startDateTime );
		$end = static::formatTime( $endDateTime );

		$ret = $start . ' - ' . $end;
		$cache[ $key ] = $ret;

		return $ret;
	}

	public static function presentRange( $dateTime1, $dateTime2 )
	{
		static $cache = array(); 

		$key = $dateTime1 . '-' . $dateTime2;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$t = static::t();

		$ret = array();

		$date1 = $t->setDateTimeDb( $dateTime1 )->getDateDb();
		$date2 = $t->setDateTimeDb( $dateTime2 )->getDateDb();

		$time1 = $t->getTimeInDay( $dateTime1 );
		$time2 = $t->getTimeInDay( $dateTime2 );

		if( 0 == $time2 ){
			$date2 = $t->getPrevDate( $date2 );
		}

		if( $date1 === $date2 ){
			$ret[0] = static::formatDateWithWeekday( $dateTime1 );

		// 00:00-00:00, or 00:00-23:59 or 00:00-23:55
			if( (0 == $time1) && ( (0 == $time2) OR (86340 == $time2) OR (86100 == $time2) OR (86400 == $time2)) ){
			}
			else {
				$ret[0] .= ' ' . static::formatTime( $dateTime1 );
				$ret[1] = static::formatTime( $dateTime2 );
			}
		}
		else {
		// 00:00-00:00, or 00:00-23:59 or 00:00-23:55
			if( (0 == $time1) && ( (0 == $time2) OR (86340 == $time2) OR (86100 == $time2) OR (86400 == $time2)) ){
				$ret[0] = static::formatDateRange( $date1, $date2 );
			}
			else {
				$ret[0] = static::formatDateWithWeekday( $dateTime1 ) . ' ' . static::formatTime( $dateTime1 );
				$ret[1] = static::formatDateWithWeekday( $dateTime2 ) . ' ' . static::formatTime( $dateTime2 );
			}
		}

		$cache[ $key ] = $ret;

		return $ret;
	}

	public static function formatRange( $dateTime1, $dateTime2 )
	{
		$ret = static::presentRange( $dateTime1, $dateTime2 );
		$ret = ( count($ret) > 1 ) ? $ret[0] . ' - ' . $ret[1] : $ret[0];
		return $ret;
	}

	public static function formatFull( $dateTimeDb )
	{
		$ret = static::formatDateWithWeekday( $dateTimeDb ) . ' ' . static::formatTime( $dateTimeDb );
		return $ret;
	}

	public static function formatDate( $dateTimeDb )
	{
		$t = static::t();

		$ret = $t->setDateTimeDb( $dateTimeDb )
			->format( static::$dateFormat )
			;

	// replace English months to localized ones
		foreach( static::$months as $m ){
			$from = $m;
			$to = '__' . $m . '__';
			$ret = str_replace( $from, $to, $ret );
		}

		return $ret;
	}

	public function formatDateDate( $dateDb )
	{
		$dateTimeDb = static::t()->setDateDb( $dateDb )->getDateTimeDb();
		return static::formatDate( $dateTimeDb );
	}

	public static function formatDateWithWeekday( $dateTimeDb )
	{
		$t = static::t();

		$wd = $t->setDateTimeDb( $dateTimeDb )->getWeekday();
		$weekdayView = static::formatWeekday( $wd );
		$dateView = static::formatDate( $dateTimeDb );
		$ret = $weekdayView . ', ' . $dateView;

		return $ret;
	}

	public static function formatDay( $dateDb )
	{
		$t = static::t();
		$ret = $t->setDateDb( $dateDb )->getDay();
		return $ret;
	}

	public static function formatWeekday( $wd )
	{
		$t = static::t();

		if( strlen($wd) > 8 ){
			$wd = $t->setDateTimeDb( $wd )->getWeekday();
		}
		elseif( strlen($wd) > 7 ){
			$wd = $t->setDateDb( $wd )->getWeekday();
		}

		$wd = (string) $wd;
		$return = '__' . static::$weekdays[$wd] . '__';
		return $return;
	}

	public function formatDayOfWeek( $dateTimeDb )
	{
		$wd = static::t()->setDateTimeDb( $dateTimeDb )->getWeekday();
		$ret = static::formatWeekday( $wd );
		return $ret;
	}

	public function formatDurationFromText( $text )
	{
		$ts1 = static::t()->setDateDb( '20190725' )->getTimestamp();
		$ts2 = static::t()->modify( '+' . $text )->getTimestamp();

		$seconds = $ts2 - $ts1;
		return static::formatDurationVerbose( $seconds );
	}

	// maxMeasure can be d, h, m
	public static function formatDurationVerbose( $seconds, $maxMeasure = 'd' )
	{
		static $cache = array();

		$seconds = (string) $seconds;

		if( isset($cache[$seconds]) ){
			return $cache[$seconds];
		}

		$measures = array( 'd' => 'd', 'h' => 'h', 'm' => 'm' );
		if( 'd' === $maxMeasure ){
		}
		if( 'h' === $maxMeasure ){
			unset( $measures['d'] );
		}
		if( 'm' === $maxMeasure ){
			unset( $measures['d'] ); unset( $measures['h'] );
		}

		$days = isset($measures['d']) ? floor( $seconds / (24 * 60 * 60) ) : 0;
		$remain = $seconds - $days * (24 * 60 * 60);
		$hours = isset($measures['h']) ? floor( $remain / (60 * 60) ) : 0;
		$remain = $remain - $hours * (60 * 60);
		$minutes = isset($measures['m']) ? floor( $remain / 60 ) : 0;

		$ret = array();

		if( $days ){
			$daysView = $days;
			$daysView = $daysView . '' . '__d__';
			$ret[] = $daysView;
		}

		if( $hours ){
			$hoursView = $hours;
			$hoursView = $hoursView . '' . '__h__';
			$ret[] = $hoursView;
		}

		if( $minutes ){
			$minutesView = sprintf( '%02d', $minutes );
			$minutesView = $minutesView . '' . '__m__';
			$ret[] = $minutesView;
		}

		$ret = join( ' ', $ret );
		$cache[$seconds] = $ret;

		return $ret;
	}

	public static function formatMonth( $dateTimeDb )
	{
		$t = static::t();
		$t->setDateTimeDb( $dateTimeDb );
		$month = $t->getMonth();
		$ret = static::formatMonthName( $month );
		return $ret;
	}

	public static function formatYear( $dateTimeDb )
	{
		$t = static::t();

		$t->setDateTimeDb( $dateTimeDb );
		$ret = $t->getYear();

		return $ret;
	}

	public static function formatMonthYear( $dateTimeDb )
	{
		$t = static::t();

		$t->setDateTimeDb( $dateTimeDb );
		$month = $t->getMonth();
		$year = $t->getYear();
		$ret = static::formatMonthName( $month ) . ' ' . $year;

		return $ret;
	}

	public static function formatDateRange( $date1, $date2, $withWeekday = FALSE, $withYear = TRUE )
	{
		$return = array();
		$skip = array();

		if( $date1 && (! $date2) ){
			$return = static::formatDate( $date1 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date1 )->getWeekday();
				$return = static::formatWeekday( $wd ) . ', ' . $return;
			}
			$return = $return . ' &rarr;';
			return $return;
		}

		if( (! $date1) && $date2 ){
			$return = static::formatDate( $date2 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date2 )->getWeekday();
				$return = static::formatWeekday( $wd ) . ', ' . $return;
			}
			$return = '&rarr; ' . $return;
			return $return;
		}

		if( $date1 == $date2 ){
			$viewDate1 = static::formatDate( $date1 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date1 )->getWeekday();
				$viewDate1 = static::formatWeekday( $wd ) . ', ' . $viewDate1;
			}
			$return = $viewDate1;
			return $return;
		}

	// WHOLE YEAR?
		$currentYear = static::t()->setDateDb( $date1 )->getYear();
		$year2 = static::t()->setDateDb( $date2 )->modify('+1 day')->getYear();
		if( $year2 !== $currentYear ){
			$year1 = static::t()->setDateDb( $date1 )->modify('-1 day')->getYear();
			if( $year1 !== $currentYear ){
		// BINGO!
				$return = $currentYear;
				return $return;
			}
		}

	// WHOLE MONTH?
		$day2 = static::t()->setDateDb( $date2 )->modify('+1 day')->getDay();
		if( 1 == $day2 ){
			$day1 = static::t()->setDateDb( $date1 )->getDay();

			if( 1 == $day1 ){
				$month1 = static::t()->getMonth();
				$year1 = static::t()->getYear();

				static::t()->setDateDb( $date2 );
				$month2 = static::t()->getMonth();
				$year2 = static::t()->getYear();

				if( $year1 == $year2 ){
				// BINGO!
					if( $month1 == $month2 ){
						$month = static::t()->format('n');
						$return = static::formatMonthName( $month ) . ' ' . $year1;
						return $return;
					}
					else {
						$month2 = static::t()->format('n');
						$month1 = static::t()->setDateDb( $date1 )->format('n');
						$return = static::formatMonthName( $month1 ) . ' - ' . static::formatMonthName( $month2 ) . ' ' . $year1;
						return $return;
					}
				}
			}
		}

		static::t()->setDateDb( $date1 );
		$year1 = static::t()->getYear();
		$month1 = static::t()->format('n');

		static::t()->setDateDb( $date2 );
		$year2 = static::t()->getYear();
		$month2 = static::t()->format('n');

		if( $year2 == $year1 )
			$skip['year'] = TRUE;
		if( ($year2 == $year1) && ($month2 == $month1) )
			$skip['month'] = TRUE;

		if( ! $withYear ){
			$skip['year'] = TRUE;
		}

		$skip['date'] = TRUE;

		$pos_y = NULL;
		if( $skip ){
			$dateFormat = static::$dateFormat;
			$dateFormatShort = $dateFormat;

			$tags = array('m', 'n', 'M');
			foreach( $tags as $t ){
				$pos_m_original = strpos($dateFormatShort, $t);
				if( $pos_m_original !== FALSE )
					break;
			}

			if( isset($skip['year']) ){
				$pos_y = strpos($dateFormatShort, 'Y');
				if( $pos_y == 0 ){
					$dateFormatShort = substr_replace( $dateFormatShort, '', $pos_y, 2 );
				}
				else {
					$dateFormatShort = substr_replace( $dateFormatShort, '', $pos_y - 1, 2 );
				}
			}

			if( isset($skip['month']) ){
				$tags = array('m', 'n', 'M');
				foreach( $tags as $t ){
					$pos_m = strpos($dateFormatShort, $t);
					if( $pos_m !== FALSE )
						break;
				}

				// month going first, do not replace
				if( $pos_m_original == 0 ){
					// $dateFormatShort = substr_replace( $dateFormatShort, '', $pos_m, 2 );
				}
				else {
					// month going first, do not replace
					if( $pos_m == 0 ){
						$dateFormatShort = substr_replace( $dateFormatShort, '', $pos_m, 2 );
					}
					else {
						$dateFormatShort = substr_replace( $dateFormatShort, '', $pos_m - 1, 2 );
					}
				}
			}

			if( $pos_y == 0 ){ // skip year in the second part
				$dateFormat1 = $dateFormat;
				$dateFormat2 = $dateFormatShort;
			}
			else {
				$dateFormat1 = $dateFormatShort;
				$dateFormat2 = $dateFormat;
			}

			if( ! $withYear ){
				$posY = strpos($dateFormat1, 'Y');
				if( FALSE !== $posY ){
					if( $posY ){
						$dateFormat1 = substr_replace( $dateFormat1, '', $posY - 1, 2 );
					}
					else {
						$dateFormat1 = substr_replace( $dateFormat1, '', $posY, 2 );
					}
				}

				$posY = strpos($dateFormat2, 'Y');
				if( FALSE !== $posY ){
					if( $posY ){
						$dateFormat2 = substr_replace( $dateFormat2, '', $posY - 1, 2 );
					}
					else {
						$dateFormat2 = substr_replace( $dateFormat2, '', $posY, 2 );
					}
				}
			}

			static::t()->setDateDb( $date1 );

			$viewDate1 = static::t()->format( $dateFormat1 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date1 )->getWeekday();
				$viewDate1 = static::formatWeekday( $wd ) . ', ' . $viewDate1;
			}
			$return[] = $viewDate1;

			static::t()->setDateDb( $date2 );
			$viewDate2 = static::t()->format( $dateFormat2 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date2 )->getWeekday();
				$viewDate2 = static::formatWeekday( $wd ) . ', ' . $viewDate2;
			}
			$return[] = $viewDate2;
		}
		else {
			$viewDate1 = static::formatDate( $date1 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date1 )->getWeekday();
				$viewDate1 = static::formatWeekday( $wd ) . ', ' . $viewDate1;
			}
			$return[] = $viewDate1;

			$viewDate2 = static::formatDate( $date2 );
			if( $withWeekday ){
				$wd = static::t()->setDateDb( $date2 )->getWeekday();
				$viewDate2 = static::formatWeekday( $wd ) . ', ' . $viewDate2;
			}
			$return[] = $viewDate2;
		}

		if( $viewDate2 ){
			$return = $viewDate1 . ' - ' . $viewDate2;
		}
		else {
			$return = $viewDate1;
		}

		return $return;
	}

	public static function formatAgo( $dateTimeDb )
	{
		$t = static::t();

		$timestamp = $t->setDateTimeDb( $dateTimeDb )->getTimestamp();
		$currentTimestamp = $t->setNow()->getTimestamp();

		$strTime = [ "second", "minute", "hour", "day", "month", "year" ];
		$length = [ "60","60","24","30","12","10" ];

		$diff = $currentTimestamp - $timestamp;
		for( $i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++ ){
			$diff = $diff / $length[$i];
		}

		$diff = round($diff);
		$ret = $diff . " " . $strTime[$i] . "(s) ago ";

		return $ret;
	}
}