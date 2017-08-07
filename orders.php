<?php
ob_start(); // Initiate the output buffer
?>
<?php
//ПОКУПКИ
define("SHOW_LOAD_TIME", false);

//WDsA4XI332RR75jk1teU8kpItcTif8i
//56nkZ42Cnw55w8451r3nhgf65Q72UlV
$load_time_start = microtime(1);
$api_key = $_REQUEST['api_key'];
$friend_api_key = $_REQUEST['friend_api_key'];
echo "<input type='hidden' id='api_key' name='api_key' value='{$api_key}' />";
$columns_count = $_REQUEST['columns_count'];

$json_url = "https://market.dota2.net/api/GetOrders/?key={$api_key}";
$content = file_get_contents($json_url);
$json_all = array();
$json_all[0] = json_decode($content, true);
$json_url = "https://market.csgo.com/api/GetOrders/?key={$api_key}";
$content = file_get_contents($json_url);
$json_all[1] = json_decode($content, true);

$friend_json_url = "https://market.dota2.net/api/GetOrders/?key={$friend_api_key}";
$friend_content = file_get_contents($friend_json_url);
$friend_json_all = array();
$friend_json_all[0] = json_decode($friend_content, true);
$friend_json_url = "https://market.csgo.com/api/GetOrders/?key={$friend_api_key}";
$friend_content = file_get_contents($friend_json_url);
$friend_json_all[1] = json_decode($friend_content, true);


$i = 0;
$friend_has_this_item = array();
$friends_price = array();
$myDatatable = array();

for ($k = 0; $k < 2; $k++) {
    if ($k == 0) {
        $website = "market.dota2.net";
        $images_storage = "dota2.net";
    } else if ($k == 1) {
        $website = "market.csgo.com";
        $images_storage = "csgo.com";
    }

    if ($json_all[$k]['success']) {
        if ($json_all[$k]['Orders'] == "No orders") {
            $game_name = "";
            if ($k == 0) {
                $game_name = "доте";
            } else if ($k == 1) {
                $game_name = "каєс";
            }
            echo "<font size='3' color='red'>Заказов по {$game_name} нет</br></font>";
            //exit();
        }
        foreach ($json_all[$k]['Orders'] as $item) {
            $i_name = $item['i_market_name'];
            $i_classid = $item['i_classid'];
            $i_instanceid = $item['i_instanceid'];
            $o_price = $item['o_price'];

            $i_img = "https://cdn." . $images_storage . "/item_{$i_classid}_{$i_instanceid}.png";
            $i_url = "https://" . $website . "/item/{$i_classid }-{$i_instanceid}-" . str_replace(" ", "+", $i_name) . "/";

            //сравнить предметы с предметами друга
            $friend_has_this_item[$i] = false;
            $friends_price[$i] = -1;
            if (!isset($friend_json_all[$k]['error'])) {
                foreach ($friend_json_all[$k]['Orders'] as $friend_item) {
                    $i_friend_classid = $friend_item['i_classid'];
                    $i_friend_instanceid = $friend_item['i_instanceid'];
                    if ($i_classid == $i_friend_classid && $i_instanceid == $i_friend_instanceid) {
                        $friend_has_this_item[$i] = true;
                        $friends_price[$i] = $friend_item['o_price'];
                    }
                }
            }

            //получить инфу о предмете
            $item_json_url = "https://" . $website . "/api/ItemInfo/{$i_classid}_{$i_instanceid}/ru/?key={$api_key}";
            $item_content = file_get_contents($item_json_url);
            $item_json = json_decode($item_content, true);

            $min_price = $item_json['min_price'];
            //offers
            $offers_table = "<table>
				<tr>
					<td align='center'>Цена</td>
					<td align='center'>Предлож.</td>
				</tr>";
            if (!isset($item_json['offers'])) {
                $offers_table = "ПРЕДЛОЖ. НЕТ";
            } else {
                for ($j = 0; $j < count($item_json['offers']) && $j < 4; $j++) {
                    $offers_table_price = $item_json['offers'][$j]['price'] / 100;
                    $offers_table .= "
					<tr>
						<td align='center'>{$offers_table_price}</td>
						<td align='center'>{$item_json['offers'][$j]['count']}</td>
					</tr>";

                }
                $offers_table .= "</table>";
            }
            //autobuy
            $auto_buy_prices_array = "";

            $auto_buy_table = "<table>
				<tr>
					<td align='center'>Цена</td>
					<td align='center'>Запросов</td>
				</tr>";
            $ABT_max_o_price = $item_json['buy_offers'][0]['o_price'];
            for ($j = 0; $j < count($item_json['buy_offers']) && $j < 4; $j++) {
                $abt_o_price = $item_json['buy_offers'][$j]['o_price'] / 100;
                $auto_buy_prices_array .= $abt_o_price." ";
                $auto_buy_table .= "
				<tr>
					<td align='center'>{$abt_o_price}</td>
					<td align='center'>{$item_json['buy_offers'][$j]['c']}</td>
				</tr>";

            }
            $auto_buy_table .= "</table>";

            $row = array();
            $row['i_name'] = $i_name;
            $row['i_classid'] = $i_classid;
            $row['i_instanceid'] = $i_instanceid;
            $row['i_img'] = $i_img;
            $row['i_url'] = $i_url;
            $row['o_price'] = $o_price;
            $row['min_price'] = $min_price;
            $row['offers_table'] = $offers_table;
            $row['auto_buy_table'] = $auto_buy_table;
            $row['ABT_max_o_price'] = $ABT_max_o_price;
            $row['auto_buy_prices_array'] = $auto_buy_prices_array;
            $row['in_friends_orders'] = $friend_has_this_item[$i];
            $row['friends_price'] = $friends_price[$i];
            $row['game_id'] = $k;
            $row['max_allowed_auto_buy'] = -1;

            $myDatatable[$i] = $row;
            $i++;
        }
    } else {
        echo "ошибка доступа проверь введенный ключ";
    }
}

