<?php
namespace Plainware;

class Q
{
	public static function order( array $objects, array $orderBy )
	{
		$ret = $objects;

	// orderby
		if( ! $orderBy ){
			return $ret;
		}

		uasort( $ret, function($a, $b) use( $orderBy ){
			$ret = 0;
			reset( $orderBy );
			foreach( $orderBy as $w ){
				list( $name, $direction ) = $w;

				if( 'ASC' == $direction ){
					$cmp1 = isset( $a[$name] ) ? $a[$name] : null;
					$cmp2 = isset( $b[$name] ) ? $b[$name] : null;
				}
				else {
					$cmp1 = isset( $b[$name] ) ? $b[$name] : null;
					$cmp2 = isset( $a[$name] ) ? $a[$name] : null;
				}

				if( is_numeric($cmp1) && is_numeric($cmp2) ){
					$ret = ( $cmp1 - $cmp2 );
				}
				else {
					$ret = strcmp( $cmp1, $cmp2 );
				}

				if( $ret ){
					return $ret;
				}
			}
			return $ret;
		});

		return $ret;
	}

	public static function filter( array $objects, array $where )
	{
		$ret = [];
		foreach( $objects as $id => $obj ){
			if( ! static::check($obj, $where) ) continue;
			$ret[ $id ] = $obj;
		}

		return $ret;
	}

	public static function check( $obj, array $where )
	{
		$ret = true;

		foreach( $where as $w ){
			$thisRet = static::checkOne( $obj, $w );
			if( ! $thisRet ){
				$ret = false;
				break;
			}
		}

		return $ret;
	}

	public static function getCompares()
	{
		$ret = [];

		$ret[ '=' ] = 1;
		$ret[ '<>' ] = 1;
		$ret[ '>' ] = 1;
		$ret[ '>=' ] = 1;
		$ret[ '<' ] = 1;
		$ret[ '<=' ] = 1;
		$ret[ 'CONTAINS' ] = 1;

		return $ret;
	}

	public static function checkOne( $obj, $cond )
	{
		$ret = false;

		list( $k, $compare, $to ) = $cond;
		if( ! array_key_exists($k, $obj) ){
			// $ret = true;
			return $ret;
		}

		$v = $obj[$k];

		if( null === $v ){
			$ret = true;
			return $ret;
		}

		switch( $compare ){
			case '=':
				if( is_array($to) ){
					if( is_array($v) ){
						$ret = array_intersect($v, $to) ? TRUE : FALSE;
					}
					else {
						$ret = in_array( $v, $to );
					}
				}
				else {
					if( is_array($v) ){
						$ret = in_array( $to, $v );;
					}
					else {
						$ret = ( $v == $to );
					}
				}
				break;

			case '<>':
				if( is_array($to) ){
					$ret = ! in_array( $v, $to );
				}
				else {
					$ret = ( $v != $to );
				}
				break;

			case '>':
				$ret = ( $v > $to );
				break;

			case '&':
				$ret = ( $v & $to );
				break;

			case '>=':
				$ret = ( $v >= $to );
				break;

			case '<':
				$ret = ( $v < $to );
				break;

			case '<=':
				$ret = ( $v <= $to );
				break;

			case 'CONTAINS':
			case 'LIKE':
				$ret = ( FALSE === strpos($v, $to) ) ? FALSE : TRUE;
				break;

			default:
				exit( __METHOD__ . ": unknown compare: '$compare'" );
				break;
		}

		return $ret;
	}
}