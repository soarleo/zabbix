<?php
/*
** Zabbix
** Copyright (C) 2001-2017 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CControllerWidgetClockView extends CController {

	protected function init() {
		$this->disableSIDValidation();
	}

	protected function checkInput() {
		$fields = [
			'name' =>	'string',
			'fields' =>	'required|array'
		];

		$ret = $this->validateInput($fields);

		if ($ret) {
			/*
			 * @var array  $fields
			 * @var int    $fields['time_type']
			 * @var string $fields['itemid']             (optional)
			 */
			// TODO VM: if fields are present, check that fields have enough data
			// TODO VM: itemid -> mandotory, if time_type is TIME_TYPE_HOST
		}

		if (!$ret) {
			// TODO VM: prepare propper response for case of incorrect fields
			$this->setResponse(new CControllerResponseData(['main_block' => CJs::encodeJson('')]));
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() >= USER_TYPE_ZABBIX_USER);
	}

	protected function doAction() {
		$time = null;
		$name = CWidgetConfig::getKnownWidgetTypes()[WIDGET_CLOCK];
		$time_zone_string = null;
		$time_zone_offset = null;
		$error = null;

		// Default values
		$default = [
			'time_type' => null,
			'itemid' => null,
			'hostid' => null // TODO VM: probably will not be used at all
		];

		$data = $this->getInput('fields');

		// Apply defualt value for data
		foreach ($default as $key => $value) {
			if (!array_key_exists($key, $data)) {
				$data[$key] = $value;
			}
		}

		switch ($data['time_type']) {
			case TIME_TYPE_HOST:
				$itemid = $data['itemid'];

				if (!empty($data['hostid'])) {
					$new_itemid = get_same_item_for_host($itemid, $data['hostid']);
					$itemid = !empty($new_itemid) ? $new_itemid : '';
				}

				$items = API::Item()->get([
					'output' => ['itemid', 'value_type'],
					'selectHosts' => ['name'],
					'itemids' => [$itemid]
				]);

				if ($items) {
					$item = $items[0];
					$name = $item['hosts'][0]['name'];
					unset($items, $item['hosts']);

					$last_value = Manager::History()->getLast([$item]);

					if ($last_value) {
						$last_value = $last_value[$item['itemid']][0];

						try {
							$now = new DateTime($last_value['value']);

							$time_zone_string = 'GMT'.$now->format('P');
							$time_zone_offset = $now->format('Z');

							$time = time() - ($last_value['clock'] - $now->getTimestamp());
						}
						catch (Exception $e) {
							$error = _('No data');
						}
					}
					else {
						$error = _('No data');
					}
				}
				else {
					$error = _('No data');
				}
				break;

			case TIME_TYPE_SERVER:
				$name = _('Server');

				$now = new DateTime();
				$time = $now->getTimestamp();
				$time_zone_string = 'GMT'.$now->format('P');
				$time_zone_offset = $now->format('Z');
				break;

			default:
				$name = _('Local');
				break;
		}

		$this->setResponse(new CControllerResponseData([
			'user' => [
				'debug_mode' => $this->getDebugMode()
			],
			'clock' => [
				'time' => $time,
				'time_zone_string' => $time_zone_string,
				'time_zone_offset' => $time_zone_offset,
				'error' => $error
			],
			'name' => $this->getInput('name', $name)
		]));
	}
}
