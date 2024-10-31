<?php
namespace Plainware;

use \Plainware\Sql;

abstract class ModelSqlTable extends \Plainware\Model
{
	public $db;

	public function getTableName()
	{
		return $this->self->name();
	}

	public function repoCreate( $m )
	{
		list( $sql, $arg ) = Sql::create( $this->getTableName(), $m );
		return $this->db->query( $sql, $arg );
	}

	public function repoRead( array $where, array $orderBy, array $limitOffset )
	{
		list( $sql, $arg ) = Sql::read( $this->getTableName(), $where, $orderBy, $limitOffset );
		return $this->db->query( $sql, $arg );
	}

	public function repoCount( array $where )
	{
		list( $sql, $arg ) = Sql::count( $this->getTableName(), $where );
		$ret = $this->db->query( $sql, $arg );

		if( $ret ){
			$ret = current( current($ret) );
			$ret = (int) $ret;
		}
		else {
			$ret = 0;
		}

		return $ret;
	}

	public function repoUpdate( array $values, array $where )
	{
		list( $sql, $arg ) = Sql::update( $this->getTableName(), $values, $where );
		$ret = $this->db->query( $sql, $arg );
		return $ret;
	}

	public function repoDelete( array $where )
	{
		list( $sql, $arg ) = Sql::delete( $this->getTableName(), $where );
		$ret = $this->db->query( $sql, $arg );
		return $ret;
	}

	final public function up1()
	{
		$fields = $this->self->fields();
		list( $sql, $arg ) = Sql::dbCreateTable( $this->getTableName(), $fields );
		return $this->db->query( $sql, $arg );
	}

	final public function down1()
	{
		list( $sql, $arg ) = Sql::dbDropTable( $this->getTableName() );
		return $this->db->query( $sql, $arg );
	}
}