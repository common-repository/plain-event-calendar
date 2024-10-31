<?php
namespace Plainware\PlainEventCalendar;

class PageDateSelector
{
	public static function title( array $x, App $app )
	{
		if( isset($x['mode']) && ('range' == $x['mode']) ){
			$ret = isset( $x['start'] ) ? '__Select End Date__' : '__Select Start Date__';
		}
		else {
			$ret = '__Select Date__';
		}

		return $ret;
	}

	public static function get( array $x, App $app )
	{
		$t = $app->Time;

		if( ! isset($x['v']) ) $x['v'] = null;
		if( ! isset($x['cal']) ) $x['cal'] = $x['v'] ? $x['v'] : $t->setNow()->getDateDb();

		$max = isset( $x['max'] ) ? $x['max'] : null;

		$min = null;
		if( isset($x['min']) ) $min = $x['min'];
		if( isset($x['start']) ) $min = $x['start'];

		if( isset($x['mode']) && ('range' == $x['mode']) && isset($x['start']) ){
			if( isset($x['duramin']) ){
				$min = $t->setDateDb( $x['start'] )->modify( '+' . ($x['duramin'] - 1 ) . ' days' )->getDateDb();
			}
			if( isset($x['duramax']) ){
				$max = $t->setDateDb( $x['start'] )->modify( '+' . ($x['duramax'] - 1 ) . ' days' )->getDateDb();
			}
		}

		$x['$min'] = $min;
		$x['$max'] = $max;

		return $x;
	}

	public static function render( array $x, App $app )
	{
		$back = isset( $x['back'] ) ? $x['back'] : 'date';

		$cal = $x[ 'cal' ];
		$v = $x[ 'v' ];

		$min = $x['$min'];
		$max = $x['$max'];

		$t = $app->Time;

		$nextCal = $t->setDateDb( $cal )->modify( '+1 year' )->setStartYear()->getDateDb();
		$prevCal = $t->setDateDb( $cal )->modify( '-1 year' )->setStartYear()->getDateDb();
		$nextCalView = $t->getYear( $nextCal );
		$prevCalView = $t->getYear( $prevCal );

		$today = $t->setNow()->getDateDb();
		$t->setDateDb( $cal );

		$months = [];
		$t->setStartYear();
		while( count($months) < 12 ){
			$date = $t->getDateDb();
			$months[ $date ] = $t->getMonthMatrix( [], false );
			$t->setDateDb( $date )->modify( '+1 month' );
		}

		$wkds = $t->getWeekdays();
?>

<section>
	<nav role="menu">
		<?php if( (! $min) OR ($prevCal >= $min) ) : ?>
			<a href="URI:.?cal=<?php echo esc_attr($prevCal); ?>"><i>&larr;</i><?php echo esc_html($prevCalView); ?></a>
		<?php endif; ?>

		<?php if( (! $max) OR ($nextCal <= $max) ) : ?>
			<a href="URI:.?cal=<?php echo esc_attr($nextCal); ?>"><?php echo esc_html($nextCalView); ?><i>&rarr;</i></a>
		<?php endif; ?>
	</nav>
</section>

<section>
<div class="pw-grid-3">
	<?php foreach( $months as $monthDate => $month ) : ?>
		<div>
			<table class="pw-month-calendar">
				<thead>
					<tr>
						<th colspan="7"><?php echo esc_html( $app->TimeFormat->formatMonthYear($monthDate) ); ?></th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<?php foreach( $wkds as $wkd ) : ?>
							<td>
								<?php echo esc_html( $app->TimeFormat->formatWeekday($wkd) ); ?>
							</td>
						<?php endforeach; ?>
					</tr>

				<?php foreach( $month as $week ) : ?>
					<tr>
						<?php foreach( $week as $date ) : ?>
							<?php
							$class = [];

							$disabled = false;
							if( $date && $min && ($date < $min) ) $disabled = true;
							if( $date && $max && ($date > $max) ) $disabled = true; 

							if( $date && ($date < $today) ) $class[] = 'pw-month-calendar-past';
							if( $date && ($date == $v) ) $class[] = 'pw-month-calendar-current';

							if( isset($x['mode']) && ('range' == $x['mode']) ){
								if( isset($x['start']) ){
									$to = '..?' . $back . '=' . $x['start'] . '&' . $back . 'to' . '=' . $date;
								}
								else {
									$to = '.?start' . '=' . $date;
								}
							}
							else {
								$to = '..?' . $back . '=' . $date;
							}
							?>
							<td<?php if( $class ) : ?> class="<?php echo join( ' ', $class ); ?>"<?php endif; ?>>
							<?php if( $date ) : ?>
								<?php $view = $t->getDay( $date ); ?>
								<?php if( $disabled ) : ?>
									<?php echo esc_html($view); ?>
								<?php elseif( $date && ($date == $v) ) : ?>
									<?php echo esc_html($view); ?>
								<?php else: ?>
									<a href="URI:<?php echo esc_attr($to); ?>"><?php echo esc_html($view); ?></a>
								<?php endif; ?>
							<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?> 
</div>
</section>

<section>
	<nav role="menu">
		<?php if( (! $min) OR ($prevCal >= $min) ) : ?>
			<a href="URI:.?cal=<?php echo esc_attr($prevCal); ?>"><i>&larr;</i><span><?php echo esc_html($prevCalView); ?></span></a>
		<?php endif; ?>

		<?php if( (! $max) OR ($nextCal <= $max) ) : ?>
			<a href="URI:.?cal=<?php echo esc_attr($nextCal); ?>"><span><?php echo esc_html($nextCalView); ?></span><i>&rarr;</i></a>
		<?php endif; ?>
	</nav>
</section>

<?php
	}
}