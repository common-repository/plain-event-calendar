<?php
namespace Plainware;

abstract class WpModelSettingOption
{
	abstract public function prefix();

	public function getPrefix()
	{
		return $this->prefix();
	}

	public function defaults()
	{
		$ret = [];
		return $ret;
	}

	public function get( $id )
	{
		$defaults = static::defaults();
		$ret = isset( $defaults[$id] ) ? $defaults[$id] : null;

		static $loaded = null;
		if( null === $loaded ){
			$loaded = $this->_loadAll();
		}

		if( array_key_exists($id, $loaded) ){
			$ret = $loaded[$id];
		}

		return $ret;
	}

	public function set( $id, $value )
	{
		$name = $this->prefix() . $id;
		update_option( $name, $value );
		return $id;
	}

	protected function _loadAll()
	{
		$ret = [];

		$prefix = $this->getPrefix();

		$all = wp_load_alloptions();
		foreach( $all as $id => $value ){
			if( 0 !== strpos($id, $prefix) ){
				continue;
			}

			$id = substr( $id, strlen($prefix) );
			$ret[ $id ] = $value;
		}

		return $ret;
	}
}