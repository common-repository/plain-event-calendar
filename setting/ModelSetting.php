<?php
namespace Plainware\PlainEventCalendar;

class ModelSetting extends \Plainware\WpModelSettingOption
{
	public function prefix()
	{
		return 'pec_';
	}

	public function defaults()
	{
		$ret = parent::defaults();

		$ret['time_week_starts'] = 0;
		$ret['time_time_format'] = 'g:ia';
		$ret['time_date_format'] = 'j M Y';

		return $ret;
	}
}