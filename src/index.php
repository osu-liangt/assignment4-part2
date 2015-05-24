<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Video Database</title>
</head>
<body>
	<section id="add">
		<h2>Add Video</h2>
		<form action="index.php" method="POST">
			<p>Name: <input type="text" name="name"></p>
			<p>Category: <input type="text" name="category"></p>
			<p>Length: <input type="number" name="length"></p>
			<p><input type="submit" value="Add"></p>
		</form>
	</section>
	<hr>
	<section id="table">
		<h2>Video Database</h2>
		<?php
			// For PW ONLY
			include 'secure.php';

			$dbhost = 'oniddb.cws.oregonstate.edu';
			$dbname = 'liangt-db';
			$dbuser = 'liangt-db';
			
			$video_name = $_POST["name"];
			echo "$video_name";
		?>
	</section>
</body>
</html>