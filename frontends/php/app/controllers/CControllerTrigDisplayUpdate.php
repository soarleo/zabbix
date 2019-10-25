<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


class CControllerTrigDisplayUpdate extends CController {

	/**
	 * @var array
	 */
	protected $color_caption;

	protected function init() {
		$this->color_captions = [
			'problem_unack_color' => _('Unacknowledged PROBLEM events'),
			'problem_ack_color'   => _('Acknowledged PROBLEM events'),
			'ok_unack_color'      => _('Unacknowledged RESOLVED events'),
			'ok_ack_color'        => _('Acknowledged RESOLVED events')
		];
	}

	protected function checkInput() {
		$fields = [
			'custom_color'        => 'int32 | in '.EVENT_CUSTOM_COLOR_DISABLED.','.EVENT_CUSTOM_COLOR_ENABLED,
			'problem_unack_style' => 'required | int32 | in 0,1',
			'problem_ack_style'   => 'required | int32 | in 0,1',
			'ok_unack_style'      => 'required | int32 | in 0,1',
			'ok_ack_style'        => 'required | int32 | in 0,1',
			'ok_period'           => 'required | string | not_empty',
			'blink_period'        => 'required | string | not_empty'
		];

		$color_fields = [
			'problem_unack_color' => 'string',
			'problem_ack_color'   => 'string',
			'ok_unack_color'      => 'string',
			'ok_ack_color'        => 'string'
		];

		$ret = $this->validateInput($fields + $color_fields);
		if ($this->getInput('custom_color') == EVENT_CUSTOM_COLOR_ENABLED) {
			$ret &= $this->validateColors($color_fields);
		}

		if (!$ret) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'trigdisplay.edit')
			);

			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot update configuration'));

			$this->setResponse($response);
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$update_values = [
			'custom_color'        => $this->getInput('custom_color', EVENT_CUSTOM_COLOR_DISABLED),
			'problem_unack_style' => $this->getInput('problem_unack_style'),
			'problem_ack_style'   => $this->getInput('problem_ack_style'),
			'ok_unack_style'      => $this->getInput('ok_unack_style'),
			'ok_ack_style'        => $this->getInput('ok_ack_style'),
			'ok_period'           => $this->getInput('ok_period'),
			'blink_period'        => $this->getInput('blink_period')
		];

		$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
			->setArgument('action', 'trigdisplay.edit')
		);

		if ($update_values['custom_color'] == EVENT_CUSTOM_COLOR_ENABLED) {
			$update_values['problem_unack_color'] = $this->getInput('problem_unack_color');
			$update_values['problem_ack_color']   = $this->getInput('problem_ack_color');
			$update_values['ok_unack_color']      = $this->getInput('ok_unack_color');
			$update_values['ok_ack_color']        = $this->getInput('ok_ack_color');
		}

		DBstart();
		$result = update_config($update_values);
		$result = DBend($result);

		if ($result) {
			$response->setMessageOk(_('Configuration updated'));
		}
		else {
			$response->setMessageError(_('Cannot update configuration'));
			$response->setFormData($this->getInputAll());
		}

		$this->setResponse($response);
	}

	protected function validateColors($fields) {
		$color_validator = new CColorValidator();

		foreach (array_keys($fields) as $field) {
			if (!$color_validator->validate($this->getInput($field))) {
				$caption = $this->color_captions[$field];
				error(_s('Colour "%1$s" is not correct: expecting hexadecimal colour code (6 symbols).', $caption));
				return false;
			}
		}

		return true;
	}
}
