<?php

class Notification
{
	public $notificationID;
	public $addressee;
	public $subject;
	public $body;
	public $timeCreated;
	public $timeSent;

	public function __construct($notificationID, $addressee, $subject, $body, $timeCreated, $timeSent)
	{
		$this->notificationID = $notificationID;
		$this->addressee = $addressee;
		$this->subject = $subject;
		$this->body = $body;
		$this->timeCreated = $timeCreated;
		$this->timeSent = $timeSent;
	}

	public function sendAsEmail($persistenceClient)
	{
		$configuration = include("Configuration.php");
		$isEmailEnabled = $configuration["EmailEnabled"];
		if ($isEmailEnabled == true)
		{
			mail($this->addressee, $this->subject, $this->body);
			$now = new DateTime();
			$this->timeSent = $now;
			$persistenceClient->notificationSave($this);
		}
	}
}

?>