function fill_data($myDatatable)
{
    for ($i = 0; $i < count($myDatatable); $i++) {
        $max_auto_buy = $myDatatable[$i]['ABT_max_o_price'] / 100;

        $o_price = $myDatatable[$i]['o_price'] / 100;
        $friends_price = $myDatatable[$i]['friends_price'] / 100;
        echo "
	<input type='hidden' id='item_name' name='item_name' value='{$myDatatable[$i]['i_name']}' />
	<input type='hidden' id='item_id1' name='item_id1' value='{$myDatatable[$i]['i_classid']}' />
	<input type='hidden' id='item_id2' name='item_id2' value='{$myDatatable[$i]['i_instanceid']}' />
	<input type='hidden' id='item_your_offer_price' name='item_your_offer_price' value='{$o_price}' />
	<input type='hidden' id='item_max_auto_buy' name='item_max_auto_buy' value='{$max_auto_buy}' />
	<input type='hidden' id='friends_price' name='friends_price' value='{$friends_price}' />
	<input type='hidden' id='game_id' name='game_id' value='{$myDatatable[$i]['game_id']}' />
	";
    }
}

function print_table($column_id, $myDatatable, $columns_count)
{
    $string_result = "<table border='1'>
                  <tr>
                     <th>Шмотка</th>
                     <th>Предлож.</th>
					 <th>Ты поставил</th>
                     <th>Автозакупка</th>
                     <th>Вручную</th>
                     <th>Автомат.</th>
                  </tr> ";

    $bgRED = "FA8072";
    $bgGREEN = "ADFF2F";
    $bgBLUE = "2BE7FF";
    $bgGOLD = "FFD700";

    for ($i = $column_id; $i < count($myDatatable); $i += $columns_count) {
        if ($myDatatable[$i]['ABT_max_o_price'] > $myDatatable[$i]['o_price']) {
            $bgColor_col0 = $bgRED;
            $bgColor_col1 = $bgRED;
            $bgColor_col2 = $bgRED;
            $bgColor_col3 = $bgRED;
            $bgColor_col4 = $bgRED;

            if ($myDatatable[$i]['ABT_max_o_price'] - 50 <= $myDatatable[$i]['o_price']) {
                $bgColor_col2 = $bgBLUE;
                $bgColor_col3 = $bgBLUE;
                $bgColor_col4 = $bgBLUE;
            }
        } else {
            $bgColor_col0 = $bgGREEN;
            $bgColor_col1 = $bgGREEN;
            $bgColor_col2 = $bgGREEN;
            $bgColor_col3 = $bgGREEN;
            $bgColor_col4 = $bgGREEN;
        }

        $str_friends_price = "";
        $str_button_price_level = "";

        if ($myDatatable[$i]['in_friends_orders']) {
            $friends_price = $myDatatable[$i]['friends_price'] / 100;
            $str_friends_price = "<br/>(цена друга: {$friends_price})";
            $str_button_price_level = "<br/><input type='button' class='button' name='{$i}' value='сравнять' />";
            $bgColor_col0 = $bgGOLD;
            $bgColor_col1 = $bgGOLD;
        }

        if ($myDatatable[$i]['min_price'] > 0) {
            $min_price = $myDatatable[$i]['min_price'] / 100;
        } else {
            $min_price = "&mdash;";
        }
        $o_price = $myDatatable[$i]['o_price'] / 100;


        if ($myDatatable[$i]['max_allowed_auto_buy'] > -1) {
            $maxAllowedAutoBuyInput = "<input type='text' id='maab{$i}' name='max_allowed_auto_buy'
				style='text-align:center;width: 70px;' value='{$myDatatable[$i]['max_allowed_auto_buy']}'>";
            $maxAllowedAutoBuyButton = "<input type='button' class='auto_buy_switch' name='{$i}' value='Выкл' />";
        }
        else {
            $maxAllowedAutoBuyInput =  "<input type='text' id='maab{$i}' name='max_allowed_auto_buy'
				style='text-align:center;width: 70px;'>";
            $maxAllowedAutoBuyButton = "<input type='button' class='auto_buy_switch' name='{$i}' value='Вкл' />";
        }

        $string_result .= "<tr>
		<td align='center' bgcolor='{$bgColor_col0}'>
			<a href='{$myDatatable[$i]['i_url']}'>{$myDatatable[$i]['i_name']}</br>
			<img src='{$myDatatable[$i]['i_img']}'width='150' height='100'/></a></td>
		<td align='center' bgcolor='{$bgColor_col1}'>{$myDatatable[$i]['offers_table']}</td>
		<td align='center' bgcolor='{$bgColor_col2}'>{$o_price}
		{$str_friends_price}{$str_button_price_level}</td>
		<td align='center' bgcolor='{$bgColor_col3}'>{$myDatatable[$i]['auto_buy_table']}</td>
		<td align='center' bgcolor='{$bgColor_col4}'>
			<input type='button' class='button' name='{$i}' value='MAAX' /></br></br>
			<input type='number' id='{$i}' name='change_price_value' 
				style='text-align:center;width: 70px;' value='0.02' min='0' max='999,99' step='0.01'><br/>
			<input type='button' class='button' name='{$i}' value='-' />&emsp;
			<input type='button' class='button' name='{$i}' value='+' />
		</td>
		<td align='center' bgcolor='{$bgColor_col4}'>
			Макс. разрешн.
			{$maxAllowedAutoBuyInput}
			{$maxAllowedAutoBuyButton}
		</td>
		</tr>";
    }
    $string_result .= "</table>";

    return $string_result;
}

