<?php

/**
 * File for PayLane_PayLaneNotifications_StandardController class
 *
 * @package		paylane-utils-magento
 * @copyright	2014 PayLane Sp. z o.o.
 * @author		Marcel Tyszkiewicz <marcel.tyszkiewicz@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLaneNotifications_StandardController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Handles the notification sent by PayLane.
	 * 
	 * @return void
	 */
	public function notifyAction()
	{
		$post_data = $this->getRequest()->getPost();

		$notification_model = Mage::getSingleton('paylanenotifications/standard', $post_data);
		
		if (!$notification_model->isTokenValid())
		{
			exit("Invalid token");
		}

		if (!$notification_model->handleNotification())
		{
			exit("Can't handle notification");
		}

		echo $notification_model->getCommunicationId();
		exit();
	}
}