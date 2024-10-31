<?php
namespace Plainware\PlainEventCalendar;

class PageInstall
{
	public static function title( array $x, App $app )
	{
		$ret = '__Install__';
		return $ret;
	}

	public static function post( array $x, array $values, App $app )
	{
		$modules = $app->ModelInstall->modules();
		$app->ModelInstall->doUp( $modules );
		$x['redirect'] = 'install-ok';
		return $x;
	}

	public static function get( array $x, App $app )
	{
		return $x;
	}

	public static function render( array $x, App $app )
	{
?>

<form method="post">

<section>
	<button type="submit">__Click To Install__</button>
</section>

</form>

<?php
	}
}