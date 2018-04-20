<?php

class PersistenceClientMySQL
{
	public function __construct($databaseServerName, $databaseUsername, $databasePassword, $databaseName)
	{
		$this->databaseServerName = $databaseServerName;
		$this->databaseUsername = $databaseUsername;
		$this->databasePassword = $databasePassword;
		$this->databaseName = $databaseName;
	}

	private function connect()
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$databaseConnection = new mysqli($this->databaseServerName, $this->databaseUsername, $this->databasePassword, $this->databaseName);
		return $databaseConnection;
	}

	private function dateToString($date)
	{
		if ($date == null)
		{
			$returnValue = null;
		}
		else
		{
			$dateFormatString = "Y-m-d H:i:s";
			$returnValue = $date->format($dateFormatString);
		}

		return $returnValue;
	}

	public function licenseGetByID($licenseID)
	{
		$returnValue = null;

		$databaseConnection = $this->connect();

		$queryText = "select * from License where LicenseID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $licenseID);
		$queryCommand->execute();
		$queryCommand->bind_result($licenseID, $userID, $productID, $transferTypeID, $transferTarget);

		while ($queryCommand->fetch())
		{
			$returnValue = new License($licenseID, $userID, $productID, $transferTypeID, $transferTarget);
			break;
		}

		$databaseConnection->close();

		return $returnValue;
	}

	public function licensesGetByTransferTarget($username, $emailAddress)
	{
		$returnValues = array();

		$databaseConnection = $this->connect();

		$queryText = "select * from License where (TransferTypeID = 1 and TransferTarget = ?) or (TransferTypeID = 2 and TransferTarget = ?)";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("ss", $username, $emailAddress);
		$queryCommand->execute();
		$queryCommand->bind_result($licenseID, $userID, $productID, $transferTypeID, $transferTarget);

		while ($queryCommand->fetch())
		{
			$license = new License($licenseID, $userID, $productID, $transferTypeID, $transferTarget);
			$returnValues[] = $license;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function licenseTransferTypesGetAll()
	{
		$returnValues = array();

		$databaseConnection = $this->connect();

		$queryText = "select * from LicenseTransferType";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->execute();
		$queryCommand->bind_result($transferTypeID, $name, $description);

		while ($queryCommand->fetch())
		{
			$transferType = new LicenseTransferType($transferTypeID, $name, $description);
			$returnValues[$transferTypeID] = $transferType;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function licenseSave($license)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		if ($license->licenseID == null)
		{
			$queryText =
				"insert into License (UserID, ProductID, TransferTypeID, TransferTarget)"
				. " values (?, ?, ?, ?)";
			$queryCommand = mysqli_prepare($databaseConnection, $queryText);
			$queryCommand->bind_param
			(
				"iiis", $license->userID, $license->productID, $license->transferTypeID, $license->transferTarget
			);
		}
		else
		{
			$queryText = "update License set UserID = ?, ProductID = ?, TransferTypeID = ?, TransferTarget = ? where LicenseID = ?";
			$queryCommand = mysqli_prepare($databaseConnection, $queryText);
			$queryCommand->bind_param
			(
				"iiisi", $license->userID, $license->productID, $license->transferTypeID, $license->transferTarget, $license->licenseID
			);
		}
		$didSaveSucceed = $queryCommand->execute();

		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$licenseID = mysqli_insert_id($databaseConnection);
			if ($licenseID != null)
			{
				$license->licenseID = $licenseID;
			}
		}

		$databaseConnection->close();

		return $license;
	}

	public function notificationSave($notification)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		if ($notification->notificationID == null)
		{
			$queryText =
				"insert into Notification (Addressee, Subject, Body, TimeCreated, TimeSent)"
				. " values (?, ?, ?, ?, ?)";
			$queryCommand = mysqli_prepare($databaseConnection, $queryText);
			$queryCommand->bind_param
			(
				"sssss",
				$notification->addressee, $notification->subject,
				$notification->body, $this->dateToString($notification->timeCreated),
				$this->dateToString($notification->timeSent)
			);
		}
		else
		{
			$queryText = "update Notification set Addressee = ?, Subject = ?, Body = ?, TimeCreated = ?, TimeSent = ? where NotificationID = ?";
			$queryCommand = mysqli_prepare($databaseConnection, $queryText);
			$queryCommand->bind_param
			(
				"sssssi",
				$notification->addressee, $notification->subject,
				$notification->body, $this->dateToString($notification->timeCreated),
				$this->dateToString($notification->timeSent),
				$notification->notificationID
			);
		}
		$didSaveSucceed = $queryCommand->execute();

		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$notificationID = mysqli_insert_id($databaseConnection);
			if ($notificationID != null)
			{
				$notification->notificationID = $notificationID;
			}
		}

		$databaseConnection->close();

		return $notification;
	}

	public function orderSave($order)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		$queryText =
			"insert into _Order (UserID, Status, TimeCompleted)"
			. " values (?, ?, ?)";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("sss", $order->userID, $order->status, $this->dateToString($order->timeCompleted));
		$didSaveSucceed = $queryCommand->execute();

		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$orderID = mysqli_insert_id($databaseConnection);
			if ($orderID != null)
			{
				$order->orderID = $orderID;
			}
		}

		$orderProducts = $order->productBatches;
		foreach ($orderProducts as $productBatch)
		{
			$productBatch->orderID = $orderID;
			$this->orderProductSave($productBatch);
		}

		$databaseConnection->close();

		return $notification;
	}

	public function orderProductSave($orderProduct)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		$queryText =
			"insert into Order_Product (OrderID, ProductID, Quantity)"
			. " values (?, ?, ?)";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("ssi", $orderProduct->orderID, $order->productID, $order->quantity);
		$didSaveSucceed = $queryCommand->execute();

		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$orderProductID = mysqli_insert_id($databaseConnection);
			if ($orderProductID != null)
			{
				$orderProduct->orderProductID = $orderProductID;
			}
		}

		$databaseConnection->close();

		return $notification;
	}

	public function paypalClientDataGet()
	{
		$returnValue = null;

		$databaseConnection = $this->connect();

		$queryText = "select * from PaypalClientData";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->execute();
		$queryCommand->bind_result($clientIDSandbox, $clientIDProduction, $isProductionEnabled);
		$queryCommand->fetch();

		$returnValue = new PaypalClientData($clientIDSandbox, $clientIDProduction, $isProductionEnabled);

		return $returnValue;
	}

	public function productGetByID($productID)
	{
		$returnValue = null;

		$databaseConnection = $this->connect();

		$queryText = "select * from Product where ProductID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $productID);
		$queryCommand->execute();
		$queryCommand->bind_result($productID, $name, $imagePath, $price, $contentPath);

		while ($queryCommand->fetch())
		{
			$returnValue = new Product($productID, $name, $imagePath, $price, $contentPath);
			break;
		}

		$databaseConnection->close();

		return $returnValue;
	}

	public function productsGetAll()
	{
		$returnValues = array();

		$databaseConnection = $this->connect();

		$queryText = "select * from Product";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->execute();
		$queryCommand->bind_result($productID, $name, $imagePath, $price, $contentPath);

		while ($queryCommand->fetch())
		{
			$product = new Product($productID, $name, $imagePath, $price, $contentPath);
			$returnValues[$productID] = $product;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function productsSearch($productNamePartial)
	{
		$returnValues = array();

		$databaseConnection = $this->connect();

		$queryText = "select * from Product where Name like '%" . $productNamePartial . "%'";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->execute();
		$queryCommand->bind_result($productID, $name, $imagePath, $price, $contentPath);

		while ($queryCommand->fetch())
		{
			$product = new Product($productID, $name, $imagePath, $price, $contentPath);
			$returnValues[$productID] = $product;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function promotionGetByCode($promotionCode)
	{
		$returnValue = null;

		$databaseConnection = $this->connect();

		$queryText = "select * from Promotion where Code = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("s", $promotionCode);
		$queryCommand->execute();
		$queryCommand->bind_result($promotionID, $description, $discount, $code);

		while ($queryCommand->fetch())
		{
			$returnValue = new Promotion($promotionID, $description, $discount, $code, array() );
			break;
		}

		$databaseConnection->close();

		if ($returnValue != null)
		{
			$returnValue->products = $this->productsGetByPromotionID($promotionID);
		}

		return $returnValue;
	}

	private function productsGetByPromotionID($promotionID)
	{
		$databaseConnection = $this->connect();

		$returnValues = array();

		$queryText = "select p.* from Promotion_Product pp, Product p where p.ProductID = pp.ProductID and pp.PromotionID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $promotionID);
		$queryCommand->execute();
		$queryCommand->bind_result($productID, $name, $imagePath, $price, $contentPath);

		while ($queryCommand->fetch())
		{
			$product = new Product($productID, $name, $imagePath, $price, $contentPath);
			$returnValues[] = $product;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function sessionSave($session)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		$queryText =
			"insert into Session (UserID, TimeStarted, TimeUpdated, TimeEnded)"
			. " values (?, ?, ?, ?)";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$timeStartedAsString = $this->dateToString($session->timeStarted);
		$timeUpdatedAsString = $this->dateToString($session->timeUpdated);
		$timeEndedAsString = $this->dateToString($session->timeEnded);
		$queryCommand->bind_param("isss", $session->user->userID, $timeStartedAsString, $timeUpdatedAsString, $timeEndedAsString);

		$didSaveSucceed = $queryCommand->execute();

		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$sessionID = mysqli_insert_id($databaseConnection);
			if ($sessionID != null)
			{
				$session->sessionID = $sessionID;
			}
		}

		$databaseConnection->close();

		return $session;
	}

	public function userDeleteByID($userID)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		$queryText = "update User set IsActive = 0 where UserID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $userID);
		$didDeleteSucceed = $queryCommand->execute();

		return $didDeleteSucceed;
	}

	public function userGetByID($userID)
	{
		$databaseConnection = $this->connect();
		$queryText = "select * from User where UserID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $userID);
		$returnValue = $this->userGetByQueryCommand($queryCommand);
		$databaseConnection->close();
		return $returnValue;
	}

	public function userGetByEmailAddress($emailAddress)
	{
		$databaseConnection = $this->connect();
		$queryText = "select * from User where EmailAddress = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("s", $emailAddress);
		$returnValue = $this->userGetByQueryCommand($queryCommand);
		$databaseConnection->close();
		return $returnValue;
	}

	public function userGetByUsername($username)
	{
		$databaseConnection = $this->connect();
		$queryText = "select * from User where Username = ? and IsActive = 1";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("s", $username);
		$returnValue = $this->userGetByQueryCommand($queryCommand);
		$databaseConnection->close();
		return $returnValue;
	}

	private function userGetByQueryCommand($queryCommand)
	{
		$queryCommand->execute();
		$queryCommand->bind_result($userID, $username, $emailAddress, $passwordSalt, $passwordHashed, $passwordResetCode, $isActive);
		$queryCommand->store_result();

		$numberOfRows = $queryCommand->num_rows();
		if ($numberOfRows == 0)
		{
			$userFound = null;
		}
		else
		{
			$queryCommand->fetch();

			$licenses = $this->licensesGetByUserID($userID);

			$userFound = new User
			(
				$userID, $username, $emailAddress, $passwordSalt,
				$passwordHashed, $passwordResetCode, $isActive, $licenses
			);
		}

		return $userFound;
	}

	private function licensesGetByUserID($userID)
	{
		$returnValues = array();

		$databaseConnection = $this->connect();

		$queryText = "select * from License where UserID = ?";
		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("i", $userID);
		$queryCommand->execute();
		$queryCommand->bind_result($licenseID, $userID, $productID, $transferTypeID, $transferTarget);

		while ($row = $queryCommand->fetch())
		{
			$license = new License($licenseID, $userID, $productID, $transferTypeID, $transferTarget);
			$returnValues[] = $license;
		}

		$databaseConnection->close();

		return $returnValues;
	}

	public function userSave($user)
	{
		$databaseConnection = $this->connect();

		if ($databaseConnection->connect_error)
		{
			die("Could not connect to database.");
		}

		if ($user->userID == null)
		{
			$queryText =
				"insert into User (Username, EmailAddress, PasswordSalt, PasswordHashed, PasswordResetCode, IsActive)"
				. " values (?, ?, ?, ?, ?, ?)";
		}
		else
		{
			$queryText = "update User set username = ?, emailAddress = ?, passwordSalt = ?, passwordHashed = ?, passwordResetCode = ?, isActive=?";
		}

		$queryCommand = mysqli_prepare($databaseConnection, $queryText);
		$queryCommand->bind_param("sssssi", $user->username, $user->emailAddress, $user->passwordSalt, $user->passwordHashed, $user->passwordResetCode, $user->isActive);
		$didSaveSucceed = $queryCommand->execute();
		if ($didSaveSucceed == false)
		{
			die("Could not write to database.");
		}
		else
		{
			$userID = mysqli_insert_id($databaseConnection);
			if ($userID != null)
			{
				$user->userID = $userID;
			}
		}

		$databaseConnection->close();

		return $user;
	}
}

?>