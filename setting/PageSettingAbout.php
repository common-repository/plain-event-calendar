<?php
namespace Plainware\PlainEventCalendar;

class PageSettingAbout
{
	public static function title( array $x )
	{
		$ret = '__About__';
		return $ret;
	}

	public static function nav( array $x )
	{
		$ret = [];

		$ret[ '9-uninstall'] = [ '.uninstall', '__Uninstall__' ];

		return $ret;
	}

	public static function get( array $x, App $app )
	{
	// ui
		$installedVersion = $app->version();
		$x['$version'] = $installedVersion;
		return $x;
	}

	public static function render( array $x )
	{
?>

<article>
	<dl>
		<dt>__Version__</dt>
		<dd>
			<?php echo esc_html( $x['$version'] ); ?>
		</dd>
	</dl>
</article>

<?php
	}
}