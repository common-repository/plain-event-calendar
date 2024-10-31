<?php
namespace Plainware\PlainEventCalendar;

class ModelInstall extends ModelSqlTable
{
	public $versions = null;

	public function name()
	{
		return '__install';
	}

	public function fields()
	{
		return [
			'id'			=> [ 'type' => 'VARCHAR(64)', 'null' => FALSE, 'key' => TRUE ],
			'version'	=> [ 'type' => 'INTEGER', 'null'	=> FALSE ],
		];
	}

	public function modules()
	{
		$ret = [];
		return $ret;
	}

	public function doUp( array $modules )
	{
		foreach( $modules as $module ){
			$moduleName = $module[0];
			$moduleVersion = $module[1];
			$funcUp = isset( $module[2] ) ? $module[2] : null;
			$funcDown = isset( $module[3] ) ? $module[3] : null;

			$installedVersion = $this->self->get( $moduleName );
			if( ! $installedVersion ) $installedVersion = 0;

			if( $installedVersion >= $moduleVersion ) continue;

			if( $funcUp ){
				if( is_array($funcUp) ) $funcUp = [ $this->app->{$funcUp[0]}, $funcUp[1] ];
				call_user_func( $funcUp );
			}

			$this->self->set( $moduleName, $moduleVersion );
		}
	}

	public function doDown( array $modules )
	{
		$modules = array_reverse( $modules );
		foreach( $modules as $module ){
			$moduleName = $module[0];
			$moduleVersion = $module[1];
			$funcUp = isset( $module[2] ) ? $module[2] : null;
			$funcDown = isset( $module[3] ) ? $module[3] : null;

		// do only 1, just drop everything
			if( 1 != $moduleVersion ) continue;

			$installedVersion = $this->self->get( $moduleName );
			if( ! $installedVersion ) $installedVersion = 0;

			// if( $installedVersion >= $moduleVersion ) continue;

			if( $funcDown ){
				if( is_array($funcDown) ) $funcDown = [ $this->app->{$funcDown[0]}, $funcDown[1] ];
				call_user_func( $funcDown );
			}

			// $this->self->set( $moduleName, $moduleVersion );
		}
	}

	public function get( $id )
	{
	// load all
		if( null === $this->versions ){
			$this->versions = $this->_load();
		}

		$ret = 0;
		if( array_key_exists($id, $this->versions) ){
			$ret = $this->versions[ $id ];
		}

		return $ret;
	}

	public function set( $id, $version )
	{
	// load all
		if( null === $this->versions ){
			$this->versions = $this->_load();
		}

		if( array_key_exists($id, $this->versions) ){
			$m = [ 'id' => $id, 'version' => $this->versions[$id] ];
			$m2 = $m;
			$m2 = [ 'version' => $version ];
			$this->self->update( $m, $m2 );
		}
		else {
			$m = [ 'id' => $id, 'version' => $version ];
			$this->self->create( $m );
		}

		$this->versions[ $id ] = $version;
		return $version;
	}

	protected function _load()
	{
		$ret = [];
		$all = $this->self->findAll();
		foreach( $all as $m ){
			$ret[ $m['id'] ] = $m['version'];
		}
		return $ret;
	}
}