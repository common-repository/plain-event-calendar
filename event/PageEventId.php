<?php
namespace Plainware\PlainEventCalendar;

class PageEventId
{
	public static function title()
	{
		$ret = '__Edit Event__';
		return $ret;
	}

	public static function vm( array $x, App $app )
	{
		$m = $x['$m'];

		$ret = [];

	// date
		$ret['date'] = strlen($m['date_start']) ? $m['date_start'] : null;
		if( isset($x['date']) ){
			$ret['date'] = $x['date'];
		}

		$ret['dateto'] = strlen($m['date_end']) ? $m['date_end'] : null;
		if( isset($x['dateto']) ){
			$ret['dateto'] = $x['dateto'];
		}

		return $ret;
	}

	public static function get( array $x, App $app )
	{
		$model = $app->ModelEvent;

		$id = $x[0];
		$m = $model->findById( $id );
		if( ! $m ) return $x;

		$x['$m'] = $m;

		$vm = $app->{ __CLASS__ }->vm( $x );
		$x[ '$vm' ] = $vm;

	// ui
		$x['input-title'] = $app->Html->inputText( 'title', $m['title'] );
		$x['input-title']['validate'][] = $app->Html->validatorRequired();
		$x['input-title']['validate'][] = $app->Html->validatorUniqueProp([
			'class' => $model,
			'prop' => 'title',
			'skip' => $m['id'],
		]);

		$x['input-description'] = $app->Html->InputTextarea( 'description', $m['description'] );

		return $x;
	}

	public static function post( array $x, array $values, App $app )
	{
		$vm = $x[ '$vm' ];
		$m = $x[ '$m' ];

		$model = $app->ModelEvent;

		$m2 = $m;

		$m2['date_start'] = $vm['date'];
		$m2['date_end'] = $vm['dateto'];

		foreach( $values as $k => $v ){
			$m2[ $k ] = $v;
		}

		$m = $model->update( $m, $m2 );
		$x['redirect'] = '..';

		return $x;
	}

	public static function render( array $x, App $app )
	{
		$vm = $x[ '$vm' ];
?>

<form method="post">

<section class="pw-grid-2">
	<section>
		<fieldset>
			<legend>__Event Date__</legend>

			<?php if( $vm['date'] ): ?>
				<article>
					<p>
						<?php echo esc_html( $app->TimeFormat->formatDateWithWeekday($vm['date']) ); ?>
					</p>
				</article>
				<nav>
					<a href="URI:.date-selector?back=date<?php if( strlen($vm['dateto']) ) : ?>&max=<?php echo esc_attr($vm['dateto']); ?><?php endif; ?>&v=<?php echo esc_attr($vm['date']); ?>">__Change__</a>
				</nav>
			<?php else : ?>
				<nav role="menu">
					<a href="URI:.date-selector?back=date&max=<?php echo esc_attr($vm['dateto']); ?>">__Select__</a>
				</nav>
			<?php endif; ?>
		</fieldset>
	</section>

	<section>
		<fieldset>
			<legend>__Event End Date__</legend>

			<?php if( $vm['dateto'] ): ?>
				<article>
					<p>
						<?php echo esc_html( $app->TimeFormat->formatDateWithWeekday($vm['dateto']) ); ?>
					</p>
				</article>
				<nav>
					<a href="URI:.date-selector?back=dateto<?php if( strlen($vm['date']) ) : ?>&min=<?php echo esc_attr($vm['date']); ?><?php endif; ?>&v=<?php echo esc_attr($vm['dateto']); ?>">__Change__</a>
				</nav>
			<?php else : ?>
				<nav role="menu">
					<a href="URI:.date-selector?back=dateto&min=<?php echo esc_attr($vm['date']); ?>">__Select__</a>
				</nav>
			<?php endif; ?>
		</fieldset>
	</section>
</section>

<section>
	<label>
		<span>__Event Name__</span>
		[[input-title]]
	</label>
</section>

<section>
	<label>
		<span>__Event Description__</span>
		[[input-description]]
	</label>
</section>

<section>
	<button type="submit">__Update Event__</button>
</section>

</form>

<?php
	}

	public static function nav( array $x, App $app )
	{
		$id = $x[0];

		$ret = [];
		$ret[ '9-delete' ] = [ '.event-' . $id . '-delete', '<i>&times;</i>__Delete__' ];

		return $ret;
	}
}