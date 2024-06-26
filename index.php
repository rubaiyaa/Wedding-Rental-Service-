<?php
session_start();
require_once("dbcontroller.php");
$db_handle = new DBController();
if (!empty($_GET["action"])) {
	switch ($_GET["action"]) {
		case "add":
			if (!empty($_POST["quantity"])) {
				$productByCode = $db_handle->selectQuery("SELECT * FROM product WHERE product_id='" . $_GET["product_id"] . "'");
				$itemArray = array($productByCode[0]["product_id"] => array('name' => $productByCode[0]["name"], 'product_id' => $productByCode[0]["product_id"], 'quantity' => $_POST["quantity"], 'price' => $productByCode[0]["price"], 'image' => $productByCode[0]["image"], 'addon-id' => 'None', 'addon-price' => 0));
				if (!empty($_SESSION["cart_item"])) {
					if (in_array($productByCode[0]["product_id"], array_keys($_SESSION["cart_item"]))) {
						foreach ($_SESSION["cart_item"] as $k => $v) {
							if ($productByCode[0]["product_id"] == $k) {
								if (empty($_SESSION["cart_item"][$k]["quantity"])) {
									$_SESSION["cart_item"][$k]["quantity"] = 0;
								}
								$_SESSION["cart_item"][$k]["quantity"] += $_POST["quantity"];
							}
						}
					} else {
						$_SESSION["cart_item"] = $_SESSION["cart_item"] + $itemArray;
					}
				} else {
					$_SESSION["cart_item"] = $itemArray;
					$_SESSION["order_price"] = 0;
				}
			}
			$db_handle->dropqueryparam();
			break;
		case "remove":
			if (!empty($_SESSION["cart_item"])) {
				foreach ($_SESSION["cart_item"] as $k => $v) {
					if ($_GET["product_id"] == $k)
						unset($_SESSION["cart_item"][$k]);
					if (empty($_SESSION["cart_item"]))
						unset($_SESSION["cart_item"]);
					unset($_SESSION["order_price"]);
				}
			}
			$db_handle->dropqueryparam();
			break;
		case "empty":
			unset($_SESSION["cart_item"]);
			unset($_SESSION["order_price"]);
			$db_handle->dropqueryparam();

			break;
		case "checkout":
			if (!empty($_SESSION["cart_item"])) {
				$currIndex = $db_handle->findIndexTable("orders");
				$order_sql = "INSERT INTO `orders` (`order_id`, `date`, `time`, `status`, `customer_id`, `seller_id`, `order_cost`) VALUES ('" . $currIndex . "','" . date("Y-m-d") . "','" . date("H:i:s") . "','pending','" . $_SESSION["user-id"] . "','1','" . $_SESSION["order_price"] . "')";
				$db_handle->executeQuery($order_sql);
				foreach ($_SESSION["cart_item"] as $pid => $prdct) {
					$order_product_sql = "INSERT INTO `order_productsandaddons` (`order_id`, `product_id`, `name`, `product_id2`, `quantity`) VALUES ('" . $currIndex . "','" . $pid . "','" . $prdct['addon-id'] . "','" . $pid . "','" . $prdct['quantity'] . "')";
					$db_handle->executeQuery($order_product_sql);
				}
				unset($_SESSION["cart_item"]);
				unset($_SESSION["coupon"]);
				unset($_SESSION["order_price"]);
			}
			$db_handle->dropqueryparam();
			break;
		case "logout":
			unset($_SESSION["user-id"]);
			unset($_SESSION["user-level"]);
			unset($_SESSION["cart_item"]);
			unset($_SESSION["coupon"]);
			unset($_SESSION["order_price"]);
			unset($_SESSION["favProducts"]);
			unset($_SESSION["account-info"]);
			session_destroy();
			$db_handle->dropqueryparam();
			break;
		case "login":
			$_SESSION["user-id"] = '1';
			$_SESSION["user-level"] = '1';
			$_SESSION["favProducts"] = array();
			$tempfav = $db_handle->selectQuery("SELECT * FROM accinfo_favorites WHERE customer_id='" . $_SESSION["user-id"] . "' and customer_level='" . $_SESSION["user-level"] . "'");
			foreach ($tempfav as $key => $value) {
				$_SESSION["favProducts"][$value["favorites"]] = $value["favorites"];
			}
			$_SESSION["account-info"] = array();
			$tempaccinfo = $db_handle->selectQuery("SELECT * FROM customer, accinfo WHERE customer.customer_id = accinfo.customer_id and accinfo.customer_id ='" . $_SESSION["user-id"] . "' and customer_level='" . $_SESSION["user-level"] . "'");
			$_SESSION["account-info"] = $tempaccinfo[0];
			$db_handle->dropqueryparam();
			break;
		case "switch-favorite":
			if (in_array($_GET["product_id"], $_SESSION["favProducts"])) {
				unset($_SESSION["favProducts"][$_GET["product_id"]]);
				$db_handle->executeQuery("DELETE FROM accinfo_favorites WHERE customer_id='" . $_SESSION["user-id"] . "' and customer_level='" . $_SESSION["user-level"] . "' and favorites='" . $_GET["product_id"] . "'");
			} else {
				$_SESSION["favProducts"][$_GET["product_id"]] = $_GET["product_id"];
				$db_handle->executeQuery("INSERT INTO `accinfo_favorites` (`favorites`, `customer_level`, `customer_id`) VALUES ('" . $_GET["product_id"] . "','" . $_SESSION["user-level"] . "','" . $_SESSION["user-id"] . "')");
			}
			$db_handle->dropqueryparam();
			break;
		case "addcoupon":
			if (isset($_POST["coupon-id"])) {
				$_SESSION["coupon"] = array();
				$coupon_query = $db_handle->selectQuery("SELECT * FROM coupons WHERE customer_id='" . $_SESSION["user-id"] . "' and coupon_id='" . $_POST["coupon-id"] . "'");
				if (empty($coupon_query)) {
					$_SESSION["coupon"]["status"] = false;
				} else {
					$_SESSION["coupon"]["status"] = true;
					$_SESSION["coupon"] += $coupon_query[0];
				}
			}
			$db_handle->dropqueryparam();
			break;
		case "removecoupon":
			unset($_SESSION["coupon"]);
			$db_handle->dropqueryparam();
			break;
	}
}
?>
<HTML>

