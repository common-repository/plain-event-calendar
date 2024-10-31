<?php
namespace Plainware;

class Sql
{
	public static $intTypes = [
		'INTEGER',
		'INT',
		// 'BIGINT',
		'TINYINT'
	];

	public static function create( $tableName, array $values )
	{
		$sql = '';
		$sqlArg = [];

	// insert
		$sql = 'INSERT INTO ' . $tableName;

	// fields
		$sf = [];
		$sv = [];

		foreach( $values as $k => $v ){
			if( null === $v ){
				continue;
			}

			$isInt = is_numeric( $v ) && ( strlen($v) < 12 );
			// $isInt = is_numeric( $v );
			// if( $isInt && (null === $v) ){
				// continue;
			// }

			if( is_array($v) ){
				$v = json_encode( $v );
			}

			$sf[] = $k;
			$sqlArg[] = $v;

			if( $isInt ){
				$sv[] = '%d';
			}
			else {
				$sv[] = '%s';
			}
		}
		$sql .= ' (' . join(', ', $sf) . ') VALUES (' . join(', ', $sv) . ')';

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function createMany( $tableName, array $arrayOfValues )
	{
// _print_r( $arrayOfValues );
// exit;

		$sqls = [];
		$sqlArgs = [];

	// split into batches
		$batchSize = 100;
		// $batchSize = 2;
		$batchCount = ceil( count($arrayOfValues) / $batchSize );

		for( $ii = 0; $ii < $batchCount; $ii++ ){
			$thisArrayOfValues = array_slice( $arrayOfValues, $batchSize * $ii, $batchSize );

			$sql = '';
			$sqlArg = [];

		// insert
			$sql = 'INSERT INTO ' . $tableName;

			$sf = [];
			$sv = [];

		// fields
			reset( $thisArrayOfValues );
			foreach( $thisArrayOfValues as $values ){
				foreach( $values as $k => $v ){
					if( isset($sf[$k]) ) continue;
					$sf[$k] = $k;
				}
			}

			reset( $thisArrayOfValues );
			foreach( $thisArrayOfValues as $values ){
				reset( $sf );
				$thisSv = [];
				foreach( $sf as $k ){
					if( ! isset($values[$k]) ){
						$thisSv[] = 'NULL';
					}
					else {
						$sqlArg[] = $values[$k];
						$isInt = is_numeric( $values[$k] ) && ( strlen($values[$k]) < 12 );

						if( $isInt ){
							$thisSv[] = '%d';
						}
						else {
							$thisSv[] = '%s';
						}
					}
				}
				$sv[] = '(' . join(', ', $thisSv) . ')';
			}

			$sql .= ' (' . join(', ', $sf) . ') VALUES ' . join(', ', $sv);

			$sqls[] = $sql;
			$sqlArgs[] = $sqlArg;
		}

// _print_r( $sqls );
// _print_r( $sqlArgs );
// exit;

		$ret = 0;
		for( $ii = 0; $ii < count($sqls); $ii++ ){
			$sql = $sqls[$ii];
			$sqlArg = $sqlArgs[$ii];
			$thisRet = static::query( $sql, $sqlArg );
			$ret += $thisRet;
		}

		return $ret;
	}

	public static function read( $tableName, array $where, array $orderBy, array $limitOffset )
	{
		$sql = '';
		$sqlArg = [];

	// select
		$sql = 'SELECT * FROM ' . $tableName;

	// where
		list( $whereSql, $whereSqlArg ) = static::whereToSql( $where );

		$sql .= $whereSql;
		$sqlArg = array_merge( $sqlArg, $whereSqlArg );

	// orderby
		if( $orderBy ){
			$s = [];
			foreach( $orderBy as $w ){
				list( $name, $direction ) = $w;
				$thisS = $name . ' ' . $direction;
				$s[ $thisS ] = $thisS;
			}
			$sql .= ' ORDER BY ' . join( ', ', $s );
		}

	// limit
		if( $limitOffset ){
			list( $limit, $offset ) = $limitOffset;

			if( $offset && $limit ){
				$sql .= ' LIMIT ' . $offset . ', ' . $limit;
			}
			elseif( $limit ){
				$sql .= ' LIMIT ' . $limit;
			}
		}

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function count( $tableName, array $where )
	{
		$ret = 0;

		$sql = '';
		$sqlArg = [];

	// select
		$sql = 'SELECT COUNT(*) AS count FROM ' . $tableName;

	// where
		list( $whereSql, $whereSqlArg ) = static::whereToSql( $where );
		$sql .= $whereSql;
		$sqlArg = array_merge( $sqlArg, $whereSqlArg );

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function update( $tableName, array $values, array $where )
	{
		$ret = null;
		if( ! $values ){
			return $ret;
		}

		$sql = '';
		$sqlArg = [];

	// update
		$sql = 'UPDATE ' . $tableName;

	// fields
		$s = [];
		foreach( $values as $k => $v ){
			$isInt = is_numeric( $v ) && ( strlen($v) < 12 );

			if( is_array($v) ){
				$v = json_encode( $v );
			}

			$thisS = $k . '=';
			if( $isInt ){
				$thisS .= '%d';
			}
			else {
				$thisS .= '%s';
			}

			$s[] = $thisS;
			$sqlArg[] = $v;
		}

		if( ! $s ){
			return $ret;
		}

		$sql .= ' SET ' . join(', ', $s);

	// where
		list( $whereSql, $whereSqlArg ) = static::whereToSql( $where );
		$sql .= $whereSql;
		$sqlArg = array_merge( $sqlArg, $whereSqlArg );

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function delete( $tableName, array $where )
	{
		if( ! $where ){
			exit( __METHOD__ . ': ' . __LINE__ . ': cannot proceed without conditions!' );
		}

		$sql = '';
		$sqlArg = [];

	// sql
		$sql = 'DELETE FROM ' . $tableName;

	// where
		list( $whereSql, $whereSqlArg ) = static::whereToSql( $where );
		$sql .= $whereSql;
		$sqlArg = array_merge( $sqlArg, $whereSqlArg );

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

/* database forge */
	public static function dbDropColumn( $tableName, $name )
	{
		$sql = 'ALTER TABLE ' . $tableName . ' DROP COLUMN ' . $name;
		$arg = [];
		$ret = [ $sql, $arg ];
		return $ret;
	}

	public static function dbDropTable( $tableName )
	{
		$sql = 'DROP TABLE ' . $tableName;
		$arg = [];
		$ret = [ $sql, $arg ];
		return $ret;
	}

	public static function dbAddColumn( $tableName, $k, array $f )
	{
		$intTypes = static::$intTypes;

		$sqlArg = [];

		$sql = '';
		$sql .= 'ALTER TABLE ' . $tableName . ' ADD ';

		$sql .= $k . ' ' . $f['type'];
		$sql .= ( isset($f['null']) && $f['null'] ) ? ' NULL' : ' NOT NULL';

		if( isset($f['default']) && (! is_array($f['default'])) ){
			$sql .= ' DEFAULT ';

			$isInt = in_array( $f['type'], $intTypes ) ? true : false;
			if( $isInt ){
				$sql .= '%d';
			}
			else {
				$sql .= '%s';
			}
			$sqlArg[] = $f['default'];
		}

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function dbEmptyTable( $tableName )
	{
		// $sql = 'TRUNCATE ' . $tableName;
		$sql = 'DELETE FROM ' . $tableName;
		$sqlArg = [];

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}

	public static function dbCreateTable( $tableName, array $fields )
	{
		$intTypes = static::$intTypes;

		$sqlArg = [];

		$sql = '';
		$sql .= 'CREATE TABLE IF NOT EXISTS ' . $tableName . ' (';

		$s = [];
		$ks = [];
		reset( $fields );
		foreach( $fields as $k => $f ){
			$thisS = $k . ' ' . $f['type'];
			$thisS .= ( isset($f['null']) && $f['null'] ) ? ' NULL' : ' NOT NULL';
			if( isset($f['auto_increment']) && $f['auto_increment'] ){
				$thisS .= ' AUTO_INCREMENT';
			}

			if( isset($f['default']) && (! is_array($f['default'])) ){
				$thisS .= ' DEFAULT ';

				$isInt = ( isset($f['type']) && in_array($f['type'], $intTypes) ) ? true : false;
				if( $isInt ){
					$thisS .= '%d';
				}
				else {
					$thisS .= '%s';
				}
				$sqlArg[] = $f['default'];
			}

			$s[] = $thisS;

			if( isset($f['key']) && $f['key'] ){
				$ks[ $k ] = $k;
			}
		}

		if( $ks ){
			$s[] = ' CONSTRAINT ' . join('_', $ks) . ' PRIMARY KEY(' . join(',', $ks) . ')';
		}

		$sql .= join( ', ', $s );
		$sql .= ')';

		$ret = [ $sql, $sqlArg ];
		return $ret;
	}
/* end of database forge */

	public static function whereToSql( array $where )
	{
		$sql = '';

		$s = [];
		$sqlArgs = [];

		foreach( $where as $w ){
			list( $name, $compare, $value ) = $w;

			if( is_array($value) ){
				$isInt = true;
				foreach( $value as $v ){
					$thisIsInt = is_numeric( $v ) && ( strlen($v) < 12 );
					if( ! $thisIsInt ){
						$isInt = false;
						break;
					}
				}
				if( $isInt ){
					$value = array_map( 'intval', $value );
				}
			}
			else {
				$isInt = is_numeric( $value ) && ( strlen($value) < 12 );
			}

			if( 'LIKE' === $compare ) $compare = ' LIKE ';
			if( 'CONTAINS' === $compare ) $compare = ' LIKE ';

			if( $isInt ){
				$thisV = '%d';
			}
			else {
				$thisV = '%s';
			}

			if( is_array($value) && ('=' == $compare) ){
				$compare = ' IN ';
				$thisV = array_fill( 0, count($value), $thisV );
				$thisV = '(' . join( ',', $thisV ) . ')';
				foreach( $value as $v ) $sqlArgs[] = $v;
			}
			else {
				$sqlArgs[] = $value;
			}

			$thisS = $name . $compare . $thisV;
			$s[] = $thisS;
		}

		if( $s ){
			$sql .= ' WHERE ' . join( ' AND ', $s );
		}

		// if( ! $s ){
			// $s[] = '1=%d';
			// $sqlArgs[] = 1;
		// }

		// $sql .= ' WHERE ' . join( ' AND ', $s );
		$ret = [ $sql, $sqlArgs ];

		return $ret;
	}
}