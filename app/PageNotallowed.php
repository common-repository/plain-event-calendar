<?php
namespace Plainware\PlainEventCalendar;

class PageNotallowed
{
	public static function title( array $x, App $app )
	{
		$ret = '__Not Allowed__';
		return $ret;
	}

	public static function get( array $x, App $app )
	{
		return $x;
	}

	public static function render( array $x, App $app )
	{
?>

__You are not allowed to view this page__

<?php
	}
}