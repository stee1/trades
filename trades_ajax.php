<?php
define("DEBUG_MODE", true);

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case '-':
            change_price(-1, false);
            break;
        case '+':
            change_price(1, false);
            break;
		case 'MIN':
			change_price(1, true);
			break;
		case 'сравнять':
			change_price("equal", false);
			break;
    }
}

function change_price($sign, $isMin) {
	$game_id = $_POST['game_id'];
	$api_key = $_POST['api_key'];
	$weapons_ids = $_POST['weapons_ids'];
	$old_price = $_POST['item_your_offer_price'];
	$name = trim($_POST['item_name']);
	$weapons_ids_array = explode(";", $weapons_ids);
	$change_price_value = round($_POST['change_price_value'], 2);
	if ($sign == "equal")
	{
		$new_price_rubl = $_POST['change_price_value'];
	}
	else 
	{
		$new_price_rubl = $old_price + ($change_price_value*$sign);
	}
	$new_price_rubl = round($new_price_rubl, 2);
	if ($new_price_rubl < 1) {
		$new_price_rubl = 1;
	}
	$new_price = $new_price_rubl * 100;
	
	if (!DEBUG_MODE) {
		$log  = "Time: ".date("F j, Y, g:i a").PHP_EOL.
		"Server remote addr: ".$_SERVER['REMOTE_ADDR'].PHP_EOL.
        "API key: ".$api_key.PHP_EOL.
        "Game id: ".$game_id.PHP_EOL.
		"Clicked item name: ".$name.PHP_EOL;
		
		foreach($weapons_ids_array as $id) {	
			$log .= "Item id: ".$id.PHP_EOL.
			"Item old price: ".$old_price.PHP_EOL.
			"Item new price: ".($new_price/100).PHP_EOL;
			
			if ($game_id == 0) {
				$json_url = "https://market.dota2.net/api/SetPrice/{$id}/{$new_price}/?key={$api_key}";
			}
			else if ($game_id == 1) {
				$json_url = "https://csgo.tm/api/SetPrice/{$id}/{$new_price}/?key={$api_key}";
			}
			
			$content = file_get_contents($json_url);
			$json = json_decode($content, true);
		
			if (isset($json['success'])) {
				if ($json['success']) {
    
					
					$str = $sign < 0 ? "уменьшилась" : "увеличилась"; 
					if ($isMin) $str = "уменьшилась";
					if ($sign == "equal") {
						echo "Цена на {$name} сравнялась со ценой, установленной другом и теперь составляет {$new_price_rubl}; ";
					}
					else {
						echo "Цена на {$name} {$str} c {$old_price} на {$change_price_value} "
					." и теперь составляет {$new_price_rubl}; ";
					}
		
				}
			}
			else {
				$str = $json['error'];
				echo html_entity_decode(str_replace('\u','&#x',$str), ENT_NOQUOTES,'UTF-8');
				exit();
			}	
		}
        $log .= "-------------------------".PHP_EOL;
		
		file_put_contents('./trades_log_'.date("j.n.Y").'.txt', $log, FILE_APPEND);
		
		exit;
	}
    else {
		foreach($weapons_ids_array as $id) {		
			if ($game_id == 0) {
				$json_url = "https://market.dota2.net/api/SetPrice/{$id}/{$new_price}/?key={$api_key}";
				//echo $json_url;
			}
			else if ($game_id == 1) {
				$json_url = "https://csgo.tm/api/SetPrice/{$id}/{$new_price}/?key={$api_key}";
				//echo $json_url;
			}
		}
		
        $name = trim($_POST['item_name']);
		$str = $sign < 0 ? "уменьшилась" : "увеличилась"; 
		if ($isMin) $str = "уменьшилась";
		if ($sign == "equal")
		{
			echo "Цена на {$name} сравнялась со ценой, установленной другом и теперь составляет {$new_price_rubl}; ";
		}
		else 
		{
			echo "Цена на {$name} (game_id={$game_id}) {$str} c {$old_price} на {$change_price_value} "
				." и теперь составляет {$new_price_rubl} ({$new_price}); ";
		}
    }
}
?>