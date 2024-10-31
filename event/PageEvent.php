<?php
namespace Plainware\PlainEventCalendar;

class PageEvent
{
	public static function title( array $x, App $app )
	{
		return '__Events__';
	}

	public static function can( array $x, App $app )
	{
		if( ! is_admin() ) return false;
		if( ! current_user_can('manage_options') ) return false;
	}

	public static function nav( array $x, App $app )
	{
		$ret = [];
		$ret[] = [ '.event-new', '__Add New Event__' ];
		return $ret;
	}

	public static function post( array $x, array $values, App $app )
	{
		return $app->Html->postBulkAction( $app->ModelEvent, $x['$models'], $x, $values );
	}

	public static function get( array $x, App $app )
	{
		$q = $app->ModelEvent->q();
		$models = $app->ModelEvent->find( $q );

		$x[ '$models' ] = $models;

		$x = $app->Html->getBulkAction( $app->ModelEvent, $models, $x );

		$table = $app->Html->table( $models, $x );
		$table[ '@columns' ] = __CLASS__ . '::renderColumns';
		$x[ 'table' ] = $table;

		return $x;
	}

	public static function renderColumns()
	{
		$ret = [];
		$ret[ 'event' ] = [ 'label' => '__Event__', '@render' => __CLASS__ . '::renderColEvent', ];
		$ret[ 'when' ] = [ 'label' => '__When__', '@render' => __CLASS__ . '::renderColWhen', ];
		return $ret;
	}

	public static function renderColEvent( $m, array $x, App $app )
	{
?>
<a href="URI:.event-<?php echo esc_attr($m['id']); ?>">
	<?php echo esc_html( $m['title'] ); ?><?php if( $app->ModelEvent->isArchived($m) ) : ?><i> &mdash; __Archived__</i><?php endif; ?>
</a>
<?php
	}

	public static function renderColWhen( $m, array $x, App $app )
	{
?>
<?php echo esc_html( $app->WidgetEvent->renderWhen($m) ); ?>
<?php
	}

	public static function render( array $x, App $app )
	{
?>

[[ table ]]

<?php
	}
}