<?php

/**
 * File for PayLane_PayLanePayPal_Model_Response class
 *
 * Created on 2011-12-03
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLanePayPal_Model_Response
{
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 'post', 'label' => 'POST'),
			array('value' => 'get', 'label' => 'GET'),
		);
	}
}