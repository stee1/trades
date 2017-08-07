<?php
define("DEBUG_MODE", false);

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case '-':
            change_price(-1);
            break;
        case '+':
            change_price(1);
            break;
		case 'MAAX':
			change_price(1);
			break;
		case 'сравнять':
			change_price("equal");
			break;
    }
}

function change_price($sign) {
	$game_id = $_POST['game_id'];
	$api_key = $_POST['api_key'];
	$id1 = $_POST['item_id1'];
	$id2 = $_POST['item_id2'];
	$change_price_value = round($_POST['change_price_value'], 2);
	if ($sign == "equal")
	{
		$new_price_rubl = $_POST['change_price_value'];
	}
	else 
	{
		$new_price_rubl = $_POST['item_your_offer_price'] + ($change_price_value*$sign);
	}
	$new_price = $new_price_rubl * 100;
	
	if (!DEBUG_MODE) {
		if ($game_id == 0) {
			$json_url = "https://market.dota2.net/api/UpdateOrder/{$id1}/{$id2}/{$new_price}/?key={$api_key}";
		}
		else if ($game_id == 1) {
			$json_url = "https://csgo.tm/api/UpdateOrder/{$id1}/{$id2}/{$new_price}/?key={$api_key}";
		}
		$content = file_get_contents($json_url);
		$json = json_decode($content, true);
		if (isset($json['success'])) {
			if ($json['success']) {
    
		$name = trim($_POST['item_name']);
		$str = $sign < 0 ? "уменьшилась" : "увеличилась"; 
		if ($sign == "equal")
		{
			echo "Цена на {$name} сравнялась со ценой, установленной другом и теперь составляет {$new_price_rubl}";
		}
		else 
		{
			echo "Цена на {$name} {$str} c {$_POST['item_your_offer_price']} на {$change_price_value} "
				." и теперь составляет {$new_price_rubl}";
		}
		exit;
		}
		}
		else {
			$str = $json['error'];
			echo html_entity_decode(str_replace('\u','&#x',$str), ENT_NOQUOTES,'UTF-8');
			exit();
		}
	}
    else {
		echo "GAME ID=".$game_id;
        $name = trim($_POST['item_name']);
		$str = $sign < 0 ? "уменьшилась" : "увеличилась"; 
		if ($sign == "equal")
		{
			echo "Цена на {$name} сравнялась со ценой, установленной другом и теперь составляет {$new_price_rubl}";
		}
		else 
		{
			echo "Цена на {$name} {$str} c {$_POST['item_your_offer_price']} на {$change_price_value} "
				." и теперь составляет {$new_price_rubl}";
		}
    }
}
?>