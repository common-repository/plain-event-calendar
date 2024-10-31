<?php
namespace Plainware;

class AppStaticCaller
{
	private $className;
	private $app;

	public function __construct( $className, $app )
	{
		$this->className = $className;
		$this->app = $app;
	}

	public function __toString()
	{
		return $this->className;
	}

	public function __call( $func, $args )
	{
		return $this->app->call( $this->className . '::' . $func, $args );
	}
}