<HEAD>
	<TITLE>Browse Items</TITLE>
	<link href="./style.css" type="text/css" rel="stylesheet" />
</HEAD>

<BODY>
	<button onclick="document.location='?action=login'">login</button>
	<button onclick="document.location='?action=logout'">logout</button>
	<div class="topnav">
		<a class="active" href=".">Browse</a>
		<a href="./profile.php">Profile</a>
		<a href="./customer_dashboard.php">Customer Dashboard</a>
		<a href="./seller-dashboard.php">Seller Dashboard</a>
	</div>
	<?php if (isset($_SESSION["user-id"])) {
		$db_handle->findIndexTable("orders"); ?>
		<div id="shopping-cart">
			<div class="txt-heading">Shopping Cart</div>

			<a id="btnEmpty" href="?action=empty">Empty Cart</a>
			<?php
			if (isset($_SESSION["cart_item"])) {
				$total_quantity = 0;
				$total_price = 0;
				$discounted_price = 0;
			?>
				<table class="tbl-cart" cellpadding="10" cellspacing="1">
					<tbody>
						<tr>
							<th style="text-align:left;">Name</th>
							<th style="text-align:left;" width="5%">Product ID</th>
							<th style="text-align:left;" width="10%">Addon</th>
							<th style="text-align:right;" width="5%">Quantity</th>
							<th style="text-align:right;" width="10%">Unit Price</th>
							<th style="text-align:right;" width="10%">Price</th>
							<th style="text-align:center;" width="5%">Remove</th>
						</tr>
						<?php
						foreach ($_SESSION["cart_item"] as $key => $value) {
							if (isset($_POST["addon-name-" . $key])) {
								$addons = $db_handle->selectQuery("SELECT * FROM addon WHERE product_id='" . $key . "'");
								$_SESSION["cart_item"][$key]["addon-id"] = $_POST["addon-name-" . $key];
							}
						}
						foreach ($_SESSION["cart_item"] as $key => $value) {
							if (isset($_POST["addon-name-" . $key])) {
								$addon_price = $db_handle->selectQuery("SELECT * FROM addon WHERE product_id='" . $key . "' and name ='" . $value['addon-id'] . "'");
								$_SESSION["cart_item"][$key]["addon-price"] = $addon_price[0]["price"];
							}
						}

						foreach ($_SESSION["cart_item"] as $item) {
							$addons = $db_handle->selectQuery("SELECT * FROM addon WHERE product_id='" . $item["product_id"] . "'");
							$item_price = ($item["price"] + $item["addon-price"]) * $item["quantity"];
						?>
							<tr>
								<td><img src="<?php echo $item["image"]; ?>" class="cart-item-image" /><?php echo $item["name"]; ?></td>
								<td><?php echo $item["product_id"]; ?></td>
								<td>
									<form method="POST"> <select name="addon-name-<?php echo $item["product_id"]; ?>" id="addon-name-<?php echo $item["product_id"]; ?>" class="addon-name" onchange='this.form.submit();'> <?php if (!empty($addons)) {
																																																								foreach ($addons as $key => $value) { ?> <option <?php if ($value["name"] == "None") {
																																																																						echo "selected";
																																																																					} ?> value="<?php echo $value["name"]; ?>"><?php echo $value["name"] ?></option><?php }
																																																																																							} ?> </select> </form>
								</td>
								<td style="text-align:right;"><?php echo $item["quantity"]; ?></td>
								<td style="text-align:right;"><?php echo "৳ " . $item["price"];
																if ($item["addon-id"] != "None") {
																	echo " + " . $item["addon-price"];
																} ?></td>
								<td style="text-align:right;"><?php echo "৳ " . number_format($item_price, 2); ?></td>
								<td style="text-align:center;"><a href="?action=remove&product_id=<?php echo $item["product_id"]; ?>" class="btnRemoveAction"><img src="icon-delete.png" alt="Remove Item" /></a></td>
							</tr>
						<?php
							$total_quantity += $item["quantity"];
							$total_price += (($item["price"] + $item["addon-price"]) * $item["quantity"]);
						}
						if (isset($_SESSION["coupon"]) and $_SESSION["coupon"]["status"] and $_SESSION["coupon"]["min_spend"] > $total_price) {
							$_SESSION["coupon"]["status"] = false;
						}
						if (isset($_SESSION["coupon"]) and $_SESSION["coupon"]["status"]) {
							$discounted_price = $total_price - $_SESSION["coupon"]["amount"];
						} else {
							$discounted_price = $total_price;
						}
						$_SESSION["order_price"] = $discounted_price;
						?>

						<tr>
							<td colspan="3" align="right">Total:</td>
							<td align="right"><?php echo $total_quantity; ?></td>
							<td align="right" colspan="2"><?php if (isset($_SESSION["coupon"]) and $_SESSION["coupon"]["status"]) {
																echo "<del>৳ " . number_format($total_price, 2) . "</del>";
															} ?><strong><?php echo "৳ " . number_format($discounted_price, 2); ?></strong></td>
							<td><button class="btnCheckout" onclick="document.location='?action=checkout'">Checkout</button></td>
						</tr>
						<tr>
							<td align="middle" colspan="7"><strong>Coupon: </strong><?php if (!isset($_SESSION["coupon"])) { ?><form method="post" action="?action=addcoupon" style="display:inline-block;"><input type="text" class="coupon" name="coupon-id"><input type="submit" value="Submit" class="btnCoupon" /></form><?php } else {
																																																																														if ($_SESSION["coupon"]["status"]) {
																																																																															echo "success";
																																																																														} else {
																																																																															echo "invalid";
																																																																														} ?> <form method="post" action="?action=removecoupon" style="display:inline-block;"><input type="submit" value="Remove Coupon" class="btnCoupon" /></form> <?php } ?></td>
						</tr>
					</tbody>
				</table>
			<?php
			} else {
			?>
				<div class="no-records">Your Cart is Empty</div>
			<?php
			}
			?>
		</div><?php } ?>

	<div id="product-grid">
		<div class="txt-heading">Bride</div>
		<?php
		$product_array = $db_handle->selectQuery("SELECT * FROM product WHERE category='Bride' ORDER BY product_id ASC");
		if (!empty($product_array)) {
			foreach ($product_array as $key => $value) {
		?>
				<div class="product-item">
					<form method="post" action="?action=add&product_id=<?php echo $product_array[$key]["product_id"]; ?>">
						<div class="product-image"><img src="<?php echo $product_array[$key]["image"]; ?>"></div>
						<?php if (isset($_SESSION["user-id"])) { ?><div class="favorite-switch" style="--star-color:<?php if (in_array($product_array[$key]['product_id'], $_SESSION["favProducts"])) echo "red";
																													else echo "black" ?>;" onclick="document.location='?action=switch-favorite&product_id=<?php echo $product_array[$key]['product_id']; ?>'"></div><?php } ?>
						<div class="product-tile-footer">
							<div class="product-title"><?php echo $product_array[$key]["name"]; ?></div>
							<div class="product-price"><?php echo "৳" . $product_array[$key]["price"]; ?></div>
							<?php if (isset($_SESSION["user-id"])) { ?><div class="cart-action"><input type="text" class="product-quantity" name="quantity" value="1" size="2" /><input type="submit" value="Add to Cart" class="btnAddAction" /></div><?php } ?>
							<div class="product-rating">
								<div class="Stars" style="--rating: <?php echo ($db_handle->product_rating($product_array[$key]['product_id'])); ?>;"></div>
							</div>
						</div>
					</form>
				</div>
		<?php
			}
		}
		?>
	</div>
	<div id="product-grid">
		<div class="txt-heading">Groom</div>
		<?php
		$product_array = $db_handle->selectQuery("SELECT * FROM product WHERE category='Groom' ORDER BY product_id ASC");
		if (!empty($product_array)) {
			foreach ($product_array as $key => $value) {
		?>
				<div class="product-item">
					<form method="post" action="?action=add&product_id=<?php echo $product_array[$key]["product_id"]; ?>">
						<div class="product-image"><img src="<?php echo $product_array[$key]["image"]; ?>"></div>
						<?php if (isset($_SESSION["user-id"])) { ?><div class="favorite-switch" style="--star-color:<?php if (in_array($product_array[$key]['product_id'], $_SESSION["favProducts"])) echo "red";
																													else echo "black" ?>;" onclick="document.location='?action=switch-favorite&product_id=<?php echo $product_array[$key]['product_id']; ?>'"></div><?php } ?>
						<div class="product-tile-footer">
							<div class="product-title"><?php echo $product_array[$key]["name"]; ?></div>
							<div class="product-price"><?php echo "৳" . $product_array[$key]["price"]; ?></div>
							<?php if (isset($_SESSION["user-id"])) { ?><div class="cart-action"><input type="text" class="product-quantity" name="quantity" value="1" size="2" /><input type="submit" value="Add to Cart" class="btnAddAction" /></div><?php } ?>
							<div class="product-rating">
								<div class="Stars" style="--rating: <?php echo ($db_handle->product_rating($product_array[$key]['product_id'])); ?>;"></div>
							</div>
						</div>
					</form>
				</div>
		<?php
			}
		}
		?>
	</div>
	<div id="product-grid">
		<div class="txt-heading">Others</div>
		<?php
		$product_array = $db_handle->selectQuery("SELECT * FROM product WHERE category='Others' ORDER BY product_id ASC");
		if (!empty($product_array)) {
			foreach ($product_array as $key => $value) {
		?>
				<div class="product-item">
					<form method="post" action="?action=add&product_id=<?php echo $product_array[$key]["product_id"]; ?>">
						<div class="product-image"><img src="<?php echo $product_array[$key]["image"]; ?>"></div>
						<?php if (isset($_SESSION["user-id"])) { ?><div class="favorite-switch" style="--star-color:<?php if (in_array($product_array[$key]['product_id'], $_SESSION["favProducts"])) echo "red";
																													else echo "black" ?>;" onclick="document.location='?action=switch-favorite&product_id=<?php echo $product_array[$key]['product_id']; ?>'"></div><?php } ?>
						<div class="product-tile-footer">
							<div class="product-title"><?php echo $product_array[$key]["name"]; ?></div>
							<div class="product-price"><?php echo "৳" . $product_array[$key]["price"]; ?></div>
							<?php if (isset($_SESSION["user-id"])) { ?><div class="cart-action"><input type="text" class="product-quantity" name="quantity" value="1" size="2" /><input type="submit" value="Add to Cart" class="btnAddAction" /></div><?php } ?>
							<div class="product-rating">
								<div class="Stars" style="--rating: <?php echo ($db_handle->product_rating($product_array[$key]['product_id'])); ?>;"></div>
							</div>
						</div>
					</form>
				</div>
		<?php
			}
		}
		?>
	</div>
	<script type="text/javascript">
		<?php if (isset($_SESSION["cart_item"])) {
			foreach ($_SESSION["cart_item"] as $key => $value) { ?>
				document.getElementById('addon-name-<?php echo $value['product_id'] ?>').value = "<?php echo $value['addon-id']; ?>";
		<?php }
		} ?>
	</script>
</BODY>

</HTML>