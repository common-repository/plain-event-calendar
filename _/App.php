<?php
namespace Plainware;

use \Plainware\AppStaticCaller;
use \Plainware\Slug;
use \Plainware\Html;
use \Plainware\Layout;

abstract class App
{
	protected $_namespaces = [];
	public static $instance;
	public $self;

// configurable
	public $slug;
	public $slugParam = 'a';

	public $file;

	public $dirs = [];
	public $assetDirs = [];
	public $conf = [];
	public $filter = [];

// cache
	public $instances = [];
	public $chains = [];

	// request context
	public $x = [];

	public function __construct( $file, array $dirs, array $conf )
	{
		$class = static::class;

		self::$instance = $this;

		$this->self = $this->{ get_class($this) };
		// $this->self = new AppStaticCaller( $class, $this );

		$this->file = $file;
		$this->conf = $conf;

		$this->_namespaces = [];
		$ns = get_class( $this );
		$pos = strrpos( $ns, '\\' );
		while( false !== $pos ){
			$ns = substr( $ns, 0, $pos );
			$this->_namespaces[] = $ns;
			$pos = strrpos( $ns, '\\' );
		}

		$this->dirs = array_reverse( $dirs );

		foreach( $dirs as $dir ){
			$bootFile = $dir . DIRECTORY_SEPARATOR . '_.php';
			if( ! file_exists($bootFile) ) continue;

			$boot = require( $bootFile );
			if( is_callable($boot) ){
				$boot( $this );
			}
			else {
				foreach( $boot as $f ){
					$this->filter( $f[0], $f[1], isset($f[2]) ? $f[2] : 1 );
				}
			}
		}

		$this->applyFilters( static::class . '::' . __FUNCTION__, $this, [] );
		register_shutdown_function( [$this, 'shutdown'] );
	}

	public function filter( $hook, $func, $order )
	{
		if( ! isset($this->filter[$hook]) ) $this->filter[$hook] = [];
		if( ! isset($this->filter[$hook][$order]) ) $this->filter[$hook][$order] = [];
		$this->filter[$hook][$order][] = $func;

		if( isset($this->chains[$hook]) ){
			unset( $this->chains[$hook] );
		}

		return $this;
	}