function print_tables($columns_count, $myDatatable)
{
    //autoBuy bot
    if (isset($_COOKIE['autoBuy'])) {
        foreach ($_COOKIE['autoBuy'] as $name => $value) {
            $cookieIdFound = false;
            $name = htmlspecialchars($name);
            $value = htmlspecialchars($value);
            $maxAllowedPrice = $value * 100; // копейки
//            echo("<br>" . $name . " - " . $value);
            for($i=0; $i< count($myDatatable); $i++) {
                $itemId = $myDatatable[$i]['i_classid']."-".$myDatatable[$i]['i_instanceid'];
//                echo "<br>".$itemId;
                if ($itemId == $name) {
                    $cookieIdFound = true;
                    $myDatatable[$i]['max_allowed_auto_buy'] = $value;

                    if ( $myDatatable[$i]['o_price'] != $myDatatable[$i]['ABT_max_o_price'] ||
                        $myDatatable[$i]['o_price'] > $maxAllowedPrice) {
                        $tmpYourPrice = $myDatatable[$i]['ABT_max_o_price']+1;
//                        echo "<br><b>".$name."</b>: tmp price is ".$tmpYourPrice;
                        if ($tmpYourPrice > $maxAllowedPrice) {
//                                echo "<br><b>".$name."</b>: price above allowed, check next price abt";
                            $autoBuyPricesArray = explode(" ", $myDatatable[$i]['auto_buy_prices_array']);
                            foreach($autoBuyPricesArray as $item) {
                                if ($item == "") {
                                    $tmpYourPrice = $maxAllowedPrice;
//                                    echo "<br><b>".$name."</b>: vse mnogo; set price ".$tmpYourPrice;
                                    break;
                                }
//                                echo "<br><b>".$name."</b>: ".$maxAllowedPrice." ".$item." ".(floatval($item) + 0.01)*100;
                                if ($maxAllowedPrice - 1 >= (floatval($item))*100) {

//                                    echo "<br><b>".$name."</b>: TRUE". $item;
                                    $tmpYourPrice = (floatval($item) + 0.01)*100;
//                                    echo "<br><b>".$name."</b>: set price ".$tmpYourPrice;
                                    break;
                                }
                            }
                        }

/*                        $b = strcmp ($tmpYourPrice, $myDatatable[$i]['o_price']);

                        echo "<br>".$tmpYourPrice. " ".$myDatatable[$i]['o_price']. " ".var_dump($b) ;*/
                        if (strcmp ($tmpYourPrice, $myDatatable[$i]['o_price'])) {

//                            echo "<br><b>".$name."</b>: need to update ".$tmpYourPrice. " ". $myDatatable[$i]['o_price'];

                            $api_key = $_REQUEST['api_key'];
                            $json_url = "https://csgo.tm/api/UpdateOrder/{$myDatatable[$i]['i_classid']}
                                    /{$myDatatable[$i]['i_instanceid']}/{$tmpYourPrice}/?key={$api_key}";

                            $content = file_get_contents($json_url);
                            $json = json_decode($content, true);
                            if (isset($json['success'])) {
                                if ($json['success']) {
//                                    echo "<br>auto buy price updated";
                                }
                            }
                        }
                    }
                }
            }
            if (!$cookieIdFound) {
//                echo "<br>COOKIE FOR ".$name." SHOULD BE DELETED";
                unset($_COOKIE['autoBuy['.$name.']']);
                setcookie('autoBuy['.$name.']', '', time() - 3600, '/'); // empty value and old timestamp
            }
        }
    }


    $string_result = "";

    for ($i = 0; $i < $columns_count; $i++) {
        $string_result .= "<td>";
        $string_result .= print_table($i, $myDatatable, $columns_count);
        $string_result .= "</td>";
    }

    return $string_result;
}

