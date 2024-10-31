<?php
namespace Plainware;

class Time extends \DateTime
{
	public static $instance;

	public static $weekStartsOn = 0;
	public static $timezone = '';

	public function __construct()
	{
		parent::__construct();
	}

	public static function construct()
	{
		if( NULL === self::$instance ){
			self::$instance = new static;
		}
		return self::$instance;
	}

	public static function getWeekStartsOn(){ return static::$weekStartsOn; }
	public static function setWeekStartsOn( $v ){ static::$weekStartsOn = $v; }

	public static function toMysqlDatetime( $dateTimeDb )
	{
		$year = substr( $dateTimeDb, 0, 4 );
		$month = substr( $dateTimeDb, 4, 2 );
		$day = substr( $dateTimeDb, 6, 2 );

		$hour = '00';
		$min = '00';
		$sec = '00';

		if( strlen($dateTimeDb) > 8 ){
			$hour = substr( $dateTimeDb, 8, 2 );
			$min = substr( $dateTimeDb, 10, 2 );
		}

		$ret = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':' . $sec;
		return $ret;
	}

	public static function fromMysqlDatetime( $mysqlDateTime )
	{
		$year = substr( $mysqlDateTime, 0, 4 );
		$month = substr( $mysqlDateTime, 5, 2 );
		$day = substr( $mysqlDateTime, 8, 2 );

		$hour = substr( $mysqlDateTime, 11, 2 );
		$min = substr( $mysqlDateTime, 14, 2 );
		$sec = '00';

		$ret = $year . $month . $day . $hour . $min;
		return $ret;
	}

	public function getDayOfWeekOccurenceInMonth( $date )
	{
		$this->setDateDb( $date );
		$month = $this->getMonth();
		$rexMonth = $month;

		$ret = 0;
		while( $rexMonth == $month ){
			$ret++;
			$this->modify( '-1 week' );
			$rexMonth = $this->getMonth();
		}

		return $ret;
	}

	public function getDayOfWeekOccurenceInMonthFromEnd( $date )
	{
		$this->setDateDb( $date );
		$month = $this->getMonth();
		$rexMonth = $month;

		$ret = 0;
		while( $rexMonth == $month ){
			$ret--;
			$this->modify( '+1 week' );
			$rexMonth = $this->getMonth();
		}

		return $ret;
	}

	public function addSeconds( $dateTime, $duration )
	{
		static $cache = [];

		$key = $dateTime;
		$key .= ( $duration > 0 ) ? '+' . $duration : '-' . (-$duration);

		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$this->setDateTimeDb( $dateTime );

		if( $duration > 0 ){
			$this->modify( '+ ' . $duration . ' seconds' );
		}
		else {
			$this->modify( '- ' . (- $duration) . ' seconds' );
		}

		$thisDateTime = $this->getDateTimeDb();
		$cache[$key] = $thisDateTime;

		return $cache[$key];
	}

	public function dateTime( $dateDb, $timeSeconds )
	{
		static $cache = [];
		// static $secToDb = NULL;
		static $secToDb = [];

		$key = $dateDb . '-' . $timeSeconds;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		if( ! $secToDb ){
			$hours = array( '00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23' );
			$minutes = array( '00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55' );
			$minuteStep = 5;

			$secToDb = [];
			for( $h = 0; $h < count($hours); $h++ ){
				for( $m = 0; $m < count($minutes); $m++ ){
					$sec = $h * 60 * 60 + $m * 60 * $minuteStep;
					$db = $hours[$h] . $minutes[$m];
					$secToDb[ $sec ] = $db;
				}
			}
			$secToDb[ 24*60*60 ] = '2400';
			// $secToDb = array();
// _print_r( $secToDb );
// exit;
		}


		if( isset($secToDb[$timeSeconds]) ){
			$ret = $dateDb . $secToDb[$timeSeconds];
		}
		else {
			// echo "KOKO: '$timeSeconds'<br>";
			$ret = $this->setDateDb( $dateDb )
				->modify( '+ ' . $timeSeconds . ' seconds' )
				->getDateTimeDb()
				;
		}

		$cache[$key] = $ret;
		return $cache[$key];
	}