	public function handle()
	{
		$this->applyFiltersBefore( static::class . '::' . __FUNCTION__, [] );

		$slug = $this->slug;
		$params = [];

		if( isset($_REQUEST[$this->slugParam]) ){
			$slug = sanitize_text_field( $_REQUEST[$this->slugParam] );
		}

		if( $this->ModelInstall ){
			$installed = $this->ModelInstall->get( 'install' );
			if( ! $installed ){
				$slug = 'install';
			}
		}

		if( ! $slug ) $slug = 'setting';
		$this->slug = $slug;

	// migration up
		if( $this->ModelInstall ){
			if( 'install' !== $slug ){
				$modules = $this->ModelInstall->modules();
				// _print_r( $modules );
				$this->ModelInstall->doUp( $modules );
			}
		}

	// page handler
		$pageArray = $this->findPage( $this->slug );
		if( ! $pageArray ){
			exit( "no handler for '$this->slug'" );
		}

		list( $shortSlug, $page, $params ) = $pageArray;
// _print_r( $params );

		$requestMethod = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : 'get';
		$requestMethod = strtoupper( $requestMethod );

	// context
		$x = [];

		$x['slug'] = $slug;
		$x['shortSlug'] = $shortSlug;

		$x += $params;

	// current user
		$currentUser = null;

		if( $this->ModelUser ){
			if( $this->ModelInstall && $installed ){
				$currentUser = $this->PageLogin->getCurrentUser();

			// override user?
				if( isset($x['*u']) ){
					if( $currentUser && $currentUser['is_admin'] ){
						$realUser = $currentUser;
						$id = $x['*u'];
						$currentUser = $this->ModelUser->findById( $id );
						$x[ '$realUser' ] = $realUser;
					}
				}
			}
		}

		if( defined('WPINC') ){
			$x[ '$currentUserId' ] = get_current_user_id();
		}
		$x[ '$currentUser' ] = $currentUser;

		$x = $this->self->x( $x );

	// can?
		$can = $this->can( $this->slug, $x );
		if( false === $can ){
			$x['redirect'] = $currentUser ? 'notallowed' : 'login';
		}

	// redirect?
		if( isset($x['redirect']) ){
			$this->self->redirect( $x['redirect'], $x );
			return;
		}

	// do GET
		$x2 = $this->{ $page }->get( $x );
		if( null !== $x2 ) $x = $x2;

	// redirect?
		if( isset($x['redirect']) ){
			$this->self->redirect( $x['redirect'], $x );
			return;
		}

	// post?
		if( 'POST' === $requestMethod ){
			$post = [];
			foreach( array_keys($_POST) as $k ){
				if( is_array($_POST[$k]) ){
					foreach( array_keys($_POST[$k]) as $k2 ){
						// $v = sanitize_text_field( $_POST[$k][$k2] );

						$nl = '--OMGKEEPNEWLINE--';
						$v = sanitize_text_field( str_replace("\n", $nl, $_POST[$k][$k2]) );
						$v = str_replace( $nl, "\n", $v );

						if( is_numeric($v) ){
							$v = floatval( $v );
						}
						$post[$k][$k2] = $v;
					}
				}
				else {
					$nl = '--OMGKEEPNEWLINE--';
					$v = sanitize_text_field( str_replace("\n", $nl, $_POST[$k]) );
					$v = str_replace( $nl, "\n", $v );

					if( is_numeric($v) ){
						$v = floatval( $v );
					}
					$post[$k] = $v;
				}
			} 

			if( ! isset($x['error']) ) $x['error'] = [];
			$x = $this->{ $page }->post( $x, $post );

			$this->Html->setFormValues( $post );
			$this->Html->setFormErrors( $x['error'] );
		}

	// redirect?
		if( isset($x['redirect']) ){
			$this->self->redirect( $x['redirect'], $x );
			return;
		}

	// assets?
		$topAsset = method_exists( '' . $this->Page, 'asset' ) ? $this->Page->asset( $x ) : [];
		$asset = method_exists( $page, 'asset' ) ? $this->{ $page }->asset( $x ) : [];
		$asset = array_merge( $topAsset, $asset );
		$x[ 'asset' ] = $asset;

	// save context for render
		$this->x = $x;

		$this->applyFilters( static::class . '::' . __FUNCTION__, $x, [] );
	}

	public function x( array $x )
	{
		return $x;
	}

	public function findPage( $slug )
	{
		$ret = false;

		if( ! strlen($slug) ) return;

	// with placeholders
		list( $page, $params ) = $this->Slug->parse( $slug, true, false );

		$ret = $page;
		$ret = str_replace( '{', '', $ret );
		$ret = str_replace( '}', '', $ret );

		$shortSlug = $ret;

		$parts = explode( '-', $ret );

		$countParts = count( $parts );
		for( $ii = 0; $ii < $countParts; $ii++ ){
			$parts[ $ii ] = ucfirst( $parts[ $ii ] );
		}

		$ret = join( '', $parts );
		$ret = 'Page' . $ret;

		$controllerClass = $this->className( $ret );
		$ret = [ $shortSlug, $controllerClass, $params ];

		return $ret;
	}

// wordpress-like callbacks
	public function applyFilters( $hook, $ret, array $args )
	{
		$chain = $this->findCallableChain( $hook );
		if( ! $chain ) return $ret;

		array_unshift( $args, $ret );

		reset( $chain );
		foreach( $chain as $order => $chain2 ){
			if( $order <= 0 ) continue;
			foreach( $chain2 as $f ){
				$thisRet = $this->call( $f, $args );

				if( null !== $thisRet ){
					$ret = $thisRet;
					$args[0] = $ret;
				}
			}
		}

		return $ret;
	}

