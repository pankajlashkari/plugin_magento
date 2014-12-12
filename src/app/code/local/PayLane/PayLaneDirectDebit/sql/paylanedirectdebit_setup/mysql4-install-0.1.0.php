<?php

/**
 * File for setting up this payment method
 *
 * Created on 2011-11-23
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

$this->startSetup();
$this->run("ALTER TABLE `{$this->getTable('sales/order_payment')}` ADD `id_sale` VARCHAR(16) NOT NULL;");
$this->endSetup();