	public function splitToWeeks( array $dates )
	{
		$return = array();

		$thisWeek = array();
		foreach( $dates as $date ){
			$this->setDateDb( $date );
			$weekNo = $this->getWeekNo();
			if( ! isset($return[$weekNo]) ){
				$return[$weekNo] = array();
			}
			$return[$weekNo][] = $date;
		}

		return $return;
	}

	public function modify( $modify )
	{
		parent::modify( $modify );
		return $this;
	}

	public function smartModifyDown( $modify )
	{
		$this->modify( $modify );

		list( $qty, $measure ) = explode( ' ', $modify );
		switch( $measure ){
			case 'days':
				$this->setStartDay();
				break;
			case 'weeks':
				$this->setStartWeek();
				break;
			case 'months':
				$this->setStartMonth();
				break;
		}

		return $this;
	}

	public function smartModifyUp( $modify )
	{
		$this->modify( $modify );

		list( $qty, $measure ) = explode( ' ', $modify );
		switch( $measure ){
			case 'days':
				$this->setEndDay();
				break;
			case 'weeks':
				$this->setEndWeek();
				break;
			case 'months':
				$this->setEndMonth();
				break;
		}

		return $this;
	}

	/* date2 - date1 */
	public function getDifferenceInDays( $date1, $date2 )
	{
		$ts1 = $this->setDateDb( $date1 )->getTimestamp();
		$ts2 = $this->setDateDb( $date2 )->getTimestamp();

		$tsDiff = $ts2 - $ts1;
		$day = 24 * 60 * 60;

		$dayDiff = floor( $tsDiff / $day );
		return $dayDiff;
	}

	public function setTimezone( $tz )
	{
		if( is_array($tz) )
			$tz = $tz[0];

		if( ! $tz )
			$tz = date_default_timezone_get();

		$this->timezone = $tz;

		try {
			$tzObject = new DateTimeZone( $tz );
			parent::setTimezone( $tzObject );
		}
		catch( Exception $e ){
			// exit( "WRONG TIMEZONE: '$tz'" );
		}
	}

	public function setTimestamp( $ts )
	{
		if( ! strlen($ts) ){
			$ts = 0;
		}

		if( function_exists('date_timestamp_set') ){
			parent::setTimestamp( $ts );
		}
		else {
			parent::__construct( '@' . $ts );
		}

		return $this;
	}

	public function setNow()
	{
		$this->setTimestamp( time() );
		return $this;
	}

	public function formatDateDb()
	{
		return $this->getDateDb();
	}

	public function getDateDb()
	{
		$dateFormat = 'Ymd';
		$ret = $this->format( $dateFormat );
		return $ret;
	}

	public function setDateDb( $date )
	{
		list( $year, $month, $day ) = $this->_splitDate( $date );
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;

		$this->setDate( $year, $month, $day );
		$this->setTime( 0, 0, 0 );
		return $this;
	}

	public function setDateTimeDb( $datetime )
	{
		$date = substr($datetime, 0, 8);
		$this->setDateDb( $date );

		$hours = substr($datetime, 8, 2);
		$minutes = substr($datetime, 10, 2);
		$this->setTime( (int) $hours, (int) $minutes, 0 );

		return $this;
	}

	protected function _splitDate( $string )
	{
		$year = substr( $string, 0, 4 );
		$month = substr( $string, 4, 2 );
		$day = substr( $string, 6, 4 );
		$return = array( $year, $month, $day );
		return $return;
	}

	public function getDateTimeDb()
	{
		$date = $this->getDateDb();
		$time = $this->getTimeDb();
		$return = $date . $time;
		return $return;
	}

	public function getEndDateTimeDb( $date = NULL )
	{
		if( NULL === $date ){
			$date = $this->getDateDb();
		}
		$time = '2400';
		$return = $date . $time;
		return $return;
	}

	public function formatDateTimeDb2()
	{
		$return = $this->format('Y-m-d H:i:s');
		return $return;
	}

	public function getTimeDb( $dateTimeDb = NULL )
	{
		if( NULL === $dateTimeDb ){
			$h = $this->format('G');
			$m = $this->format('i');

			$h = str_pad( $h, 2, 0, STR_PAD_LEFT );
			$m = str_pad( $m, 2, 0, STR_PAD_LEFT );

			$ret = $h . $m;
		}
		else {
			$ret = substr( $dateTimeDb, 8, 4 );
		}

		return $ret;
	}

