<?php
//ПРОДАЖА
define("SHOW_LOAD_TIME", false);

//WDsA4XI332RR75jk1teU8kpItcTif8i
//56nkZ42Cnw55w8451r3nhgf65Q72UlV
$load_time_start = microtime(1) ;
$api_key = $_REQUEST['api_key'];
$friend_api_key = $_REQUEST['friend_api_key'];
echo "<input type='hidden' id='api_key' name='api_key' value='{$api_key}' />";
$columns_count = $_REQUEST['columns_count'];

$json_url = "https://market.dota2.net/api/Trades/?key={$api_key}";
$content = file_get_contents($json_url);
$json_all = array();
$json_all[0] = json_decode($content, true);
$json_url = "https://market.csgo.com/api/Trades/?key={$api_key}";
$content = file_get_contents($json_url);
$json_all[1] = json_decode($content, true);

$friend_json_url = "https://market.dota2.net/api/Trades/?key={$friend_api_key}";
$friend_content = file_get_contents($friend_json_url);
$friend_json_all = array();
$friend_json_all[0] = json_decode($friend_content, true);
$friend_json_url = "https://market.csgo.com/api/Trades/?key={$friend_api_key}";
$friend_content = file_get_contents($friend_json_url);
$friend_json_all[1] = json_decode($friend_content, true);


$i=0;
$friend_has_this_item = array();
$friends_price = array();
$myDatatable = array();

for($k=0;$k<2;$k++) {
	if ($k == 0) {
		$website = "market.dota2.net";
		$images_storage = "dota2.net";
	}
	else if ($k == 1) {
		$website = "market.csgo.com";
		$images_storage = "csgo.com";
	}

if (isset($json_all[$k])) {
	foreach($json_all[$k] as $item) {	
		
		$i_name = $item['i_market_name'];
		$i_classid = $item['i_classid'];
		$i_instanceid = $item['i_instanceid'];
		$ui_id = $item['ui_id'];
		$ui_price = $item['ui_price'];
		
		
		$file = file_get_contents('./trades_info_'.date("j.n.Y").'.txt');
		//var_dump($file);
		$log = "";
		$add_wepon_to_info_log = false;
		$pos = strrpos($file , $ui_id);
		if ($pos === false) { 
			$add_wepon_to_info_log = true;
			
		}
		
		while (isset($myDatatable[$i_name]) && ($ui_price != $myDatatable[$i_name]['ui_price'])) {
			$i_name .= " ";
		}
		
		if(isset($myDatatable[$i_name])) {
			//echo "for {$i_name} price {$ui_price} dt price {$myDatatable[$i_name]['ui_price']}</br>";
			//echo "add to existing {$i_name} price {$ui_price} dt price {$myDatatable[$i_name]['ui_price']}</br>";
			$items_count++;
			$weapons_ids .= ";".$ui_id;
		}
		else {
			//echo "new weapon {$i_name} price {$ui_price}</br>";
			$items_count = 1;
			$weapons_ids = $ui_id;
		}
		
		
		$i_img = "https://cdn.".$images_storage."/item_{$i_classid}_{$i_instanceid}.png";
		$i_url = "https://".$website."/item/{$i_classid }-{$i_instanceid}-".str_replace(" ", "+", $i_name)."/";
		
		//сравнить предметы с предметами друга
		$friend_has_this_item[$i] = false;
		$friends_price[$i] = -1;
		if (!isset($friend_json_all[$k]['error'])) {
			foreach($friend_json_all[$k] as $friend_item) {
				$i_friend_classid = $friend_item['i_classid'];
				$i_friend_instanceid = $friend_item['i_instanceid'];
				if ($i_classid == $i_friend_classid && $i_instanceid == $i_friend_instanceid) {
					$friend_has_this_item[$i] = true; 
					$friends_price[$i] = $friend_item['ui_price'];
				}
			}
		}
		
		//получить инфу о предмете
		$item_json_url = "https://".$website."/api/ItemInfo/{$i_classid}_{$i_instanceid}/ru/?key={$api_key}";
		$item_content = file_get_contents($item_json_url);
		$item_json = json_decode($item_content, true);
		
		$min_price = $item_json['min_price'];
		//offers
		//$offer_min_o_price = $item_json['buy_offers'][0]['o_price'];
		$offers_table = "<table>
				<tr>
					<td align='center'>Цена</td>
					<td align='center'>Предлож.</td>
				</tr>";
		if (!isset($item_json['offers'])) {
			$offers_table = "ПРЕДЛОЖ. НЕТ";
		}
		else {
			for ($j=0; $j < count($item_json['offers']) &&  $j<4; $j++) {
				$offers_table_price = $item_json['offers'][$j]['price'] / 100;
				$offers_table .= "
					<tr>
						<td align='center'>{$offers_table_price}</td>
						<td align='center'>{$item_json['offers'][$j]['count']}</td>
					</tr>";
			
			}
		$offers_table .= "</table>";
		}
		
		$row = array();
		$row['i_name']= $i_name;
		//$row['array_ui_ids']
		$row['i_classid']= $i_classid;
		$row['i_instanceid']= $i_instanceid;
		$row['i_img']= $i_img;
		$row['i_url']= $i_url;
		$row['ui_id']= $ui_id;
		$row['ui_price']= $ui_price;
		$row['min_price']= $min_price;
		$row['offers_table']= $offers_table;
		$row['in_friends_orders']=$friend_has_this_item[$i];
		$row['friends_price'] = $friends_price[$i];
		$row['game_id'] = $k;
		$row['weapons_ids'] = $weapons_ids;
		$row['items_count'] = $items_count;
		
		if ($add_wepon_to_info_log) {
			$log = "ID: {$ui_id}; Name: {$i_name}; URL: {$i_url}".PHP_EOL;
			file_put_contents('./trades_info_'.date("j.n.Y").'.txt', $log, FILE_APPEND);
		}
		
		$myDatatable[$i_name] = $row;
		$i++;
	}
}
else {
echo "no trades";
exit;
}
}

