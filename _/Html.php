<?php
namespace Plainware;

class Html
{
	public $inputId = 1;
	public $formValues = [];
	public $formErrors = [];

	public function setFormValues( array $values )
	{
		$this->formValues = $values;
		return $this;
	}

	public function setFormErrors( array $errors )
	{
		$this->formErrors = $errors;
		return $this;
	}

	public static function input( $name, $value )
	{
		static $inputId = 1;

		$ret = [
			'value'		=> $value,
			'name'		=> $name,
			'rawid'		=> $inputId,
			'id'			=> 'pw-input-' . $inputId,
			'error'		=> '',
			'validate'	=> [],
			'@grab'		=> __CLASS__ . '::grab',
		];

		$inputId++;

		return $ret;
	}

	public static function htmlAttr( array $attr )
	{
		$ret = [];

		foreach( $attr as $k => $v ){
			if( is_bool($v) ){
				if( $v ){
					$ret[] = $k;
				}
			}
			else {
				if( is_array($v) ){
					$v = join( ' ', $v );
				}
				$ret[] = $k . '="' . esc_attr( $v ). '"';
			}
		}

		$ret = join( ' ', $ret );
		return $ret;
	}

	public function renderInputError( $name )
	{
		$err = $this->inputError( $name );
		if( ! $err ) return;
?>
<div><strong><?php echo esc_html( $err ); ?></strong></div>
<?php
	}

	public function inputError( $name )
	{
		$ret = isset( $this->formErrors[$name] ) ? $this->formErrors[$name] : null;
		return $ret;
	}

	public function inputValue( $name, $default )
	{
		$ret = $default;
		$formValues = $this->formValues;

		$pos = strpos( $name, '[' );
		if( false === $pos ){
			if( array_key_exists($name, $formValues) ){
				$ret = $formValues[ $name ];
			}
		}
		else {
			$shortName = substr( $name, 0, $pos );
			$index = substr( $name, $pos + 1, -1 );

			if( array_key_exists($shortName, $formValues) && array_key_exists($index, $formValues[$shortName]) ){
				$ret = $formValues[ $shortName ][ $index ];
			}
		}

		return $ret;
	}

	public function renderInput( array $attr )
	{
		$name = $attr['name'];
		$value = array_key_exists( 'value', $attr ) ? $attr['value'] : null;

		if( ! isset($attr['type']) ){
			$attr['type'] = 'text';
		}

		if( ! isset($attr['id']) ){
			$attr['id'] = 'pw-input-' . $this->inputId++;
		}

		$htmlAttr = static::htmlAttr( $attr );
?><input <?php echo $htmlAttr; ?>><?php
	}

	public function renderInputText( $name, $value, array $attr = [] )
	{
		$attr['type'] = 'text';
		$attr['name'] = $name;

		$formValue = $this->inputValue( $name, null );
		if( null !== $formValue ){
			$value = $formValue;
		}
		$attr['value'] = $value;

		return $this->renderInput( $attr ) . $this->renderInputError( $name );
	}

	public function renderInputCheckbox( $name, $value, $checked, array $attr = [] )
	{
		$attr['type'] = 'checkbox';
		$attr['name'] = $name;
		$attr['value'] = $value;

		$formChecked = $this->inputValue( $name, null );
		if( (null !== $formChecked) && (false !== $formChecked) ){
			$checked = true;
		}
		$attr['checked'] = $checked ? true : false;

		return $this->renderInput( $attr );
	}

	public function renderInputRadio( $name, $value, $checked, array $attr = [] )
	{
		$attr['type'] = 'radio';
		$attr['name'] = $name;
		$attr['value'] = $value;

		$formValue = $this->inputValue( $name, null );
		if( (null !== $formValue) && ($value == $formValue) ){
			$checked = true;
		}
		$attr['checked'] = $checked ? true : false;

		return $this->renderInput( $attr );
	}

	public function renderInputTextarea( $name, $value, array $attr = [] )
	{
		$attr['name'] = $name;
		if( ! isset($attr['id']) ){
			$attr['id'] = 'pw-input-' . $this->inputId++;
		}

		$formValue = $this->inputValue( $name, null );
		if( null !== $formValue ){
			$value = $formValue;
		}

		$htmlAttr = static::htmlAttr( $attr );
?>
<textarea <?php echo $htmlAttr; ?>><?php echo esc_textarea($value); ?></textarea>
<?php echo $this->renderInputError( $name ); ?>
<?php
	}

