<?php include "Common.php"; ?>
<?php Session::verify(); ?>

<html>

<head><?php PageWriter::elementHeadWrite("Order Details"); ?></head>

<body>

	<?php PageWriter::headerWrite(); ?>

	<div class="divCentered">
		<label><b>Order Verification:</b></label><br /><br />
		<div>
			<?php
				$session = $_SESSION["Session"];
				$userLoggedIn = $session->user;
				$persistenceClient = $_SESSION["PersistenceClient"];
				$order = $userLoggedIn->orderCurrent;

				$orderID = $order->orderID;
				$productBatchesInOrder = $order->productBatches;
				$productsAll = $persistenceClient->productsGetAll();
				$numberOfBatches = count($productBatchesInOrder);
				if ($numberOfBatches == 0)
				{
					echo "(no items)";
				}
				else
				{
					echo "<ul>";
					for ($i = 0; $i < count($order->productBatches); $i++)
					{
						$productBatch = $order->productBatches[$i];
						$productID = $productBatch->productID;
						$controlID = "Product" . $productID . "Quantity";
						if (isset($_POST[$controlID]) == true)
						{
							$quantityChanged = $_POST[$controlID];
							unset($_POST[$controlID]) ;
							$productBatch->quantity = $quantityChanged;
						}
						$product = $productsAll[$productID];
						$productName = $product->name;
						$productAsString = $productName;
						$quantity = $productBatch->quantity;
						$productPricePerUnit = $product->price;
						$productBatchPrice = ($productPricePerUnit * $quantity);
						if ($order->timeCompleted == null)
						{
							$productAsString = $productAsString . " x " . $quantity . "@ $" . $productPricePerUnit . " each = $" . $productBatchPrice;
						}
						else
						{
							$productAsString .= " x " . $quantity . " @ $" . $productPricePerUnit . " each = $" . $productBatchPrice;
						}
						$productAsListItem = "<li>" . $productAsString . "</li>";
						echo($productAsListItem);
					}
					echo "</ul>";
				}
			?>
			<br />
			<br />
			<label><b>Promotion:</b></label><br /><br />
			<label>
				<?php
					$promotion = $order->promotion;
					if ($promotion == null)
					{
						echo "(none)";
					}
					else
					{
						echo $promotion->description . " ($" . $promotion->discount . " off)";
					}
				?>
			</label><br />
			<br />

			<label><b>Payment:</b></label>
			<label>
				<?php 
					$paymentID = $order->paymentID;
echo("paymentID is " . $paymentID);
					$paypalClient = PaypalClient::fromConfiguration($configuration);
					$isPaymentValid = $paypalClient->paymentVerifyByID($paymentID);
					if ($isPaymentValid == true)
					{
						$now = new DateTime();
						$userLoggedIn->orderCurrent = new Order(null, $userLoggedIn->userID, null, "InProgress", $now, $now, null, null, array() );

						$orderToComplete = $order;
						$orderToComplete->complete($paymentID);
						$persistenceClient = $_SESSION["PersistenceClient"];
						$persistenceClient->orderSave($orderToComplete);

						$licensesFromOrder = $orderToComplete->toLicenses();
						foreach ($licensesFromOrder as $license)
						{
							$persistenceClient->licenseSave($license);
							$userLoggedIn->licenses[] = $license;
						}

						echo("Verified.");
					}
					else
					{
						echo("Payment could not be verified.  Please contact support.");
					}
				?>
			</label>
		</div><br />

		<a href='OrderHistory.php'>Order History</a><br />

		<a href='User.php'>Back to Account Details</a><br />

	</div>

	<?php PageWriter::footerWrite(); ?>

</body>
</html>