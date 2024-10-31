<?php
namespace Plainware\PlainEventCalendar;

abstract class ModelSqlTable extends \Plainware\ModelSqlTable
{
	public function __construct( App $app )
	{
		$this->db = $app->Db;
		$this->app = $app;
		$this->self = $app->{ get_class($this) };
	}

	public function getTableName()
	{
		global $wpdb;

		if( is_multisite() ){
			// $shareDatabase = get_site_option( 'locatoraid_share_database', 0 );
			$shareDatabase = false;
			$prefix = $shareDatabase ? $wpdb->base_prefix : $wpdb->prefix;
		}
		else {
			$prefix = $wpdb->prefix;
		}

		$prefix .= 'pec_';

		$ret = parent::getTableName();
		$ret = str_replace( '__', $prefix, $ret );

		return $ret;
	}
}