	public function renderInputSelect( $name, array $option, $value, array $attr = [] )
	{
		$attr['name'] = $name;
		if( ! isset($attr['id']) ){
			$attr['id'] = 'pw-input-' . $this->inputId++;
		}

		$formValue = $this->inputValue( $name, null );
		if( null !== $formValue ){
			$value = $formValue;
		}

		$htmlAttr = static::htmlAttr( $attr );
?>

<select <?php echo $htmlAttr; ?>>
<?php foreach( $option as $k => $v ) : ?>
	<option value="<?php echo esc_attr($k); ?>"<?php if( $value == $k ) : ?> selected<?php endif; ?>><?php echo esc_html( $v ); ?></option>
<?php endforeach; ?>
</select>
<?php echo $this->renderInputError( $name ); ?>

<?php
	}

	public function renderInputRadioSet( $name, array $option, $value, array $attr )
	{
?>

<fieldset>
	<div class="pw-inline-list">
	<?php foreach( $option as $k => $label ) : ?>
		<div>
			<label>
				<?php $checked = ( $k == $value ) ? true : false; ?>
				<?php echo $this->renderInputRadio( $name, $k, $checked, $attr ); ?><span><?php echo $label; ?></span>
			</label>
		</div>
	<?php endforeach; ?>
	</div>
	<?php echo $this->renderInputError( $name ); ?>
</fieldset>

<?php
	}

	public function renderInputCheckboxSet( $name, array $option, $value )
	{
		if( ! is_array($value) ) $value = [ $value ];
?>

<fieldset>
	<div class="pw-inline-list">
	<?php foreach( $option as $k => $label ) : ?>
		<div>
			<label>
				<?php $checked = in_array($k, $value) ? true : false; ?>
				<?php echo $this->renderInputCheckbox( $name . '[]', $k, $checked, [] ); ?><span><?php echo $label; ?></span>
			</label>
		</div>
	<?php endforeach; ?>
	</div>
	<?php echo $this->renderInputError( $name ); ?>
</fieldset>

<?php
	}

// ----------------------------------------------------
// Pager
// ----------------------------------------------------
	public static function pager( array $ret = [] )
	{
		$ret += [
			'total' => 0,
			'limit' => 10,
			'offset' => 0,

			'limits' => [ 10, 25, 100, 0 ],

			'offsetParam' => 'offset',
			'limitParam' => 'limit',

			'textFirst'		=> '__First Page__',
			'textNext'		=> '__Next Page__',
			'textPrev'		=> '__Previous Page__',
			'textLast'		=> '__Last Page__',
			'textPerPage'	=> '__Per Page__',

			'@render'	=> __CLASS__ . '::renderPager',
		];
		return $ret;
	}

	public static function renderPager( $total, $limit, $offset, array $attr )
	{
		$attr = $attr + [
			'offsetParam'	=> 'offset',
			'limitParam'	=> 'limit',
			'textFirst'		=> '__First Page__',
			'textNext'		=> '__Next Page__',
			'textPrev'		=> '__Previous Page__',
			'textLast'		=> '__Last Page__',
			'textPerPage'	=> '__Per Page__',
			'limits'	=> [ 10, 25, 100, 0 ],
		];

		extract( $attr );

		if( ! $total ) return;
		$minLimit = current( $limits );

		if( $total <= $minLimit ) return;
		// if( $total <= $limit ) return;

		$displayed1 = $total ? $offset + 1 : 0;
		$displayed2 = $limit ? min( $offset + $limit, $total ) : $total;

		$prevOffset = null;
		if( $limit && $offset ){
			$prevOffset = max( $offset - $limit, 0 );
		}

		$firstOffset = null;
		if( $offset && $prevOffset ){
			$firstOffset = 0;
		}

		$nextOffset = null;
		if( $limit && ($total > $displayed2) ){
			$nextOffset = $offset + $limit;
		}

		$lastOffset = null;
		if( $limit && ($total > $limit) ){
			$lastOffset = ( ceil($total / $limit) - 1 ) * $limit;
			if( $lastOffset == $nextOffset ) $lastOffset = null;
			if( $lastOffset == $offset ) $lastOffset = null;
		}

		$links = [];

		if( null !== $firstOffset ){
			$links['first'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $firstOffset,
				// 'label' => '<i>&laquo;</i>' . $textFirst,
				'label' => '&laquo;',
				'title' => $textFirst,
			];
		}

		if( null !== $prevOffset ){
			$links['prev'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $prevOffset,
				// 'label' => '<i>&lsaquo;</i>' . $textPrev,
				'label' => '&lsaquo;',
				'title' => $textPrev,
			];
		}

		if( (! $limit) OR $links OR $nextOffset OR $lastOffset ){
			$current = ( $limit > 1 ) ? $displayed1 . ' - ' . $displayed2 :  $displayed1;
			$current .= ' / ' . $total;
			$links['current'] = $current;
		}

		if( $nextOffset ){
			$links['next'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $nextOffset,
				// 'label' => $textNext . '<i>&rsaquo;</i>',
				'label' => '&rsaquo;',
				'title' => $textNext,
			];
		}

		if( $lastOffset ){
			$links['last'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $lastOffset,
				// 'label' => $textLast . '<i>&raquo;</i>',
				'label' => '&raquo;',
				'title' => $textLast,
			];
		}

		$links['perpage'] = '<small>' . $textPerPage . '</small>';

		foreach( $limits as $k ){
			if( $k && ($total <= $k) ) continue;

			$v = $k ? $k : '__All__';

			if( $k == $limit ){
				$links[ 'perpage-' . $k ] = $v;
			}
			else {
				$links[ 'perpage-' . $k ] = [
					'href' => 'URI:.?' . $limitParam . '=' . $k . '&' . $offsetParam . '=null',
					'label' => $v,
				];
			}
		}

		if( ! $links ) return;
?>

<nav role="toolbar">
<?php foreach( $links as $link ) : ?>
	<?php if( is_array($link) ) : ?>
		<a href="<?php echo $link['href']; ?>"<?php if( isset($link['title']) ) : ?> title="<?php echo $link['title']; ?>"<?php endif; ?>><?php echo $link['label']; ?></a>
	<?php else : ?>
		<span><?php echo $link; ?></span>
	<?php endif; ?>
<?php endforeach; ?>
</nav>

<?php
	}