	public function applyFiltersBefore( $hook, array $args )
	{
		$chain = $this->findCallableChain( $hook );
		if( ! $chain ) return $args;

		$args[] = $this;
		reset( $chain );
		foreach( $chain as $order => $chain2 ){
			if( $order >= 0 ) break;

			foreach( $chain2 as $f ){
				$thisRet = $this->call( $f, $args );

				if( null === $thisRet ) continue;

				if( false === $thisRet ){
					// echo "ESCAPE EXECUTION '$func'<br>";
					return false;
				}

				$args = $thisRet;
				$args[] = $this;
			}
		}

		array_pop( $args );
		return $args;
	}

// return fully namespaced class name
	public function className( $name )
	{
	// already namespaced
		if( false !== strpos($name, '\\') ){
			return $name;
		}

		$ret = null;

		reset( $this->_namespaces );
		foreach( $this->_namespaces as $ns ){
			$cn = $ns . '\\' . $name;
			if( class_exists($cn) ){
				$ret = $cn;
				break;
			}
		}

		return $ret;
	}

	public function findCallableChain( $func )
	{
		if( isset($this->chains[$func]) ) return $this->chains[$func];

		$ret = [];

		$hooks = [];
		$hooks[] = $func;

	// if it's a class then check parent classes
		$pos = strpos( $func, '::' );
		if( false !== $pos ){
			list( $funcClassName, $funcMethodName ) = explode( '::', $func );
			$parents = class_exists($funcClassName) ? class_parents( $funcClassName, false ) : [];
			foreach( $parents as $p ){
				array_unshift( $hooks, $p . '::' . $funcMethodName );
			}
		}

		foreach( $hooks as $h ){
			if( ! isset($this->filter[$h]) ) continue;

			foreach( array_keys($this->filter[$h]) as $k ){
				$ret[ $k ] = isset( $ret[$k] ) ? array_merge( $ret[$k], $this->filter[$h][$k] ) : $this->filter[$h][$k];
			}
		}

	// replacement defined for main func? then leave the latest addition otherwise the func itself
		if( isset($ret[0]) ){
			// echo "FUNC! '$func'<br>";
			// exit;
// _print_r( $ret[0] );

			$ret[0] = end( $ret[0] );
		}
		else {
			// if static method
			$method = new \ReflectionMethod( $func );
			if( $method->isStatic() ){
				$main = $func;
			}
			else {
				if( ! isset($this->instances[$funcClassName]) ){
					if( get_class($this) == $funcClassName ){
						$this->instances[$funcClassName] = $this;
					}
					else {
						$this->instances[$funcClassName] = new $funcClassName( $this );
					}
				}
				$main = [ $this->instances[$funcClassName], $funcMethodName ];
			}

			$ret[0] = $main;
		}

		ksort( $ret );
		$this->chains[$func] = $ret;

		return $ret;
	}

