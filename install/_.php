<?php
namespace Plainware\PlainEventCalendar;

return [
	[ ModelInstall::class . '::modules',	ModelInstall_InstallWp::class . '::modules' ],
	[ ModelInstall::class . '::crud',	ModelInstall_InstallWp::class . '::crud' ],
];

class ModelInstall_InstallWp
{
	public static function crud( $ret, App $app )
	{
		return $app->CrudInstallWp;
	}

	public static function modules( array $ret, App $app )
	{
		$ret[] = [ 'install', 1, [$app->ModelInstall, 'up1'], [$app->ModelInstall, 'down1'] ];
		return $ret;
	}
}