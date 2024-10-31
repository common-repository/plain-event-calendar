<?php
namespace Plainware;
use \Plainware\Q;

abstract class WpModelCustomPost extends \Plainware\Model
{
	abstract public function toWp();

	// public function toWp()
	// {
		// return [
			// 'id'				=> 'ID',
			// 'title'			=> 'post_title',
			// 'description'	=> 'post_content',
			// 'state'			=> [ 'post_status' => ['active' => 'publish', 'archive' => 'trash'] ],
		// ];
	// }

	public function __construct()
	{
		parent::__construct();

		$postType = $this->self->getPostType();

		if( ! post_type_exists($postType) ){
			// echo "REGISTERING POST TYPE '" . $postType . "'<br/>";
			register_post_type(
				$postType,
				[
					// 'public' => TRUE,
					'public' => false,
					'publicly_queryable' => true,
					'has_archive' => false,
					'exclude_from_search' => true,
					'show_in_menu' => false,
					'show_in_nav_menus'	=> false,
					'show_in_rest' => true,
					// 'show_ui'		=> TRUE,
					// 'menu_position'	=> 5,
				]
			);
		}
	}

	public function getPostType()
	{
		return $this->self->name();
	}

	public function convertToWp( $m )
	{
		$core = [];
		$meta = [];

		$toWp = $this->self->toWp();

		foreach( $m as $k => $v ){
			if( isset($toWp[$k]) ){
				$k2 = $toWp[$k];
				if( is_array($k2) ){
					$v2 = isset( $k2[$v] ) ? $k2[$v] : $v;
					$k2 = array_keys( $k2 );
					$k2 = current( $k2 );
					$core[ $k2 ] = $v2;
				}
				else {
					$core[ $k2 ] = $v;
				}
			}
			else {
				$meta[ $k ] = $v;
			}
		}

		$ret = [ $core, $meta ];
		return $ret;
	}

	public function convertFromWp( $wpPost, $meta )
	{
		$ret = [];
		$ret = $meta;

		$toWp = $this->self->toWp();

		foreach( $toWp as $k => $v ){
			if( isset($ret[$k]) ) continue;

			if( is_array($v) ){
				$k2 = $v;
				$k2 = array_keys( $k2 );
				$k2 = current( $k2 );
				$v2 = $wpPost->{ $k2 };

				$v = $v[ $k2 ];

				$k2 = array_flip( $v );
				$v2 = isset( $k2[$v2] ) ? $k2[$v2] : $v2;
			}
			else {
				$v2 = $wpPost->{ $v };
			}

			$ret[ $k ] = $v2;
		}

		return $ret;
	}

	public function repoUpdate( array $values, array $where )
	{
		$models = $this->self->find( $where, [], [] );
		if( ! $models ) return;

		foreach( $models as $m ){
			list( $core, $meta ) = $this->self->convertToWp( $m );
			$m2 = array_merge( $m, $values );
			list( $core2, $meta2 ) = $this->self->convertToWp( $m2 );

			$coreValues = array_diff( $core2, $core );
			$metaValues = array_diff( $meta2, $meta );

			$id = $core['ID'];

			if( $coreValues ){
				$coreValues['ID'] = $id;
				wp_update_post( $coreValues );
			}

			if( $metaValues ){
				foreach( $metaValues as $k => $v ){
					update_post_meta( $id, $k, $v );
				}
			}
		}
	}

	public function repoRead( array $where, array $orderBy, array $limitOffset )
	{
		$ret = [];

		$postType = $this->self->getPostType();

		$wpq = [];
		$wpq[ 'post_type' ] = $postType;
		$wpq[ 'posts_per_page' ] = -1;
		$wpq[ 'post_status' ] = [ 'any', 'trash', 'draft' ];
		$wpq[ 'perm' ] = 'readable';

		$wpQuery = new \WP_Query( $wpq );
		$posts = $wpQuery->get_posts();

	// meta
		$count = count( $posts );
		for( $ii = 0; $ii < $count; $ii++ ){
			$metaValues = [];
			$meta = get_metadata( 'post', $posts[$ii]->ID );
			if( $meta ){
				$metaValues = array_map( function($n){return $n[0];}, $meta );
			}

			$values = $this->self->convertFromWp( $posts[$ii], $metaValues );

			if( ! $values ) continue;
			if( ! isset($values['id']) ) continue;

			$id = $values['id'];
			$ret[ $id ] = $values;
		}

		$ret = Q::filter( $ret, $where );
		if( $orderBy ){
			$ret = Q::order( $ret, $orderBy );
		}

		list( $limit, $offset ) = $limitOffset ? $limitOffset : [ null, 0 ];

		if( $offset && $limit ){
			$ret = array_slice( $ret, $offset, $limit, true );
		}
		elseif( $offset ){
			$ret = array_slice( $ret, $offset, null, true );
		}
		elseif( $limit ){
			$ret = array_slice( $ret, 0, $limit, true );
		}

		return $ret;
	}

	public function repoCount( array $where )
	{
		$ret = $this->self->repoRead( $where, [], [] );
		$ret = count( $ret );

		return $ret;
	}

	public function repoCreate( $m )
	{
		$postType = $this->self->getPostType();

		list( $core, $meta ) = $this->self->convertToWp( $m );

		$wpPost = $core;
		$wpPost['post_type'] = $postType;

		if( ! array_key_exists('post_status', $wpPost) ){
			$wpPost['post_status'] = 'publish';
		}

		if( $meta ){
			$wpPost['meta_input'] = $meta;
		}

		$id = wp_insert_post( $wpPost, true );

		if( is_wp_error($id) ){
			$error = '__Database Error__' . ': ' . $id->get_error_message();
			throw new \Exception( $error );
		}

		$m['id'] = $id;
		return $m;
	}

	public function repoDelete( array $where )
	{
		$models = $this->self->read( $where, [], [] );

		foreach( $models as $m ){
			list( $core, $meta ) = $this->self->convertToWp( $m, $app );
			wp_delete_post( $core['ID'], true );
		}
	}

	public function findById( $id )
	{
		$ret = [];

		$post = get_post( $id );
		if( ! $post ) return $ret;

		$metaValues = [];
		$meta = get_metadata( 'post', $post->ID );
		if( $meta ){
			$metaValues = array_map( function($n){return $n[0];}, $meta );
		}

		$ret = $this->self->convertFromWp( $post, $metaValues );

		return $ret;
	}
}