	public static function renderPagerOld( array $m )
	{
		extract( $m );

		if( ! $total ) return;
		if( $total <= $limit ) return;

		$displayed1 = $total ? $offset + 1 : 0;
		$displayed2 = $limit ? min( $offset + $limit, $total ) : $total;

		$prevOffset = null;
		if( $limit && $offset ){
			$prevOffset = max( $offset - $limit, 0 );
		}

		$firstOffset = null;
		if( $offset && $prevOffset ){
			$firstOffset = 0;
		}

		$nextOffset = null;
		if( $limit && ($total > $displayed2) ){
			$nextOffset = $offset + $limit;
		}

		$lastOffset = null;
		if( $limit && ($total > $limit) ){
			$lastOffset = ( ceil($total / $limit) - 1 ) * $limit;
			if( $lastOffset == $nextOffset ) $lastOffset = null;
			if( $lastOffset == $offset ) $lastOffset = null;
		}

		$links = [];

		if( null !== $firstOffset ){
			$links['first'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $firstOffset,
				'label' => '<i>&laquo;</i>' . $textFirst,
			];
		}

		if( null !== $prevOffset ){
			$links['prev'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $prevOffset,
				'label' => '<i>&lsaquo;</i>' . $textPrev,
			];
		}

		if( (! $limit) OR $links OR $nextOffset OR $lastOffset ){
			$current = ( $limit > 1 ) ? $displayed1 . ' - ' . $displayed2 :  $displayed1;
			$current .= ' / ' . $total;
			$links['current'] = $current;
		}

		if( $nextOffset ){
			$links['next'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $nextOffset,
				'label' => $textNext . '<i>&rsaquo;</i>',
			];
		}

		if( $lastOffset ){
			$links['last'] = [
				'href' => 'URI:.?' . $offsetParam . '=' . $lastOffset,
				'label' => $textLast . '<i>&raquo;</i>',
			];
		}

		$links['perpage'] = '<small>' . $textPerPage . '</small>';

		$option = $m['limits'];
		foreach( $option as $k ){
			if( $k && ($total <= $k) ) continue;

			$v = $k ? $k : '__All__';

			if( $k == $limit ){
				$links[ 'perpage-' . $k ] = $v;
			}
			else {
				$links[ 'perpage-' . $k ] = [
					'href' => 'URI:.?' . $limitParam . '=' . $k . '&' . $offsetParam . '=null',
					'label' => $v,
				];
			}
		}

		if( ! $links ) return;
?>
<nav role="menu">
<?php foreach( $links as $link ) : ?>
	<?php if( is_array($link) ) : ?>
		<a href="<?php echo $link['href']; ?>"><?php echo $link['label']; ?></a>
	<?php else : ?>
		<span><?php echo $link; ?></span>
	<?php endif; ?>
<?php endforeach; ?>
</nav>
<?php
	}

	public static function sortAction( array $actions )
	{
		$items = [];

		foreach( $actions as $k => $v ){
			$order = $v[0];
			$k2 = $order . '-' . $k;
			$v[0] = $k;
			$items[ $k2 ] = $v;
		}

		ksort( $items );

		$ret = [];
		foreach( $items as $item ) $ret[ $item[0] ] = $item[1];
		return $ret;
	}

