<?php
namespace Plainware\PlainEventCalendar;

class PageInstallOk
{
	public static function title( array $x, App $app )
	{
		$ret = '__Installation Successful__';
		return $ret;
	}

	public static function get( array $x, App $app )
	{}

	public static function render( array $x, App $app )
	{
?>

<p>
__Thank you for installing our product.__
</p>

<nav>
	<a href="URI:event">__Please proceed to the home page__</a>
</nav>

<?php
	}
}