function fill_data($myDatatable) {
	//for($i = 0; $i < count($myDatatable); $i++){
	foreach($myDatatable as $weapon_info) {
		$min_offer = $weapon_info['min_price'] / 100;
		
		$ui_price = $weapon_info['ui_price'];
		$ui_id = $weapon_info['ui_id'];
		$friends_price = $weapon_info['friends_price'] / 100;
		
	echo "
	<input type='hidden' id='item_name' name='item_name' value='{$weapon_info['i_name']}' />
	<input type='hidden' id='item_id1' name='item_id1' value='{$weapon_info['i_classid']}' />
	<input type='hidden' id='item_id2' name='item_id2' value='{$weapon_info['i_instanceid']}' />
	<input type='hidden' id='item_min_offer' name='item_min_offer' value='{$min_offer}' />
	<input type='hidden' id='item_your_offer_price' name='item_your_offer_price' value='{$ui_price}' />
	<input type='hidden' id='item_ui_id' name='item_ui_id' value='{$ui_id}' />
	<input type='hidden' id='friends_price' name='friends_price' value='{$friends_price}' />
	<input type='hidden' id='game_id' name='game_id' value='{$weapon_info['game_id']}' />
	<input type='hidden' id='weapons_ids' name='weapons_ids' value='{$weapon_info['weapons_ids']}' />
	";	
	 }
}

function print_table($column_id, $myDatatable, $columns_count) {
	$string_result= "<table border='1'>
                  <tr>
                     <th>Шмотка</th>
                     <th>Предлож.</th>
					 <th>Ты поставил</th>
					 <th>Количество</th>
                     <th>РУБЛИ</th>
                  </tr> ";
				  
    $bgRED = "FA8072";
    $bgGREEN = "ADFF2F";
	$bgBLUE = "2BE7FF";
	$bgGOLD = "FFD700";
                   					 
    //for($i = $column_id; $i < count($myDatatable); $i += $columns_count){
		$i = $column_id;
	foreach ($myDatatable as $weapon_info) {
		$my_price = $weapon_info['ui_price'] * 100;
		if ($weapon_info['min_price'] < $my_price) {
			$bgColor_col0 = $bgRED;
			$bgColor_col1 = $bgRED;
			$bgColor_col2 = $bgRED;
			$bgColor_col3 = $bgRED;
			$bgColor_col4 = $bgRED;
			
			if ($weapon_info['min_price'] + 50 >= $my_price) {
				$bgColor_col2 = $bgBLUE;
				$bgColor_col3 = $bgBLUE;
				$bgColor_col4 = $bgBLUE; 
			}
		}
		else {
			$bgColor_col0 = $bgGREEN;
			$bgColor_col1 = $bgGREEN;
			$bgColor_col2 = $bgGREEN;
			$bgColor_col3 = $bgGREEN;
			$bgColor_col4 = $bgGREEN;
		}
		
		$str_friends_price = "";
		$str_button_price_level = "";
		
		if ($weapon_info['in_friends_orders']) {
			$friends_price = $weapon_info['friends_price']/100;
			$str_friends_price = "<br/>(цена друга: {$friends_price})";
			$str_button_price_level = "<br/><input type='button' class='button' name='{$i}' value='сравнять' />";
			$bgColor_col0 = $bgGOLD;
			$bgColor_col1 = $bgGOLD;
		}

		$ui_price = $weapon_info['ui_price'];
		
          $string_result .= "<tr>
		<td align='center' bgcolor='{$bgColor_col0}'>
			<a href='{$weapon_info['i_url']}'>{$weapon_info['i_name']}</br>
			<img src='{$weapon_info['i_img']}'width='150' height='100'/></a></td>
		<td align='center' bgcolor='{$bgColor_col1}'>{$weapon_info['offers_table']}</td>
		<td align='center' bgcolor='{$bgColor_col2}'>{$ui_price}
		{$str_friends_price}{$str_button_price_level}</td>
		<td align='center' bgcolor='{$bgColor_col3}'>{$weapon_info['items_count']}</td>
		<td align='center' bgcolor='{$bgColor_col3}'>
			<input type='button' class='button' name='{$i}' value='MIN' />({$i};{$weapon_info['i_name']})</br></br>
			<input type='number' id='{$i}' name='change_price_value' 
				style='text-align:center;width: 70px;' value='0.02' min='0' max='999,99' step='0.01'><br/>
			<input type='button' class='button' name='{$i}' value='-' />&emsp;
			<input type='button' class='button' name='{$i}' value='+' />
		</td>
		</tr>";
		$i++;
        }
    $string_result .= "</table>";
	
	return $string_result;
} 

