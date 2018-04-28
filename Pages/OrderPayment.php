<?php include "Common.php"; ?>
<?php Session::verify(); ?>
<?php	
	$configuration = include("Configuration.php");
	$persistenceClient = $_SESSION["PersistenceClient"];
	$paypalClient = PaypalClient::fromConfiguration($configuration);
	$session = $_SESSION["Session"];
	$userLoggedIn = $session->user;
	$order = $userLoggedIn->orderCurrent;
	$storeURL = $configuration["StoreURL"];
	$paymentExecuteURL = $storeURL . "/OrderComplete.php";
	$paymentCancelURL = $storeURL . "/OrderPaymentCancelled.php";
	$paymentResponse = $paypalClient->payForOrder($order, $paymentExecuteURL, $paymentCancelURL);
	$paymentResponseAsLookup = JSONEncoder::jsonStringToLookup($paymentResponse);
	$paypalPaymentID = $paymentResponseAsLookup["id"];
$authHeader = "Authorization: Bearer " . $paypalClient->accessToken();
header($authHeader);
header("Content-Type: application/json");
	echo "{\"id\":\"" . $paypalPaymentID. "\"}";
?>