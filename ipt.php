<?php
if(empty($_GET['nums'])) {
	echo 'To track:<br />';
	echo 'Add ?nums=:ID,:COUNTRY,:POSTALCODE to the url<br />';
	echo 'Example to track multiple shipments:<br />';
	echo '?nums=:ID,:COUNTRY,:POSTALCODE;:ID2,:COUNTRY2,:POSTALCODE2';
	die();
}
echo '<meta http-equiv="refresh" content="15">';
$numbers = explode(';', $_GET['nums']);
$i = 0;
foreach($numbers as $num) {
	$i++;
	list($code, $country, $postalcode) = explode(',', $num);
	$jsons[$i]['result'] = json_decode(file_get_contents('https://www.internationalparceltracking.com/api/shipment/?language=en&barcode='.$code.'&country='.$country.'&postalCode='.$postalcode));
	$jsons[$i]['code'] = $code;
}
foreach($jsons as $json) {
	echo '<h3>'.$json['code'].'</h3>';
	if(!isset($json['result']->showEta)) {
		echo 'Not found (yet)';
		continue;
	}
	echo '<table border="1">';
	if($json['result']->showEta == true) echo '<tr><td>Expected delivery time:</td><td>'.date('Y-m-d H:i:s', strtotime($json['result']->deliveryExpectation->deliveryDateFrom) + 60 * 60).' tot '.date('Y-m-d H:i:s', strtotime($json['result']->deliveryExpectation->deliveryDateUntil) + 60 * 60).'</td></tr>';
	foreach($json['result']->amounts as $am) {
		if($am->amountType == 'CashOnDelivery' && $am->amount != '0.0') {
			echo '<tr><td>Pay on delivery</td><td>'.$am->amount.' '.$am->currency.'</td></tr>';
		}
	}
	if(isset($json['result']->shipmentStep)) {
		$st = $json['result']->shipmentStep;
		$steps = array();
		if($st >= 1) $steps[] = 'Shipping information received';
		if($st >= 2) $steps[] = 'In transit';
		if($st >= 3) $steps[] = 'Arrived in country of destination';
		if($st >= 4) $steps[] = 'Out for delivery';
		if($st >= 5) $steps[] = 'Delivered';
		echo '<tr><td>Steps</td>';
		foreach($steps as $st) {
			echo '	<td>'.$st.'</td>';
		}
		echo '</tr>';
	}
	
	echo '</table>';
	echo '<table border="1" width="100%">';
	foreach($json['result']->trackingEvents as $event) {
		echo '<tr>';
		echo '	<td>'.date('Y-m-d H:i:s', strtotime($event->dateTime) + 60 * 60).'</td>';
		if($event->location == null) echo '	<td>nol</td>';
		else echo '	<td>'.$event->location.'</td>';
		echo '	<td>'.$event->description.'</td>';
		echo '</tr>';
	}
	echo '</table>';
}
echo 'nol = no location';