	public function setStartDay()
	{
		$this->setTime( 0, 0, 0 );
		return $this;
	}

	public function setEndDay()
	{
		// $this
			// ->setStartDay()
			// ->modify('+1 day')
			// ;
		$this
			->setTime( 23, 59, 59 )
			;
		return $this;
	}

	public function getTimeInDay( $dateTimeDb )
	{
		$ret = null;
		if( null === $dateTimeDb ) return $ret;

		$this->setDateTimeDb( $dateTimeDb );
		$timestamp = $this->getTimestamp();
		$date = $this->getDateDb();
		$this->setDateDb( $date );
		$timestamp2 = $this->getTimestamp();

		$ret = $timestamp - $timestamp2;
		$this->setTimestamp( $timestamp );

		return $ret;
	}

	public function setStartWeek()
	{
		$this->setStartDay();
		$weekDay = $this->getWeekday();

		$wso = static::$weekStartsOn;
		while( $weekDay != $wso ){
			$this->modify( '-1 day' );
			$weekDay = $this->getWeekday();
		}

		return $this;
	}

	public function isStartWeek( $dateDb )
	{
		return ( $this->getWeekday($dateDb) == static::$weekStartsOn ) ? TRUE : FALSE;
	}

	public function setEndWeek()
	{
		$this->setStartDay();
		$this->modify( '+1 day' );
		$weekDay = $this->getWeekday();

		$wso = static::$weekStartsOn;
		while( $weekDay != $wso ){
			$this->modify( '+1 day' );
			$weekDay = $this->getWeekday();
		}

		$this
			->modify( '-1 day' )
			->setEndDay()
			;
		return $this;
	}

	public function setStartMonth()
	{
		$year = $this->format('Y');
		$month = $this->format('m');
		$day = '01';

		$date = $year . $month . $day;
		$this
			->setDateDb( $date )
			->setTime( 0, 0, 0 )
			;

		return $this;
	}

	public function setEndMonth()
	{
		$currentMonth = $this->format('m');
		$nextMonth = $currentMonth;

		while( $currentMonth == $nextMonth ){
			$this->modify('+28 days');
			$nextMonth = $this->format('m');
		}

		$year = $this->format('Y');
		$month = $this->format('m');
		$day = '01';

		$date = $year . $month . $day;
		$this
			->setDateDb( $date )
			->modify('-1 day')
			->setEndDay()
			;

		return $this;
	}

	public function setStartYear()
	{
		$year = $this->format('Y');
		$month = '01';
		$day = '01';

		$date = $year . $month . $day;
		$this
			->setDateDb( $date )
			->setTime( 0, 0, 0 )
			;

		return $this;
	}

	public function setEndYear()
	{
		$this
			->setStartYear()
			->modify('+1 year')
			->modify('-1 day')
			;

		return $this;
	}

	public function getYear( $dateTimeDb = NULL )
	{
		if( NULL !== $dateTimeDb ){
			$this->setDateTimeDb( $dateTimeDb );
		}
		$ret = $this->format('Y');
		return $ret;
	}

	public function getDay( $dateTimeDb = null )
	{
		if( NULL !== $dateTimeDb ){
			$this->setDateTimeDb( $dateTimeDb );
		}
		$ret = $this->format('j');
		return $ret;
	}

	public function getWeekday( $dateDb = null )
	{
		static $cache = array();

		if( null !== $dateDb ){
			if( isset($cache[$dateDb]) ){
				return $cache[$dateDb];
			}
			$this->setDateDb( $dateDb );
		}

		$ret = $this->format('w');

		if( null !== $dateDb ){
			$cache[ $dateDb ] = $ret;
		}

		return $ret;
	}