	public function call( $func, array $args = [] )
	{
		$thisArgs = $args;
		$thisArgs[] = $this;

		if( ! is_string($func) ){
			$ret = call_user_func_array( $func, $thisArgs );
			return $ret;
		}

		$isRender = false;
		list( $funcClassName, $funcName ) = explode( '::', $func );

		$renderFuncs = [ 'render' ];
		foreach( $renderFuncs as $rf ){
			if( $rf === substr($funcName, 0, strlen($rf)) ){
				$isRender = true;
				break;
			}
		}

		$chain = $this->findCallableChain( $func );

// if( '::redirect' == substr($func, -strlen('::redirect')) ){
	// echo "CHAIN FOR '$func'<br>";
	// _print_r( $chain );
// }

	// before - may alter args or stop execution
		reset( $chain );
		foreach( $chain as $order => $chain2 ){
			if( $order >= 0 ) break;

			foreach( $chain2 as $f ){
				$thisRet = $this->call( $f, $thisArgs );

				if( null === $thisRet ) continue;

				if( false === $thisRet ){
					// echo "ESCAPE EXECUTION '$func'<br>";
					return;
				}

				$thisArgs = $thisRet;
				$thisArgs[] = $this;
			}
		}

	// main
		if( $isRender ){
			ob_start();
		}

		$ret = call_user_func_array( $chain[0], $thisArgs );

		if( $isRender ){
			$echoRet = ob_get_clean();
			$echoRet = trim( $echoRet );
			if( strlen($echoRet) ) $ret = $echoRet;
		}

	// after
		array_unshift( $thisArgs, $ret );

		reset( $chain );
		foreach( $chain as $order => $chain2 ){
			if( $order <= 0 ) continue;
			foreach( $chain2 as $f ){
				$thisRet = $this->call( $f, $thisArgs );

				if( null !== $thisRet ){
					$ret = $thisRet;
					$thisArgs[0] = $ret;
				}
			}
		}

		return $ret;
	}

	public function filterLinks( $ret )
	{
		$x = $this->x;
		$currentSlug = $this->slug;

	// start with * - global param
		$globalSlugParams = [];
		foreach( $x as $k => $v ){
			if( '*' === substr($k, 0, 1) ){
				$globalSlugParams[ $k ] = $x[ $k ];
			}
		}

	// check ahrefs within <nav></nav> areas
		$ahrefs = [];
		$pos1Nav = strpos( $ret, '<nav', 0 );
		while( false !== $pos1Nav ){
			$pos2Nav = strpos( $ret, '</nav>', $pos1Nav ) + strlen('</nav>');

			$pos1Uri = stripos( $ret, 'URI:', $pos1Nav );
			while( (false !== $pos1Uri) && ($pos1Uri < $pos2Nav) ){
				$pos1Ahref1 = strrpos( substr($ret, 0, $pos1Uri), '<a' );
				$pos1Ahref2 = strrpos( substr($ret, 0, $pos1Uri), '<form' );
				if( (false !== $pos1Ahref1) && (false !== $pos1Ahref2) ){
					$pos1Ahref = max( $pos1Ahref1, $pos1Ahref2 );
				}
				else {
					$pos1Ahref = ( false !== $pos1Ahref1 ) ? $pos1Ahref1 : $pos1Ahref2;
				}

				if( $pos1Ahref == $pos1Ahref1 ){
					$endTag = '</a>';
				}
				else {
					$endTag = '</form>';
				}
				$pos2Ahref = strpos( $ret, $endTag, $pos1Ahref );

				$ahref = substr( $ret, $pos1Ahref, $pos2Ahref - $pos1Ahref + strlen($endTag) );

				$pos1Slug = $pos1Uri + strlen('URI:');
				$quote = substr( $ret, $pos1Uri - 1, 1 );
				$pos2Slug = strpos( $ret, $quote, $pos1Slug + 1 );
				$ahrefSlug = substr( $ret, $pos1Slug, $pos2Slug - $pos1Slug );

				$ahrefs[] = [ $ahrefSlug, $pos1Ahref, $pos2Ahref + strlen($endTag) ];

				$pos1Uri = stripos( $ret, 'URI:', $pos2Ahref + 1 );
			}

			$offsetNav = $pos1Nav + 1;
			$pos1Nav = strpos( $ret, '<nav', $pos1Nav + 1 );
		}

// _print_r( $ahrefs );
// exit;

	// ok now check these ahref slugs. if ahrefSlug not allowed then remove full <a href string
		$removeChunks = [];
		foreach( $ahrefs as $ahref ){
			list( $ahrefSlug, $chunkStart, $chunkEnd ) = $ahref;

			$thisX = $x;

			$params = [];
			$params += $globalSlugParams;
			$testSlug = $this->Slug->make( $ahrefSlug, $currentSlug, $params );

			$thisCan = $this->can( $testSlug, $thisX );
			if( false !== $thisCan ) continue;

			$removeChunks[] = [ $chunkStart, $chunkEnd ];
		}

		$removeChunks = array_reverse( $removeChunks );
		foreach( $removeChunks as $chunk ){
			list( $chunkStart, $chunkEnd ) = $chunk;

// echo "REMOVE '" . esc_html( substr($ret, $chunkStart, $chunkEnd - $chunkStart) ) . "'";
// exit;

			$ret = substr( $ret, 0, $chunkStart ) . substr( $ret, $chunkEnd );
		}

		return $ret;
	}

