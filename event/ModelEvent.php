<?php
namespace Plainware\PlainEventCalendar;

class ModelEvent extends ModelSqlTable
{
	public function name()
	{
		return '__event';
	}

	public function fields()
	{
		return [
			'id'				=> [ 'type' => 'INTEGER',			'null' => false,	'auto_increment' => true, 'key' => true ],
			'title'			=> [ 'type' => 'VARCHAR(255)',	'null' => false ],
			'description'	=> [ 'type' => 'TEXT',				'null' => false ],
			'date_start'	=> [ 'type' => 'INTEGER',			'null' => false ],
			'date_end'		=> [ 'type' => 'INTEGER',			'null' => false ],
			'state'			=> [ 'type' => 'VARCHAR(16)',		'null' => false, 'default' => 'active' ],
		];
	}

	public function q()
	{
		$q = parent::q();

		$q->orderBy( 'id', 'DESC' );
		$q->orderBy( 'date_start' );

		return $q;
	}

	public static function isArchived( $m )
	{
		return ( $m['state'] == 'archive' );
	}

// actions
	public function actions( $m )
	{
		$ret = [];

		if( $this->self->isArchived($m) ){
			$ret[ 'activate' ] = '__Activate__';
			$ret[ 'delete' ] = '__Delete__';
		}
		else {
			$ret[ 'deactivate' ] = '__Deactivate__';
		}

		return $ret;
	}

	public function deactivate( $m )
	{
		$m2 = $m;
		$m2['state'] = 'archive';
		return $this->self->update( $m, $m2 );
	}

	public function activate( $m )
	{
		$m2 = $m;
		$m2['state'] = 'active';
		return $this->self->update( $m, $m2 );
	}
}