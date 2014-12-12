<?php

/**
 * File for PayLane_PayLaneDirectDebit_Block_Form_Standard class
 *
 * Created on 2011-11-29
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLaneDirectDebit_Block_Form_Standard extends Mage_Payment_Block_Form
{
	
	/**
	 * Import form for direct debit details input
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('paylanedirectdebit/form/standard.phtml');
	}
}