	public function parseLinks( $ret )
	{
		$app = $this;
		$x = $this->x;
		$currentSlug = $this->slug;

	// replace URIs - replace "URI:smth" to real urls
		$strings = [];

	// start with * - global param
		$globalSlugParams = [];
		foreach( $x as $k => $v ){
			if( '*' === substr($k, 0, 1) ){
				$globalSlugParams[ $k ] = $x[ $k ];
			}
		}

		$start = 'URI:';
		$startLen = strlen( $start );
		$end = '"';
		$endLen = strlen( $end );

		$pos1 = stripos( $ret, $start );
		while( false !== $pos1 ){
			$pos2 = strpos( $ret, $end, $pos1 + $startLen + 1 );
			if( false === $pos2 ) break;

			$pos2 = $pos2 + $endLen;
			$fromString = substr( $ret, $pos1 + $startLen, $pos2 - $endLen - $pos1 - $startLen );

			if( isset($uris[$fromString]) ){
				$toString = $uris[$fromString];
			}
			else {
				$toString = $fromString;

				$params = [];
				$params += $globalSlugParams;

				$toSlug = $app->Slug->make( $fromString, $currentSlug, $params );
				$toString = $this->self->linkTo( $toSlug );

			// update: add data-slug="slug"
				// $toString .= '" data-slug="' . $toSlug;

				$uris[ $fromString ] = $toString;
			}

			$ret = substr( $ret, 0, $pos1 ) . $toString . substr( $ret, $pos2 - 1 );
			$pos2 = $pos1 + strlen( $toString ) + 1;

			$pos1 = stripos( $ret, $start, $pos2 + 1 );
		}

		return $ret;
	}

	public function redirect( $to, array $x )
	{
		$slug = $this->slug;

		$globalSlugParams = [];
		foreach( $x as $k => $v ){
			if( '*' === substr($k, 0, 1) ){
				$globalSlugParams[ $k ] = $x[ $k ];
			}
		}

		$param = [];
		if( is_array($to) ){
			list( $to, $param ) = $to;
		}

		if( null === $to ) $to = $slug;

		$param += $globalSlugParams;
		$to = $this->Slug->make( $to, $slug, $param );

// echo "REDIRECT TO = '$to'<br>";

	// redirect to a page with title() only
		$stackParents = $this->Slug->findStack( $to );
		do {
			$toPageArray = $this->findPage( $to );
			if( $toPageArray ){
				list( $shortSlug, $toPage, $thisX ) = $toPageArray;

				$thisX += $param;
				$thisX += $x;

				$toTitle = $toPage ? $this->{ $toPage }->title( $thisX ) : null;
				if( strlen($toTitle) ){
					break;
				}
			}
			$to = array_shift( $stackParents );
		} while( strlen($to) );

		$to = $this->self->linkTo( $to );
		$this->applyFilters( static::class . '::' . __FUNCTION__, null, [$to, $x] );

		header( 'Location: ' . $to );
		exit;
	}

	public function can( $slug, array $x )
	{
		$ret = true;

		$parents = $this->Slug->findParents( $slug, true );
		foreach( $parents as $testSlug ){
			$testPageArray = $this->findPage( $testSlug );
			if( ! $testPageArray ) continue;

			list( $shortSlug, $testPage, $textX ) = $testPageArray;
			if( ! method_exists($testPage, 'can') ) continue;

			$testX = $textX + $x;
			$can = $this->{ $testPage }->can( $testX );

			if( false === $can ){
				$ret = false;
				break;
			}
		}

		return $ret;
	}

