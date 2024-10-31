<?php
namespace Plainware\PlainEventCalendar;

class PageUninstall
{
	public static function title( array $x, App $app )
	{
		return '__Uninstall__';
	}

	public static function validate( array $x, App $app )
	{
		$ret = [];
		$ret[] = [ 'sure', $app->Html . '::validateRequired' ];
		return $ret;
	}

	public static function post( array $x, array $values, App $app )
	{
		$modules = $app->ModelInstall->modules();
		$app->ModelInstall->doDown( $modules );
		$x['redirect'] = '';
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
	<strong>__All current data will be deleted.__</strong>
</section>

<section>
	<label>
		<?php echo $app->Html->renderInput( ['name' => 'sure', 'type' => 'checkbox', 'value' => 1] ); ?>
		<span>__Are you sure?__</span>
	</label>
</section>

<section>
	<button type="submit">__Confirm Uninstall__</button>
</section>

</form>

<?php
	}
}