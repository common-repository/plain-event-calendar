<?php
namespace Plainware\PlainEventCalendar;

class PageFront
{
	public static function title( array $x, App $app )
	{
		$ret = '__Events__';

		$vm = $app->{ __CLASS__ }->vm( $x );
		$cal = $vm['cal'];
		if( $cal ){
			// $ret = $app->TimeFormat->formatDateWithWeekday( $cal );
			$ret = $app->TimeFormat->formatDate( $cal );
		}

		return $ret;
	}

	public static function vm( array $x, App $app )
	{
		$ret = [];

		$cal = null;
		if( isset($x['cal']) ){
			$cal = $x['cal'];
		}
		else {
			$today = $app->Time->setNow()->getDateDb();

		// find nearest next event
			$q = $app->ModelEvent->q();
			$q->where( 'date_start', '>=', $today );
			$q->orderBy( 'date_start', 'ASC' );
			$q->limit( 1 );
			$models = $app->ModelEvent->find( $q );
			if( $models ){
				$m = current( $models );
				$cal = $m['date_start'];
			}
		}
		$ret['cal'] = $cal;

		return $ret;
	}

	public static function nav( array $x, App $app )
	{
		$vm = $app->{ __CLASS__ }->vm( $x );

		$today = $app->Time->setNow()->getDateDb();

		$cal = $vm['cal'];

		$nextCal = null;
		$prevCal = null;

	// find nearest next event
		if( $cal ){
			$q = $app->ModelEvent->q();
			$q->where( 'date_start', '>', $cal );
			$q->orderBy( 'date_start', 'ASC' );
			$q->limit( 1 );
			$models = $app->ModelEvent->find( $q );
			if( $models ){
				$m = current( $models );
				$nextCal = $m['date_start'];
			}

			$q = $app->ModelEvent->q();
			$q->where( 'date_start', '<', $cal );
			$q->where( 'date_start', '>=', $today );

			$q->orderBy( 'date_start', 'DESC' );
			$q->limit( 1 );
			$models = $app->ModelEvent->find( $q );
			if( $models ){
				$m = current( $models );
				$prevCal = $m['date_start'];
			}
		}

		$ret = [];

		if( $prevCal ){
			$prevLabel = $app->TimeFormat->formatDateWithWeekday( $prevCal );
			$ret[] = [ '.?cal=' . $prevCal, '<i>&larr;</i>' . $prevLabel ];
		}

		if( $nextCal ){
			$nextLabel = $app->TimeFormat->formatDateWithWeekday( $nextCal );
			$ret[] = [ '.?cal=' . $nextCal, $nextLabel . '<i>&rarr;</i>' ];
		}

		return $ret;
	}

	public static function get( array $x, App $app )
	{
		$vm = $app->{ __CLASS__ }->vm( $x );

		$cal = $vm['cal'];

		$q = $app->ModelEvent->q();
		$q->where( 'date_start', '=', $cal );
		$models = $app->ModelEvent->find( $q );

		$x[ '$models' ] = $models;

		return $x;
	}

	public static function post( array $x, array $values, App $app )
	{
		return $x;
	}

	public static function render( array $x, App $app )
	{
		$models = $x[ '$models' ];
?>

<?php foreach( $models as $m ) : ?>
	<article class="pec-event">
		<time>
			<?php echo esc_html($app->WidgetEvent->renderWhen($m)); ?>
		</time>
	
		<h3>
			<?php echo esc_html($m['title']); ?>
		</h3>

		<?php echo esc_html($m['description']); ?>
	</article>
<?php endforeach; ?>

<?php
	}
}