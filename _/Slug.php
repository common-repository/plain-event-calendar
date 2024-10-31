<?php
namespace Plainware;

class Slug
{
	public static $stackSeparator = '--';
	public static $pageSeparator = '-';
	public static $paramSeparator = '_';

	public static function make( $page, $currentSlug, array $params )
	{
		$pageIn = $page;
// echo "PAGE = '$page', CURRENT = '$currentSlug'<br/>";

		$stackSep = static::$stackSeparator;
		$paramSep = static::$paramSeparator;

		list( $currentPage, $currentParams ) = static::parse( $currentSlug, false, false );

		$pos = strpos( $page, '?' );
		if( false !== $pos ){
			$paramString = substr( $page, $pos + 1 );
			parse_str( $paramString, $pageParams );
			$page = substr( $page, 0, $pos );
			$params = array_merge( $params, $pageParams );
		}

		if( '.' === $page ){
		// current slug without params
			$pos = strrpos( $currentSlug, $stackSep );
			if( false !== $pos ){
				$page = substr( $currentSlug, 0, $pos ) . $stackSep . $currentPage;
			}
			else {
				$page = $currentPage;
			}
		}
		else {
			$currentParams = [];
		}

		$replace = [];

	// parents
		$realSlugArray = explode( $stackSep, $currentSlug );
		$count = count( $realSlugArray );
		for( $ii = 0; $ii < $count; $ii++ ){
			$from = join( $stackSep, array_fill(0, $count - $ii, '..') );
			$to = join( $stackSep, array_slice($realSlugArray, 0, $ii) );
			$replace[ $from ] = $to;
		}

		$replace[ '.' ] = $currentSlug . $stackSep;

		foreach( $replace as $from => $to ){
			$page = str_replace( $from, $to, $page );
		}

		$ret = $page;

		if( $params or $currentParams ){
			$newParams = $currentParams;
			foreach( $params as $k => $v ){
				if( (null === $v) OR ('null' === $v) ){
					unset( $newParams[$k] );
					continue;
				}
				$newParams[ $k ] = $v;
			}

			if( $newParams ){
				list( $retPage, $retParams ) = static::parse( $ret, true, true );
				$changeRetParams = array_intersect_key( $newParams, $retParams );

				if( $changeRetParams ){
					// echo "WAS RETPARAMS";
					// _print_r( $retParams );

					foreach( $changeRetParams as $k => $v ){
						if( (null === $v) OR ('null' === $v) ){
							unset( $retParams[$k] );
							continue;
						}
						$retParams[ $k ] = $v;
						unset( $newParams[$k] );
					}

					// echo "NOW RETPARAMS";
					// _print_r( $retParams );
				
					$paramString = static::buildParamString( $retParams );
					$ret = $retPage . $paramString;
				}
			}

			if( $newParams ){
				$paramString = static::buildParamString( $newParams );
				$ret .= $paramString;
			}
		}

// echo "RET = '$ret'<br/><br/>";

// if( isset($params['customer']) ){
// _print_r( $params );
// _print_r( $replace );
// echo "RET = '$ret'<br/><br/>";
// _print_r( $currentParams );
	// echo "AXA";
	// exit;
// }

		return $ret;
	}

	public static function buildParamString( array $params )
	{
		$ret = '';
		$paramSep = static::$paramSeparator;

		foreach( $params as $k => $v ){
			if( is_array($v) ){
				foreach( $v as $v2 ) $ret .= $paramSep . $k . $paramSep . $v2;
			}
			else {
				$ret .= $paramSep . $k . $paramSep . $v;
			}
		}

		return $ret;
	}

// returns [ page, params ]
	public static function parse( $slug, $withPlaceholders, $withParents )
	{
		$page = null;
		$params = [];
		$ret = [ $page, $params ];

	// interested only in the last part of the stack
		$slugParentString = $slug;
		$stackSep = static::$stackSeparator;
		$pos = strrpos( $slug, $stackSep );
		if( false !== $pos ){
			$slugParentString = substr( $slug, 0, $pos + strlen($stackSep) );
			// echo "SLUG PS = '$slugParentString'<br>";
			// echo "POS = '$pos'<br>";
			// echo "WAS = '$slug'<br>";
			$slug = substr( $slug, $pos + strlen($stackSep) );
			// echo "NOW = '$slug'<br>";
		}

	// have params?
		$paramSep = static::$paramSeparator;
		$pos = strpos( $slug, $paramSep );

	// no params
		if( false === $pos ){
			$page = $slug;
		}
	// with params
		else {
			$page = substr( $slug, 0, $pos );
			$paramString = substr( $slug, $pos + 1 );
			$paramArray = explode( $paramSep, $paramString );

			$count = count( $paramArray );
			for( $i = 0; $i < $count; $i+=2 ){
				$k = $paramArray[$i];
				$v = $paramArray[ $i + 1 ];

				if( array_key_exists($k, $params) ){
					if( ! is_array($params[$k]) ) $params[$k] = [ $params[$k] ];
					$params[$k][] = $v;
				}
				else {
					$params[ $k ] = $v;
				}
			}
		}

		if( $withPlaceholders ){
		// replace to {id} if seems like id
			$pageSep = static::$pageSeparator;
			$pageArray = explode( $pageSep, $page );
			for( $ii = 0; $ii < count($pageArray); $ii++ ){
				if( ! is_numeric($pageArray[$ii]) ) continue;
				$params[] = $pageArray[$ii];
				$pageArray[$ii] = '{id}';
			}

			$page = join( $pageSep, $pageArray );
		}

		if( $withParents ){
			$page = $slugParentString . $page;
		}

		$ret = [ $page, $params ];
		return $ret;
	}

// return [ 'page1-page11-page22', 'page1-page11', 'page1' ];
	public static function findParents( $slug, $includeMyself )
	{
		$ret = [];

		list( $page, $params ) = static::parse( $slug, false, false );

		$pageSep = static::$pageSeparator;
		$slugArray = explode( $pageSep, $page );

		$endIndex = count( $slugArray );
		if( $includeMyself ) $endIndex += 1;

		for( $ii = 1; $ii < $endIndex; $ii++ ){
			$thisSlug = join( $pageSep, array_slice($slugArray, 0, $ii) );
			$ret[] = $thisSlug;
		}

		$ret = array_reverse( $ret );
		return $ret;
	}

// 'page1--page11--page22--page33': return [ 'page1', 'page1--page11', 'page1--page11--page22' ];
	public static function findStack( $slug )
	{
		$ret = [];

		$stackSep = static::$stackSeparator;
		$stack = explode( $stackSep, $slug );
		array_pop( $stack );

		$stackSlug = '';
		foreach( $stack as $thisSlug ){
			if( strlen($stackSlug) ){
				$thisSlug = $stackSlug . $stackSep . $thisSlug;
			}
			$ret[] = $thisSlug;
			$stackSlug = $thisSlug;
		}

		return $ret;
	}
}