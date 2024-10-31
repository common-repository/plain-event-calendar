<?php
namespace Plainware;

use \Plainware\Q;

abstract class Model
{
	public $self;

	abstract public function name();
	abstract public function fields();

	public function __construct()
	{
		$this->self = $this;
	}

	public function getFields()
	{
		return $this->fields();
	}

	public function actions( $m )
	{
		$ret = [];
		return $ret;
	}

	public function construct()
	{
		$ret = [];

		$fields = $this->self->fields();
		foreach( $fields as $k => $f ){
			$ret[ $k ] = isset( $f['default'] ) ? $f['default'] : null;
		}

		return $ret;
	}

	public function export( $m )
	{
		$ret = [];

		foreach( $m as $k => $v ){
			$ret[ $k ] = $v;
		}

		// $timeVars = [ 'start_at', 'end_at', 'create_at' ];
		// foreach( $m as $k => $v ){
			// if( in_array($k, $timeVars) ){
				// $v2 = $app->LibTimeFormat->formatFull( $v );
				// $ret[ $k ] = $v;

				// $k2 = $k . '.timestamp';
				// $ret[ $k2 ] = $v;
			// }
			// else {
				// $ret[ $k ] = $v;
			// }
		// }

		return $ret;
	}

	public function id( $m )
	{
		$ret = isset( $m['id'] ) ? $m['id'] : null;
		return $ret;
	}

	public function where( $m )
	{
		$ret = [];
		$ret[] = [ 'id', '=', $m['id'] ];
		return $ret;
	}

	public function create( $m )
	{
		$id = $this->self->repoCreate( $m );

		if( array_key_exists('id', $m) ){
			$m['id'] = $id;
		}

		return $m;
	}

	public function update( $m, $m2 )
	{
		$values = [];
		foreach( $m2 as $k2 => $v2 ){
			if( array_key_exists($k2, $m) && ($v2 == $m[$k2]) ){
				continue;
			}
			$values[ $k2 ] = $v2;
		}

		if( ! $values ){
			return $m2;
		}

		$where = $this->self->where( $m );
		$this->self->repoUpdate( $values, $where );

		return $m2;
	}

	public function delete( $m )
	{
		$where = $this->self->where( $m );
		$this->self->repoDelete( $where );

		return $m;
	}

	public function count( array $where )
	{
		$ret = $this->self->repoCount( $where );
		return $ret;
	}

	public function find( array $where, array $orderBy, array $limitOffset )
	{
		$ret = [];

		$data = $this->self->repoRead( $where, $orderBy, $limitOffset );
		if( ! $data ){
			return $ret;
		}

		$mBlueprint = $this->self->construct();

		foreach( $data as $e ){
			$m = $mBlueprint;
			$m = $this->toM( $m, $e );
			if( ! $m ) continue;

			if( array_key_exists('id', $m) ){
				$ret[ $m['id'] ] = $m;
			}
			else {
				$ret[] = $m;
			}
		}

		return $ret;
	}

	public function toM( $m, array $data )
	{
		foreach( $data as $k => $v ){
			if( ! array_key_exists($k, $m) ) continue;
			$m[$k] = $v;
		}
		return $m;
	}

	public function findById( $id )
	{
		if( is_array($id) && (! $id) ){
			$ret = [];
			return $ret;
		}

		$where = [];
		$where[] = [ 'id', '=', $id ];

		$limitOffset = is_array($id) ? [ null, 0 ] : [ 1, 0 ];

		$ret = $this->self->find( $where, [], $limitOffset );

		if( ! is_array($id) ){
			$ret = array_shift( $ret );
		}

		return $ret;
	}

	public function findAll()
	{
		return $this->self->find( [], [], [null, 0] );
	}

	// final public static function up1( App $app )
	// {
		// $crud = $app->{ get_called_class() }->crud();
		// if( ! $crud ){
			// return;
		// }

		// $fields = $app->{ get_called_class() }->fields();
		// $crud->up( $fields );
	// }

	// final public static function down1( App $app )
	// {
		// $crud = $app->{ get_called_class() }->crud();
		// if( ! $crud ){
			// return;
		// }

		// $crud->down();
	// }

	// abstract public function up1();
	// abstract public function down1();

	abstract public function repoCreate( $m );
	abstract public function repoRead( array $where, array $orderBy, array $limitOffset );
	abstract public function repoCount( array $where );
	abstract public function repoUpdate( array $values, array $where );
	abstract public function repoDelete( array $where );
}