	public static function versionStringFromFile( $fileName )
	{
		$ret = null;
		$fileContents = file_get_contents( $fileName );
		if( preg_match('/version:[\s\t]+?([0-9.]+)/i', $fileContents, $v) ){
			$ret = $v[1];
		}
		return $ret;
	}

	public static function versionNumFromString( $verString )
	{
		$ret = explode( '.', $verString );
		if( strlen($ret[2]) < 2 ) $ret[2] = '0' . $ret[2];
		$ret = join( '', $ret );
		$ret = (int) $ret;
		return $ret;
	}

	// public function isAjax()
	// {
		// $ret = ( isset( $_GET['pwajax'] ) && $_GET['pwajax'] ) ? true : false;
		// return $ret;
	// }

	public function isAjax()
	{
		$ret = false;
		if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ('XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH']) ){
			$ret = true;
		}
		return $ret;
	}

	public function shutdown()
	{
		$this->applyFilters( static::class . '::' . __FUNCTION__, $this, [] );
		// echo 'SHUTDOWN ' . static::class . '::' . __FUNCTION__ . '<br>';
	}

	public function confFileName()
	{
		$ret = dirname( $this->file ) . DIRECTORY_SEPARATOR . 'config.php';
		return $ret;
	}

	public function version()
	{
		$ret = static::versionStringFromFile( $this->file );
		return $ret;
	}

	public function translate( $ret )
	{
		$parts = [];

		$start = '__';
		$startLen = strlen( $start );
		$end = '__';
		$endLen = strlen( $end );

		$pos1 = strpos( $ret, $start );
		while( false !== $pos1 ){
			$pos2 = strpos( $ret, $end, $pos1 + $startLen + 1 );
			if( false === $pos2 ) break;

			$pos2 = $pos2 + $endLen;
			$part = substr( $ret, $pos1, $pos2 - $pos1 );
			$fromString = substr( $part, $startLen, -$endLen );
			$parts[] = [ $pos1, $pos2, $fromString ];

			if( $pos2 >= strlen($ret) ){
				break;
			}
			$pos1 = strpos( $ret, $start, $pos2 + 1 );
		}

		$strings = [];
		foreach( array_reverse($parts) as $part ){
			list( $pos1, $pos2, $fromString ) = $part;

			if( isset($strings[$fromString]) ){
				$toString = $strings[$fromString];
			}
			else {
				$toString = $fromString;
			// $toString = __( $fromString, 'plaintracker' );
				$strings[$fromString] = $toString;
			}

			$ret = substr( $ret, 0, $pos1 ) . $toString . substr( $ret, $pos2 );
		}

		return $ret;
	}

	public function render()
	{
		// $this->applyFiltersBefore( static::class . '::' . __FUNCTION__, [] );

		$app = $this;
		$x = $this->x;
		$slug = $this->slug;

		$pageArray = $this->findPage( $slug );
		list( $shortSlug, $page, $params ) = $pageArray;

		$title = method_exists( $page, 'title' ) ? $app->{ $page }->title( $x ) : null;
		$toolbar = method_exists( $page, 'nav' ) ? $app->{ $page }->nav( $x ) : null;
		$content = $app->{ $page }->render( $x );

	// pagestack aka breadcrumbs
		$pagestack = [];
		$stack = $app->Slug->findStack( $slug );

		foreach( $stack as $stackSlug ){
			$stackPageArray = $app->findPage( $stackSlug );
			if( ! $stackPageArray ) continue;

			list( $shortSlug, $stackPage, $thisX )= $stackPageArray;
			$thisX = $thisX + $x;

			$stackTitle = method_exists( $stackPage, 'title' ) ? $app->{ $stackPage }->title( $thisX ) : '';
			if( ! strlen($stackTitle) ) continue;
			$pagestack[] = [ $stackSlug, $stackTitle ];
		}

		$x[ 'menubar' ] = $this->Page->nav( $x );
		$x[ 'title' ] = $title;
		$x[ 'toolbar' ] = $toolbar;
		$x[ 'breadcrumb' ] = $pagestack;

		$ret = $this->Layout->render( $x );

		$contentTag = '[[content]]';
		$pos = strpos( $ret, $contentTag );
		$ret = substr( $ret, 0, $pos ) . $content . substr( $ret, $pos + strlen($contentTag) );

	// replace tabs as we have them a lot
		$ret = str_replace( "\t", '', $ret );

		$ret = $this->self->filterLinks( $ret );
		$ret = $this->self->parseLinks( $ret );
		$ret = $this->self->translate( $ret );

		// $ret = $this->applyFilters( static::class . '::' . __FUNCTION__, $ret, [] );

		return $ret;
	}

	public function view()
	{
		return $this->self->render();
	}

	public function versionUri( $mode )
	{
		$ret = 'https://www.shiftexec.com/';
		$glue = ( false === strpos($ret, '?') ) ? '?' : '&'; 
		$ret = $ret . $glue . 'ver=' . $mode;
		return $ret;
	}

	public function linkTo( $slug )
	{
// echo __METHOD__ . "<br>\n";
		$ret = '?' . $this->slugParam . '=' . $slug;
		return $ret;
	}

	public function assetUri( $file )
	{
		$ret = null;

		$fullFile = $this->findAsset( $file );
		if( $fullFile ){
			$ret = $this->self->assetFileUri( $fullFile );
		}

		$ret = str_replace( DIRECTORY_SEPARATOR, '/', $ret );

		return $ret;
	}

	public function assetFileUri( $fullFile )
	{
		$ret = null;

		$rootDir = dirname( $this->file );
		if( $rootDir == substr($fullFile, 0, strlen($rootDir)) ){
			$ret = substr( $fullFile, strlen($rootDir) + strlen(DIRECTORY_SEPARATOR) );
		}

		return $ret;
	}

	public function findAsset( $file )
	{
		$ret = null;

	// find real file
		$dirs = $this->dirs;
		array_unshift( $dirs, __DIR__ );

		reset( $dirs );
		foreach( $dirs as $d ){
			$f = $d . DIRECTORY_SEPARATOR . 'asset' . DIRECTORY_SEPARATOR . $file;
			if( file_exists($f) ){
				$ret = $f;
				break;
			}
		}

		return $ret;
	}

