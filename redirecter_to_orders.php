<?php
$api_key = $_REQUEST['api_key'];
$friend_api_key = $_REQUEST['friend_api_key'];
$columns_count = $_REQUEST['columns_count'];
header("Location: orders.php?api_key=".$api_key."&friend_api_key=".$friend_api_key."&columns_count=".$columns_count);
exit();
?>