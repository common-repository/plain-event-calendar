<?php
namespace Plainware;

abstract class WpApp extends \Plainware\App
{
	public function assetFileUri( $fullFile )
	{
		$ret = plugins_url( basename($fullFile), $fullFile );
		return $ret;
	}

	public function render()
	{
		$ret = parent::render();

		if( is_admin() ){
			$ret = $this->processRenderAdmin( $ret );
		}

		return $ret;
	}

	public function handle()
	{
		parent::handle();

	// assets
		$x = $this->x;
		$assets = isset( $x['asset'] ) ? $x['asset'] : [];

		$handleId = 1;
		$appVer = $this->version();
		foreach( $assets as $asset ){
			$isScript = ( '.js' == substr($asset, -strlen('.js')) ) ? true : false;

			$uri = $this->self->assetUri( $asset );
			$handle = $this->slugParam . '-' . $handleId++;

			if( $isScript ){
				wp_enqueue_script( $handle, $uri, [], $appVer );
			}
			else {
				wp_enqueue_style( $handle, $uri, [], $appVer );
			}
		}
	}

	public function processRenderAdmin( $ret )
	{
		$ret = str_replace( 'class="pw-button-link', 'class="page-title-action pw-button-link', $ret );


	// links & buttons in nav
		$ma = [];
		preg_match_all( '/\<nav role="toolbar".+\<\/nav\>/smU', $ret, $ma );

		for( $ii = 0; $ii < count($ma[0]); $ii++ ){
			$from = $ma[0][$ii];

			$to = $from;
			$to = str_replace( '<a class="', '<a class="page-title-action ', $to );
			$to = str_replace( '<a href=', '<a class="page-title-action" href=', $to );
			$to = str_replace( '<button type="submit"', '<button class="button button-secondary" type="submit"', $to );
			$to = str_replace( '<button type="button"', '<button class="button button-secondary" type="button"', $to );

			$ret = str_replace( $from, $to, $ret );
		}

	// submit button
		$ret = str_replace( '<button type="submit"', '<button type="submit" class="button button-primary"', $ret );
		// $ret = str_replace( '<button type="submit"', '<button type="submit" class="button button-secondary"', $ret );

	// links in menubar
		$ma = [];
		preg_match_all( '/\<nav role="menubar"\>.+\<\/nav\>/smU', $ret, $ma );

		for( $ii = 0; $ii < count($ma[0]); $ii++ ){
			$from = $ma[0][$ii];

			$to = $from;
			// $to = str_replace( '<a ', '<a class="nav-tab nav-tab-active" ', $to );
			// $to = str_replace( '<a ', '<a class="nav-tab" ', $to );
			$to = str_replace( '<a aria-selected="true"', '<a aria-selected="true" class="nav-tab nav-tab-active"', $to );
			$to = str_replace( '<a role="tab"', '<a role="tab" class="nav-tab"', $to );
			$to = '<div class="nav-tab-wrapper">' . $to . '</div>';

			$ret = str_replace( $from, $to, $ret );
		}

	// final wrap
		$ret = '<div class="wrap">' . $ret . '</div>';

		return $ret;
	}

	public function adminMenu( $label, $cap, $icon, $pos )
	{
		add_menu_page( $label, $label, $cap, $this->slugParam, [$this->self, 'adminRender'], $icon, $pos );

		$app = $this;
		$app->filter( get_class($app) . '::linkTo', function( $slug ) use ( $app ){
			$ret = admin_url( 'admin.php?page=' . esc_attr($app->slugParam) . '&' . esc_attr($app->slugParam) . '=' . esc_attr($slug) );
			return $ret;
		}, 0 );

		$submenu = $this->Page->nav( $this->x );
		$submenu = $this->Html->sortMenu( $submenu );

		$this->adminSubmenu( $submenu );
	}

	public function adminSubmenu( array $mySubmenu )
	{
	// submenu
		global $submenu;
		add_filter( 'parent_file', [$this, 'highlightCurrentAdminSubmenu'] );

		// ksort( $mySubmenu );

		$mySubmenuCount = 0;

		$parentMenu = $this->slugParam;
		$cap = 'read';

		foreach( $mySubmenu as $item ){
			if( ! is_array($item) ) continue;

			list( $slug, $label ) = $item;

			$to = $this->self->linkTo( $slug );
			$label = $this->self->translate( $label );

			// remove_submenu_page( $menuSlug, $to );

			$ret = add_submenu_page(
				$parentMenu,		// parent
				$label,				// page_title
				$label,				// menu_title
				$cap,					// capability
				$parentMenu . '-' . $slug,	// menu_slug
				'__return_null'
			);

			// if( ! array_key_exists($menuSlug, $submenu) ){
				// continue;
			// }

			$mySubmenu = $submenu[ $parentMenu ];
			$mySubmenuIds = array_keys( $mySubmenu );
			$mySubmenuId = array_pop( $mySubmenuIds );

			$submenu[ $parentMenu ][ $mySubmenuId ][ 2 ] = $to;
			$mySubmenuCount++;
		}

		if( isset($submenu[$parentMenu][0]) && ($submenu[$parentMenu][0][2] == $parentMenu) ){
			unset($submenu[$parentMenu][0]);
		}

		// if( ! $mySubmenuCount ){
			// remove_menu_page( $menuSlug );
		// }
	}

	public function highlightCurrentAdminSubmenu( $parentFile )
	{
		global $submenu, $submenu_file, $current_screen, $pagenow;

		$menuSlug = $this->slugParam;
		if( $current_screen->base != 'toplevel_page_' . $menuSlug ){
			return $parentFile;
		}

		if( 'admin.php' != $pagenow ){
			return $parentFile;
		}

		if( ! array_key_exists($menuSlug, $submenu) ){
			return $parentFile;
		}

		$parentFile = $menuSlug;

		$currentUri = admin_url( sprintf('admin.php?%s', http_build_query($_GET)) );
		foreach( $submenu[$menuSlug] as $sbm ){
			$submenuUri = $sbm[2];
			if( $submenuUri == substr($currentUri, 0, strlen($submenuUri)) ){
				$submenu_file = $submenuUri;
				break;
			}
		}

		return $parentFile;
	}

	public function adminHandle()
	{
		$page = null;
		if( isset($_POST['page']) ){
			$page = sanitize_text_field( $_POST['page'] );
		}
		elseif( isset($_GET['page']) ){
			$page = sanitize_text_field( $_GET['page'] );
		}
		if( $page != $this->slugParam ) return;

		$this->self->handle();

		$ajaxParam = 'pwajax';
		if( ! isset($_GET[$ajaxParam]) ) return;

		$isAjax = sanitize_text_field( $_GET[$ajaxParam] );
		if( $isAjax ){
			$ret = $this->self->render();
			echo $ret;
			exit;
		}
	}

	public function adminHandleAjax()
	{
		$page = null;
		if( isset($_POST['page']) ){
			$page = sanitize_text_field( $_POST['page'] );
		}
		elseif( isset($_GET['page']) ){
			$page = sanitize_text_field( $_GET['page'] );
		}
		if( $page != $this->slugParam ) return;

		$this->self->handle();
		$ret = $this->self->render();
		echo $ret;
		wp_die();
	}

	public function adminRender()
	{
		echo $this->self->render();
	}
}