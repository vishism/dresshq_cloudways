<?php
require_once(__DIR__ . "/support.php");
require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceOrder.php");
require_once(__DIR__ . "/../model/AffinityProduct.php");
require_once(__DIR__ . "/../model/AffinityOrder.php");
require_once(__DIR__ . "/../service/AffinityBackendService.php");
$arrFees = AffinityBackendService::getSellerCosts();

if(!is_array($arrFees) || !is_array($arrFees['data']['AccountEntry'])) {
	echo "No Entries Found";
	exit();
}
?>

<div class="ebayaffinity-header">
	<span>eBay Fees</span>
	<em><?php print count($arrFees['data']['AccountEntry']) ?> entries</em>
</div>



<div class="ebayaffinity-inv-block">
		<table class="ebayaffinity-inv-table">
			<tbody><tr>
				<th>Date</th>
				<th class="ebayaffinity-not-mobile">Description</th>
				<th class="ebayaffinity-not-mobile">Gross Amount</th>
				<th class="ebayaffinity-not-mobile">Net Amount</th>
				<th class="ebayaffinity-not-mobile">Related to</th>
			</tr>
			
			<?php
			$arrFees['data']['AccountEntry'] = array_reverse($arrFees['data']['AccountEntry']);
			foreach($arrFees['data']['AccountEntry'] as $arrAccountEntry):
			?>	
				<tr>
					<td>
					<?php 
						print date("d/M/Y", $arrAccountEntry['Date'] / 1000);
					?>
					</td>
					<td>
					<?php 
						print $arrAccountEntry['Description'];
					?>
					</td>
					<td>
					<?php 
						echo $arrAccountEntry['GrossDetailAmount']['currencyID'] . " " . number_format((float) $arrAccountEntry['GrossDetailAmount']['value'], 2, '.', ',');
					?>
					</td>
					<td>
					<?php 
						echo $arrAccountEntry['NetDetailAmount']['currencyID'] . " " . number_format((float) $arrAccountEntry['NetDetailAmount']['value'], 2, '.', ',');
					?>
					</td>
					<td>
					<?php 
						$strLinkHtml = "";
						if(!empty($arrAccountEntry['ItemID'])) {
							$productId = AffinityProduct::getProductIdAssociatedToListing($arrAccountEntry['ItemID']);
							
							if(!empty($productId)) {
								$productUrl = admin_url() . "/admin.php?page=ebay-sync-inventory&id=$productId";
								$strLinkHtml = "Generated by <a href='$productUrl' target='_blank'>Product $productId</a> ";
							}
							
							if(!empty($arrAccountEntry['TransactionID'])) {
								$orderId = AffinityOrder::getOrderIdAssociatedWithItemAndTransactionID($arrAccountEntry['ItemID'], $arrAccountEntry['TransactionID']);
								
								if(!empty($orderId)) {
									$orderUrl = AffinityEcommerceOrder::getEditOrderLink($orderId);
									$strLinkHtml .= "- <a href='$orderUrl' target='_blank'>Order $orderId</a>";
								}
							}
						}
						
						echo $strLinkHtml;
					?>
					</td>
				</tr>
			<?php
			endforeach;
			?>
			
		</tbody>
	</table>
</div>

<?php
exit();
?>
<div class="ebayaffinity-fees">	
	
	<table>
		<thead>
			<tr>
				<th class="feeDate">Date</th>
				<th class="feeDescription">Description</th>
				<th class="feeGrossAmount">Gross Amount</th>
				<th class="feeNetAmount">Net Amount</th>
				<th class="feeRelatedTo">Related To</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($arrFees['data']['AccountEntry'] as $arrAccountEntry):
			?>	
				<tr>
					<td>
					<?php 
						print date("d/M/Y", $arrAccountEntry['Date'] / 1000);
					?>
					</td>
					<td>
					<?php 
						print $arrAccountEntry['Description'];
					?>
					</td>
					<td>
					<?php 
						echo $arrAccountEntry['GrossDetailAmount']['value'] . " " . $arrAccountEntry['GrossDetailAmount']['currencyID'];
					?>
					</td>
					<td>
					<?php 
						echo $arrAccountEntry['NetDetailAmount']['value'] . " " . $arrAccountEntry['NetDetailAmount']['currencyID'];
					?>
					</td>
					<td>
					<?php 
						$productId = AffinityProduct::getProductIdAssociatedToListing($arrAccountEntry['ItemID']);
						$productUrl = admin_url() . "/admin.php?page=ebay-sync-inventory&id=$productId";
						
						if(!empty($productId)) {
							echo "Related to <a href='$productUrl'>Product ID $productId</a>";
						}
					?>
					</td>
				</tr>
			<?php
			endforeach;
			?>
		</tbody>
	</table>
</div>