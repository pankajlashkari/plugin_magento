<?php

/**
 * File for PayLane_PayLaneDirectDebit_Model_Country class
 *
 * Created on 2011-11-23
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLaneDirectDebit_Model_Country
{
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 'AT', 'label' => 'Austria'),
			array('value' => 'DE', 'label' => 'Germany'),
			array('value' => 'NL', 'label' => 'Netherlands'),
		);
	}
}