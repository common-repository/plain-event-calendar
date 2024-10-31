<?php
namespace Plainware\PlainEventCalendar;

abstract class WpModelCustomPost extends \Plainware\WpModelCustomPost
{
	public function getPostType()
	{
		$ret = parent::getPostType();

		$prefix = 'pec-';
		$ret = str_replace( '__', $prefix, $ret );

		return $ret;
	}
}