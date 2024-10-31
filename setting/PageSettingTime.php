<?php
namespace Plainware\PlainEventCalendar;

class PageSettingTime
{
	public static function title( array $x )
	{
		$ret = '__Date & Time__';
		return $ret;
	}

	public static function post( array $x, array $values, App $app )
	{
		foreach( $values as $k => $v ){
			$app->ModelSetting->set( $k, $v );
		}
		$x[ 'redirect' ] = 'setting-time';
		return $x;
	}

	public static function get( array $x, App $app )
	{
		$ks = [ 'time_date_format', 'time_time_format', 'time_week_starts' ];

		$v = [];
		foreach( $ks as $k ) $v[$k] = $app->ModelSetting->get( $k );
		$x[ '$v' ] = $v;

		return $x;
	}

	public static function render( array $x, App $app )
	{
		$v =  $x[ '$v' ];
?>

<form method="post">

<?php
$option = [
	'j M Y',

	'n/j/Y',
	'm/d/Y',
	'm-d-Y',
	'm.d.Y',

	'j/n/Y',
	'd/m/Y',
	'd-m-Y',
	'd.m.Y',

	'Y/m/d',
	'Y-m-d',
	'Y.m.d',
	];

$option = array_flip( $option );
foreach( array_keys($option) as $k ){
	$option[$k] = date($k);
}
?>
<section>
	<label>
		<span>__Date Format__</span>
		<?php echo $app->Html->renderSelect(['name' => 'time_date_format'], $option, $v['time_date_format'] ); ?>
	</label>
</section>

<?php
// time_time_format
$testTimes = [ '202206100800', '202206101400', '202206101515' ];
$option = [ 'g:ia', 'g:i A', 'H:i' ];
$option = [ 'g:ia', 'g:i A', '12short', '12xshort', 'H:i', '24short' ];

$option = array_flip( $option );

$original = $app->TimeFormat->getTimeFormat();

foreach( array_keys($option) as $k ){
	$app->TimeFormat->setTimeFormat( $k );
	$view = [];
	foreach( $testTimes as $test ){
		$view[] = $app->TimeFormat->formatTime( $test );
	}
	$view = join( ', ', $view );
	$option[ $k ] = $view;
}

$app->TimeFormat->setTimeFormat( $original );
?>
<section>
	<label>
		<span>__Time Format__</span>
		<?php echo $app->Html->renderSelect( ['name' => 'time_time_format'], $option, $v['time_time_format'] ); ?>
	</label>
</section>

<?php
$option = [ '0' => '__Sun__', '1' => '__Mon__' ];
?>
<section>
	<label>
		<span>__Week Starts On__</span>
		<?php echo $app->Html->renderSelect( ['name' => 'time_week_starts'], $option, $v['time_week_starts'] ); ?>
	</label>
</section>

<section>
	<button type="submit">__Save__</button>
</section>

</form>

<?php
	}
}