function print_tables($columns_count, $myDatatable) {
	$string_result ="";
	$string_result .= "<td>";
		$string_result .= print_table(0, $myDatatable, $columns_count);
		$string_result .= "</td>";
	/*for ($i=0; $i<$columns_count; $i++) {
		$string_result .= "<td>";
		$string_result .= print_table($i, $myDatatable, $columns_count);
		$string_result .= "</td>";
	}*/
	
	return $string_result;
}
?>

<html>
  <head>
  <title>Продажи</title>
      <script>
         function reload_page() {
         	location.reload();
         }
         
         setTimeout(reload_page, 10000);
      </script>
	  
	  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
		
		<?php 
		
			$my_array = array();
			$my_array[0] = 1;
			$my_array[1] = 2; 
			?>
		
		<script type="text/javascript">
		
		$(document).ready(function(){
			$('.button').click(function(){
				var clickBtnValue = $(this).val();
				
				var item_name = document.getElementsByName('item_name');
				var item_your_offer_price = document.getElementsByName('item_your_offer_price');
				var change_price_value = document.getElementsByName('change_price_value');
				var game_id = document.getElementsByName('game_id');
				var weapons_ids = document.getElementsByName('weapons_ids');
				for (var i = 0; i < change_price_value.length; i++) {
					 if (change_price_value[i].id == this.name) {
						var change_price_value_cur = change_price_value[i].value;
						//alert("btn="+this.name+";id="+change_price_value[i].id+";val="+change_price_value[i].value);
					 }						
				 }
				if (clickBtnValue == 'MIN') {
					var min = document.getElementsByName('item_min_offer')[this.name].value;
					change_price_value_cur = (min - item_your_offer_price[this.name].value) - 0.01;
					//alert (min);
				}
				if (clickBtnValue == 'сравнять') {
					change_price_value_cur = document.getElementsByName('friends_price')[this.name].value;
				}
				var api_key = document.getElementById('api_key').value;
				
				
				var ajaxurl = 'trades_ajax.php',
				data =  {'action': clickBtnValue, 
						'item_name': item_name[this.name].value,
						'item_id1': item_id1[this.name].value,
						'item_id2': item_id2[this.name].value,
						'item_your_offer_price': item_your_offer_price[this.name].value,
						'item_ui_id': item_ui_id[this.name].value,
						'change_price_value': change_price_value_cur,
						'api_key': api_key,
						'game_id': game_id[this.name].value,
						'weapons_ids': weapons_ids[this.name].value};
				$.post(ajaxurl, data, function (response) {
					// Response div goes here.
					alert(response);
					reload_page();
				});
			});
		});
		</script>
		
   </head>
   <body>
    <input type="button" value="Покупки" id="btnTrades" 
onClick="document.location.href='orders.php?api_key=<?php echo $api_key ?>&friend_api_key=<?php echo $friend_api_key ?>&columns_count=<?php echo $columns_count ?>'" />
      <table >
         <caption>Таблица продаж</caption>
         <tr>
            <?php
			if (SHOW_LOAD_TIME) {
				$load_time = microtime(1) - $load_time_start;
				echo "php code: ".$load_time."<br/>";
			}
			echo print_tables($columns_count, $myDatatable);
			fill_data($myDatatable);
			if (SHOW_LOAD_TIME) {
				$load_time = microtime(1) - $load_time_start;
				echo "table draw: ".$load_time."<br/>";
			}
			?>
         </tr>
      </table>
   </body>
</html>