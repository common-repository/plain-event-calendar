<?php
/*
Plugin Name: Plain Event Calendar
Plugin URI: https://www.plainware.com/eventcalendar/
Description: A simple event calendar to manage and publish your events.
Version: 1.0.2
Author: plainware.com
Author URI: https://www.plainware.com/
Text Domain: plain-event-calendar
Domain Path: /languages/
*/

if( function_exists('add_action') ){
	add_action( 'plugins_loaded', array('PlainwarePlainEventCalendar', 'start') );
}

if( ! class_exists('PlainwarePlainEventCalendar') ){
class PlainwarePlainEventCalendar
{
	public static $instance;
	public $app;

	public static function start()
	{
		new static( __FILE__ );
	}

	public function __construct()
	{
		self::$instance = $this;

		$conf = [];
		$require = [ __DIR__ . '/include.php', __DIR__ . '/dev.php' ];
		$dirs = [];
		foreach( $require as $f ){
			if( file_exists($f) ) $dirs = array_merge( $dirs, require($f) );
		}

		if( ! class_exists(\Plainware\Autoloader::class) ){
			include_once( __DIR__ . '/_/Autoloader.php' );
		}
		\Plainware\Autoloader::register( 'Plainware\\PlainEventCalendar', $dirs );

		$this->app = new \Plainware\PlainEventCalendar\App( __FILE__, $dirs, $conf );

		add_action( 'admin_menu', [$this, 'adminMenu'] );
		add_action( 'admin_init', [$this->app, 'adminHandle'] );

		add_action( 'init', [$this, 'registerBlock'] );
		add_shortcode( 'plain-event-calendar', [$this, 'shortcode'] );
	}

	public function adminMenu()
	{
		$label = 'Plain Event Calendar';
		$cap = 'manage_options';
		$icon = 'dashicons-calendar';
		$this->app->adminMenu( $label, $cap, $icon, 31 );
	}

	public function registerBlock()
	{
		if ( ! function_exists('register_block_type') ){
			 // Gutenberg is not active
			 return;
		}

		$uri = $this->app->assetUri( 'pec-block.js' );
		wp_register_script( 'pec_block', $uri, [
			'wp-blocks',
			'wp-element',
			'wp-components',
			'wp-editor'
		]);

		register_block_type( 'plain-event-calendar/plain-event-calendar', [
			'title' => 'Plain Event Calendar',
			'editor_script' => 'pec_block',
			'render_callback' => [$this, 'renderBlock'],
			'attributes' => [
				'id' => [
					'type' => 'string',
					],
				]
			]
		);
	}

	public function shortcode( $attr )
	{
		if( is_admin() ){
			$ret = 'shortcode is rendered in front end only.';
			return $ret;
		}

		$app = $this->app;

		$app->filter( get_class($app) . '::makeUri', function( $ret, $slug ) use ( $app ){
			global $post;
			$ret = get_permalink( $post->ID );

			$glue = ( false === strpos($ret, '?') ) ? '?' : '&'; 
			$ret .= $glue . esc_attr($app->slugParam) . '=' . esc_attr($slug);

			return $ret;
		}, 1 );

		$app->slug = 'front';
		$app->handle();
		$ret = $app->render();

		return $ret;
	}

	public function renderBlock( $attr )
	{
		return $this->shortcode( $attr );
	}
}
}