// trying to access a class
	public function __get( $name )
	{
		$ret = null;

		$className = $this->className( $name );
		if( $className ){
			$ret = new AppStaticCaller( $className, $this );
		}

		if( ! strlen($name) ) return;

		$this->{$name} = $ret;
		return $this->{$name};
	}

// call $name::construct( ..$args ) 
	public function __call( $name, $args )
	{
		$className = $this->className( $name );
		return $this->call( $className . '::' . 'construct', $args );
	}
}

if( ! function_exists('_print_r') ){
// function _print_r( $thing ){
	// $wpAdmin = defined('WPINC') && is_admin();

	// if( $wpAdmin ){
		// echo '<div style="margin-left: 15em;">';
	// }

	// echo '<pre>';
	// print_r( $thing );
	// echo '</pre>';

	// if( $wpAdmin ){
		// echo '</div>';
	// }
// }
}

if( ! function_exists('\sanitize_text_field') ){
function sanitize_text_field( $ret ){
	return $ret;
}
}

if( ! function_exists('\esc_html') ){
function esc_html( $ret ){
	$ret = htmlspecialchars( $ret );
	return $ret;
}
}

if( ! function_exists('\esc_attr') ){
function esc_attr( $ret ){
	return $ret;
}
}

if( ! function_exists('\esc_textarea') ){
function esc_textarea( $ret ){
	return $ret;
}
}