	public function getMonthMatrix( array $skipWeekdays, $overlap )
	{
		// $overlap = TRUE; // if to show dates of prev/next month
		// $overlap = FALSE; // if to show dates of prev/next month

		$matrix = array();
		$currentMonthDay = 0;

		$currentDate = $this->formatDateDb();
		$thisMonth = substr( $currentDate, 4, 2 );

		$this->setStartMonth();
		if( $overlap ){
			$this->setStartWeek();
		}
		$startDate = $this->formatDateDb();

		$this
			->setDateDb( $currentDate )
			->setEndMonth()
			;
		if( $overlap ){
			$this->setEndWeek();
		}
		$this->modify('-1 second');

		$endDate = $this->formatDateDb();

		$rexDate = $startDate;
		if( $overlap ){
			$this->setDateDb( $startDate );
			$this->setStartWeek();
			$rexDate = $this->formatDateDb();
		}

		$this->setDateDb( $startDate );
		$this->setStartWeek();
		$rexDate = $this->formatDateDb();

		$this->setDateDb( $rexDate );
		while( $rexDate <= $endDate ){
			$week = array();
			$weekSet = FALSE;
			$thisWeekStart = $rexDate;

			for( $weekDay = 0; $weekDay <= 6; $weekDay++ ){
				$thisWeekday = $this->getWeekday();
				$setDate = $rexDate;

				if( ! $overlap ){
					if( 
						( $rexDate > $endDate ) OR
						( $rexDate < $startDate )
						){
						$setDate = NULL;
						}
				}

				// $week[ $thisWeekday ] = $setDate;

				if( (! $skipWeekdays) OR (! in_array($thisWeekday, $skipWeekdays)) ){
					if( NULL !== $setDate ){
						$rexMonth = substr( $setDate, 4, 2 );

						if( ! $overlap ){
							if( $rexMonth != $thisMonth ){
								$setDate = NULL;
							}
						}
					}

					$wki = $this->getWeekday();
					$week[ $wki ] = $setDate;
					if( NULL !== $setDate ){
						$weekSet = TRUE;
					}
				}

				$this->modify('+1 day');
				$rexDate = $this->formatDateDb();
			}

			if( $weekSet )
				$matrix[$thisWeekStart] = $week;
		}

		return $matrix;
	}

	public function getParts()
	{
		$full = $this->formatDateTimeDb();

		$year = substr( $full, 0, 4 );
		$month = substr( $full, 4, 2 );
		$day = substr( $full, 6, 2 );
		$hour = substr( $full, 8, 2 );
		$min = substr( $full, 10, 2 );

		$return = array( $year, $month, $day, $hour, $min );
		return $return;
	}

	public function getWeekdays()
	{
		$ret = [];

		$wkds = [ 0, 1, 2, 3, 4, 5, 6 ];
		$wkds = $this->sortWeekdays( $wkds );

		reset( $wkds );
		foreach( $wkds as $wkd ){
			$ret[ $wkd ] = $wkd;
		}
		return $ret;
	}

	public function getSortedWeekdays()
	{
		$ret = [ 0, 1, 2, 3, 4, 5, 6 ];
		$ret = $this->sortWeekdays( $ret );
		return $ret;
	}

	public function sortWeekdays( $wds )
	{
		$ret = [];
		$later = [];

		sort( $wds );
		reset( $wds );
		foreach( $wds as $wd ){
			if( $wd < static::$weekStartsOn )
				$later[] = $wd;
			else
				$ret[] = $wd;
		}
		$ret = array_merge( $ret, $later );
		return $ret;
	}

	public function getDuration( $dateTime1, $dateTime2 )
	{
		static $cache = [];

		if( $dateTime1 == $dateTime2 ){
			return 0;
		}

		$key = $dateTime1 . '-' . $dateTime2;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		// $timestamp1 = $this->getTimestamp();
		$timestamp1 = $this->setDateTimeDb( $dateTime1 )->getTimestamp();
		$timestamp2 = $this->setDateTimeDb( $dateTime2 )->getTimestamp();

		$ret = abs( $timestamp2 - $timestamp1 );

		$cache[$key] = $ret;

		return $ret;
	}

	public function getMonth()
	{
		$month = $this->format('n');
		return $month;
	}

	public function getWeekNo( $date = NULL )
	{
		if( NULL !== $date ){
			$this->setDateDb( $date );
		}

		$ret = $this->format('W'); // but it works out of the box for week starts on monday
		$weekday = $this->getWeekday();
		if( ! $weekday ){ // sunday
			if( ! static::$weekStartsOn ){
				$ret = $ret + 1;
			}
		}

		return $ret;
	}

