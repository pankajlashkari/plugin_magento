<?php

/**
 * File for PayLane_PayLaneSecureForm_Model_Language class
 *
 * Created on 2011-11-06
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLaneSecureForm_Model_Language
{
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 'en', 'label' => 'English'),
			array('value' => 'pl', 'label' => 'Polish'),
		);
	}
}