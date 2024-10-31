<?php
namespace Plainware\PlainEventCalendar;

class PageEventIdDelete
{
	public static function title( array $x, App $app )
	{
		return '__Delete Event__';
	}

	public static function get( array $x, App $app )
	{
		$model = $app->ModelEvent;

		$id = $x[0];
		$m = $model->findById( $id );
		$x[ '$m' ] = $m;

		$x['input-sure'] = $app->Html->inputCheckbox( 'sure', 1, false );
		$x['input-sure']['validate'][] = $app->Html->validatorRequired();

		return $x;
	}

	public static function post( array $x, array $values, App $app )
	{
		$model = $app->ModelEvent;

		$m = $x[ '$m' ];
		$m = $model->delete( $m );
		$x['redirect'] = '..--..';

		return $x;
	}

	public static function render( array $x, App $app )
	{
?>

<form method="post">

<section>
	<label>
		[[input-sure]]
		<span>__Are you sure?__</span>
	</label>
</section>

<section>
	<strong><button type="submit">__Confirm Delete__</button></strong>
</section>

</form>

<?php
	}

	public static function nav( array $x, App $app )
	{
		$ret = [];

		$ret[ '1-cancel' ] = [ '..', '<i>&larr;</i><span>__Cancel__</span>' ];

		return $ret;
	}
}