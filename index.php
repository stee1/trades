<html>
 <head>
 </head>
 <body>
		<form action="redirecter_to_orders.php" method="POST">
			<fieldset>
				api key: <input type="text" size="30" name="api_key"></input>
				friend api key: <input type="text" size="30" name="friend_api_key"></input>
				columns: <select name="columns_count">
					<option value="1">1</option>
					<option value="2">2</option>
					<option value="3" selected="selected">3</option>
					<option value="4">4</option>
				</select>
				<input type="submit" value="OK"/>
			</fieldset>
		</form>
 </body>
</html>