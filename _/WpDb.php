<?php
namespace Plainware;

abstract class WpDb
{
	public static function query( $sql, array $arg )
	{
		global $wpdb;

		$isSelect = false;
		$isInsert = false;
		if( preg_match( '/^\s*insert\s/i', $sql ) ){
			$isInsert = true;
		}
		elseif( preg_match( '/^\s*(select|show)\s/i', $sql ) ){
			$isSelect = true;
		}

		if( $arg ){
			$sql = $wpdb->prepare( $sql, $arg );
		}

		if( $isSelect ){
			$ret = $wpdb->get_results( $sql, ARRAY_A );
		}
		else {
			$ret = $wpdb->query( $sql );
			if( $isInsert && (1 == $ret) ){
				$ret = $wpdb->insert_id;
			}
		}

		if( $wpdb->last_error ){
			$ret = FALSE;
			return $ret;
			exit( $wpdb->last_error . '<br/>' . $sql );
		}

		return $ret;
	}
}