	public function getAllDates( $startDate, $endDate, $withStartEnd = false )
	{
		static $cache = array();
		$key = $startDate . '-' . $endDate;
		$key .= $withStartEnd ? '-1' : '-0';
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$return = array();

		$rexDate = $startDate;
		$this->setDateDb( $rexDate );
		while( $rexDate <= $endDate ){
			if( $withStartEnd ){
				$startDateTime = $this->dateTime( $rexDate, 0 );
				$endDateTime = $this->getEndDateTimeDb( $rexDate );
				$return[ $rexDate ] = array( $startDateTime, $endDateTime );
			}
			else {
				$return[] = $rexDate;
			}
			$rexDate = $this->getNextDate( $rexDate );
		}

		$cache[$key] = $return;
		return $cache[$key];
	}

	public function splitByWeek( array $dates )
	{
		$ret = [];

		$countDates = count( $dates );
		$startDate = $dates[ 0 ];
		$endDate = $dates[ $countDates - 1 ];

		$parseDates = $dates;

		$startParse = $this->setDateDb( $startDate )->setStartWeek()->getDateDb();
		$rexDate = $this->setDateDb( $startDate )->modify( '-1 day' )->getDateDb();
		while( $rexDate >= $startParse ){
			array_unshift( $parseDates, $rexDate );
			$rexDate = $this->getPrevDate( $rexDate );
		}

		$endParse = $this->setDateDb( $endDate )->setEndWeek()->getDateDb();
		$rexDate = $this->setDateDb( $endDate )->modify( '+1 day' )->getDateDb();
		while( $rexDate <= $endParse ){
			$parseDates[] = $endParse;
			$rexDate = $this->getNextDate( $rexDate );
		}

		$week = [];
		foreach( $parseDates as $rexDate ){
			$append = in_array( $rexDate, $dates ) ? $rexDate : null;
			$week[] = $append;

			if( 7 == count($week) ){
				$ret[] = $week;
				$week = [];
			}
		}

		return $ret;
	}

	public function getNextDate( $date )
	{
		static $cache = array();
		$key = $date;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$ret = $this->setDateDb( $date )->modify( '+1 day' )->getDateDb();
		$cache[$key] = $ret;
		return $cache[$key];
	}

	public function getPrevDate( $date )
	{
		static $cache = array();
		$key = $date;
		if( isset($cache[$key]) ){
			return $cache[$key];
		}

		$ret = $this->setDateDb( $date )->modify( '-1 day' )->getDateDb();
		$cache[$key] = $ret;
		return $cache[$key];
	}

	public function findDays( $fromDateTime, $toDateTime )
	{
		$ret = [];

		$startDate = $this->setDateTimeDb( $fromDateTime )->getDateDb();
		$endDate = $this->setDateTimeDb( $toDateTime )->getDateDb();

		$ret = $this->getAllDates( $startDate, $endDate, true );

		return $ret;
	}

	public function findWeeks( $fromDateTime, $toDateTime )
	{
		$ret = [];

		$startDate = $this->setDateTimeDb( $fromDateTime )->getDateDb();
		$endDate = $this->setDateTimeDb( $toDateTime )->getDateDb();

		$rexDate = $this->setDateDb( $startDate )->setStartWeek()->getDateDb();
		while( $rexDate <= $endDate ){
			$start = $this->setDateDb( $rexDate )->getDateTimeDb();
			$nextDate = $this->setDateDb( $rexDate )->modify( '+1 week' )->getDateDb();
			$endDate = $this->getPrevDate( $nextDate );
			$end = $this->setDateDb( $endDate )->getDateTimeDb();
			$ret[ $rexDate ] = [ $start, $end ];
			$rexDate = $nextDate;
		}
		return $ret;
	}

	public function recurValidOnDate( $date, $recurType, $recurDetail )
	{
		$ret = false;

		if( 'daily' == $recurType ){
			$ret = true;
			return $ret;
		}

		if( 'weekly' == $recurType ){
			$wkd = $this->getWeekday( $date );
			if( ! is_array($recurDetail) ) $recurDetail = [ $recurDetail ];
			$ret = in_array( $wkd, $recurDetail );
			return $ret;
		}

		return $ret;
	}
}