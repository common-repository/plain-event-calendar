<?php
namespace Plainware\PlainEventCalendar;

class PageEventNew
{
	public static function title()
	{
		$ret = '__Add New Event__';
		return $ret;
	}

	public static function post( array $x, array $values, App $app )
	{
		$model = $app->ModelEvent;

		$vm = $x[ '$vm' ];
		$m = $x[ '$m' ];

		$m['date_start'] = $vm['date'];
		$m['date_end'] = $vm['dateto'];

		foreach( $values as $k => $v ){
			$m[ $k ] = $v;
		}

		$m = $model->create( $m );
		$x['redirect'] = '..';

		return $x;
	}

	public static function vm( array $x, App $app )
	{
		$ret = [];

	// date
		$ret['date'] = null;
		if( isset($x['date']) ){
			$ret['date'] = $x['date'];
		}

		$ret['dateto'] = $ret['date'];
		if( isset($x['dateto']) ){
			$ret['dateto'] = $x['dateto'];
		}

		return $ret;
	}

	public static function get( array $x, App $app )
	{
		$model = $app->ModelEvent;

		$vm = $app->{ __CLASS__ }->vm( $x );

		if( ! $vm['date'] ){
			$x['redirect'] = [ '.date-selector', ['back' => 'date'] ];
			return $x;
		}

		$x[ '$vm' ] = $vm;

	// new m
		$m = $model->construct();
		$x['$m'] = $m;

	// input
		$x['input-title'] = $app->Html->InputText( 'title', $m['title'] );
		$x['input-title']['validate'][] = $app->Html->validatorRequired();
		$x['input-title']['validate'][] = $app->Html->validatorUniqueProp([
			'class' => $model,
			'prop' => 'title',
			'skip' => $m['id'],
		]);

		$x['input-description'] = $app->Html->InputTextarea( 'description', $m['description'] );

		return $x;
	}

	public static function render( array $x, App $app )
	{
		$vm = $x[ '$vm' ];

		$ready = true;
		foreach( $vm as $k => $v ){
			if( null === $v ){
				$ready = false;
				// echo "NOTREADY '$k'<br>";
				break;
			}
		}
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
					<a href="URI:.date-selector?back=date&max=<?php echo esc_attr($vm['dateto']); ?>&v=<?php echo esc_attr($vm['date']); ?>">__Change__</a>
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
					<a href="URI:.date-selector?back=dateto&min=<?php echo esc_attr($vm['date']); ?>&v=<?php echo esc_attr($vm['dateto']); ?>">__Change__</a>
				</nav>
			<?php else : ?>
				<nav role="menu">
					<a href="URI:.date-selector?back=dateto&min=<?php echo esc_attr($vm['date']); ?>">__Select__</a>
				</nav>
			<?php endif; ?>
		</fieldset>
	</section>
</section>

<?php if( $ready ) : ?>
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
	<button type="submit">__Add New Event__</button>
</section>
<?php endif; ?>

</form>

<?php
	}
}