	public static function renderAction( array $actions )
	{
		$actions = static::sortAction( $actions );
?>

<select name="action">
	<option value="">- __Action__ -</option>
	<?php foreach( $actions as $k => $label ) : ?>
		<option value="<?php echo esc_attr($k); ?>"><?php echo $label; ?></option>
	<?php endforeach; ?>
</select>

<?php
	}

	public static function sortMenu( array $links )
	{
		$ret = [];

		foreach( $links as $k => $v ){
			$order = ( 3 == count($v) ) ? array_shift( $v ) : 5;
			$k2 = $order . '-' . $k;
			$ret[ $k2 ] = $v;
		}
		ksort( $ret );

		return $ret;
	}

	public static function renderMenu( array $links )
	{
		$items = static::sortMenu( $links );
?>

<?php foreach( $items as $k => $v ) : ?>
	<?php if( 3 == count($v) ) : ?>
		<form method="post" action="URI:<?php echo $v[0]; ?>"><button type="submit"><?php echo $v[2]; ?></button></form>
	<?php else : ?>
		<?php
		$to = $v[0];
		if( is_array($to) ){
			list( $to, $param ) = $to;
			if( $param ){
				$to = $to . '?' . http_build_query( $param );
			}
		}
		
		$label = $v[1];
		?>
		<a href="URI:<?php echo $to; ?>"><?php echo $label; ?></a>
	<?php endif; ?>
<?php endforeach; ?>

<?php
	}

	public static function renderBreadcrumb( array $links )
	{
		$items = static::sortMenu( $links );
?>

<nav role="navigation">
<?php foreach( $items as $v ) : ?>
	<a href="URI:<?php echo esc_attr($v[0]); ?>"><i>&raquo;</i><?php echo esc_html($v[1]); ?></a>
<?php endforeach; ?>
</nav>

<?php
	}

	public static function renderMenubar( array $links, $currentSlug )
	{
		$items = static::sortMenu( $links );
?>

<nav role="menubar">
<?php foreach( $items as $k => $v ) : ?>
	<?php
	$isCurrent = ( $v[0] == substr($currentSlug, 0, strlen($v[0])) ) ? true : false;
	?>
	<a<?php if( $isCurrent ) : ?> aria-selected="true"<?php endif; ?> role="tab" href="URI:<?php echo $v[0]; ?>"><?php echo $v[1]; ?></a>
<?php endforeach; ?>
</nav>

<?php
	}

	public static function downloadFile( $file, $shortFile = null )
	{
		if( ob_get_contents() ){
			ob_end_clean();
		}

		if( null === $shortFile ){
			$shortFile = basename( $file );
		}

		$fileSize = filesize( $file );

		header("Type: application/force-download");
		header("Content-Type: application/force-download");
		header("Content-Length: $fileSize");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=\"$shortFile\"");
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Connection: close");
		readfile( $file );
		exit;
	}

	public static function downloadData( $filename, $data )
	{
	// Try to determine if the filename includes a file extension.
	// We need it in order to set the MIME type
		if (FALSE === strpos($filename, '.')){
			return FALSE;
		}

	// Grab the file extension
		$x = explode('.', $filename);
		$extension = end($x);

		// Load the mime types
		$mimes = array();

		// Set a default mime if we can't find it
		if ( ! isset($mimes[$extension])){
			$mime = 'application/octet-stream';
		}
		else {
			$mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
		}

	// Generate the server headers
		if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE){
			header('Content-Type: "'.$mime.'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header("Content-Transfer-Encoding: binary");
			header('Pragma: public');
			header("Content-Length: ".strlen($data));
		}
		else {
			header('Content-Type: "'.$mime.'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			header("Content-Length: ".strlen($data));
		}

		exit( $data );
	}

	public static function adjustColorBrightness( $hex, $steps )
	{
	// Steps should be between -255 and 255. Negative = darker, positive = lighter
		$steps = max( -255, min(255, $steps) );

	// Normalize into a six character long hex string
		$hex = str_replace('#', '', $hex);
		if( strlen($hex) == 3 ){
			$hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
		}

		// Split into three parts: R, G and B
		$colorParts = str_split( $hex, 2 );
		$ret = '#';

		foreach( $colorParts as $color ){
			$color = hexdec( $color ); // Convert to decimal
			$color = max( 0, min(255,$color + $steps) ); // Adjust color
			$ret .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
		}

		return $ret;
	}
}

if( ! function_exists('_print_r') ){
function _print_r( $thing ){
	echo '<pre>';
	print_r( $thing );
	echo '</pre>';
}
}