?>

<html>
<head>
    <title>Покупки</title>
    <script>
        function reload_page() {
            location.reload();
        }

        setTimeout(reload_page, 10000);
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>

    <script type="text/javascript">

        $(document).ready(function () {
            //del this
/*            document.cookie = "autoBuy[123131231231-12312312312]=234.54; " +
                "expires=Thu, 25 May 2017 12:00:00 UTC; path=/";*/


            $('.auto_buy_switch').click(function () {
                var switchButton = $(this);
                var maxAllowedPrice = $('#maab' + switchButton.attr('name'));
                var maxAllowedPriceValue = maxAllowedPrice.val();

                var item_id1 = document.getElementsByName('item_id1');
                var item_id2 = document.getElementsByName('item_id2');
                var itemId = item_id1[this.name].value + "-" + item_id2[this.name].value;

                if (switchButton.val() == "Вкл") {
                    if (maxAllowedPriceValue != "" && maxAllowedPriceValue > 0.5) {
                        switchButton.val("Выкл")

                        var objToday = new Date(),
                            weekday = new Array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'),
                            dayOfWeek = weekday[objToday.getDay()],
                            domEnder = function () {
                                var a = objToday;
                                if (/1/.test(parseInt((a + "").charAt(0)))) return "th";
                                a = parseInt((a + "").charAt(1));
                                return 1 == a ? "st" : 2 == a ? "nd" : 3 == a ? "rd" : "th"
                            }(),
                            dayOfMonth = today + ( objToday.getDate() < 10) ? '0' + objToday.getDate() + domEnder : objToday.getDate(),
                            months = new Array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'),
                            curMonth = months[objToday.getMonth()],
                            nextMonth = months[objToday.getMonth() + 1],
                            curYear = objToday.getFullYear(),
                            curHour = objToday.getHours() > 12 ? objToday.getHours() - 12 : (objToday.getHours() < 10 ? "0" + objToday.getHours() : objToday.getHours()),
                            curMinute = objToday.getMinutes() < 10 ? "0" + objToday.getMinutes() : objToday.getMinutes(),
                            curSeconds = objToday.getSeconds() < 10 ? "0" + objToday.getSeconds() : objToday.getSeconds(),
                            curMeridiem = objToday.getHours() > 12 ? "PM" : "AM";
                        var today = curHour + ":" + curMinute + "." + curSeconds + curMeridiem + " " + dayOfWeek + " " + dayOfMonth + " of " + curMonth + ", " + curYear;

                        var expires = dayOfWeek + ", " + dayOfMonth + " " + nextMonth + " " + curYear + " " + curHour + ":" + curMinute + ":" + curSeconds + " UTC";

                        // отправка cookie
                        document.cookie = "autoBuy["+itemId+"]=" + maxAllowedPriceValue + "; " +
                            "expires=" + expires + "; path=/";
//                        document.cookie = "autoBuy[id2]=" + item_id2[this.name].value + "; " +
//                            "expires=" + expires + "; path=/";
                    }
                    else {
                        alert("ЕРРОР!!! проверь максимальную разрешенную цену");
                    }

                }
                else {
                    switchButton.val("Вкл")
                    maxAllowedPrice.val("");
//                    alert(itemId);
                    document.cookie = "autoBuy["+itemId+"]"+ '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
                }
                reload_page();
            });

            $('.button').click(function () {
                var clickBtnValue = $(this).val();

                var item_name = document.getElementsByName('item_name');
                var item_id1 = document.getElementsByName('item_id1');
                var item_id2 = document.getElementsByName('item_id2');
                var item_your_offer_price = document.getElementsByName('item_your_offer_price');
                var change_price_value = document.getElementsByName('change_price_value');
                var game_id = document.getElementsByName('game_id');
                for (var i = 0; i < change_price_value.length; i++) {
                    if (change_price_value[i].id == this.name) {
                        var change_price_value_cur = change_price_value[i].value;
                        //alert("btn="+this.name+";id="+change_price_value[i].id+";val="+change_price_value[i].value);
                    }
                }
                if (clickBtnValue == 'MAAX') {
                    var max = document.getElementsByName('item_max_auto_buy')[this.name].value;
                    change_price_value_cur = (max - item_your_offer_price[this.name].value) + 0.01;
                    //alert (max);
                }
                if (clickBtnValue == 'сравнять') {
                    change_price_value_cur = document.getElementsByName('friends_price')[this.name].value;
                }
                var api_key = document.getElementById('api_key').value;


                var ajaxurl = 'orders_ajax.php',
                    data = {
                        'action': clickBtnValue,
                        'item_name': item_name[this.name].value,
                        'item_id1': item_id1[this.name].value,
                        'item_id2': item_id2[this.name].value,
                        'item_your_offer_price': item_your_offer_price[this.name].value,
                        'change_price_value': change_price_value_cur,
                        'api_key': api_key,
                        'game_id': game_id[this.name].value
                    };
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
<input type="button" value="Продажи" id="btnTrades"
       onClick="document.location.href='trades.php?api_key=<?php echo $api_key ?>&friend_api_key=<?php echo $friend_api_key ?>&columns_count=<?php echo $columns_count ?>'"/>
<table>
    <caption>Таблица покупок</caption>
    <tr>
        <?php
        if (SHOW_LOAD_TIME) {
            $load_time = microtime(1) - $load_time_start;
            echo "php code: " . $load_time . "<br/>";
        }
        echo print_tables($columns_count, $myDatatable);
        fill_data($myDatatable);
        if (SHOW_LOAD_TIME) {
            $load_time = microtime(1) - $load_time_start;
            echo "table draw: " . $load_time . "<br/>";
        }
        ?>
    </tr>
</table>
</body>
</html>
<?php
ob_end_flush(); // Flush the output from the buffer
?>