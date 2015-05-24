<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html');

// Connect to Database

	// For $dbpass ONLY
	include 'secure.php';

	$dbhost = 'oniddb.cws.oregonstate.edu';
	$dbname = 'liangt-db';
	$dbuser = 'liangt-db';

	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

	if ($mysqli->connect_errno) {
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " .
			$mysqli->connect_error;
	}

// If video is posted with a non-empty name and positive length

	$badAddVideo = false;

	$noName = count($_POST) > 0 && empty($_POST["name"]);
	$badLength =
		count($_POST) > 0 &&
		!empty($_POST["length"]) &&
		$_POST["length"] <= 0;

	if (count($_POST) > 0 && !$noName && !$badLength) {

		// Prepare statement for data insertion

		if (!($addVideoStmt = $mysqli->prepare(
			"INSERT INTO videos(name, category, length, rented) VALUES
				(?, ?, ?, ?)"))) {
    	echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}

		// All videos are by default checked in
		$rented = 0;

		if (!$addVideoStmt->bind_param(
			"ssii",
			$_POST["name"],
			$_POST["category"],
			$_POST["length"],
			$rented)) {
    	echo "Binding parameters failed: (" . $addVideoStmt->errno . ") " .
    		$addVideoStmt->error;
		}

		if (!$addVideoStmt->execute()) {
			$badAddVideo = true;
			$badAddVideoCode = $addVideoStmt->errno;
		}

		if ($badAddVideo && $badAddVideoCode != 1062) {
    	echo "Execute failed: (" . $addVideoStmt->errno . ") " .
    		$addVideoStmt->error;
		}

		$addVideoStmt->close();

	}

// Check out

if (isset($_GET["checkToggle"]) && isset($_GET["rented"])) {
	$oppositeStatus = strval((intval($_GET["rented"]) + 1) % 2);
	$mysqli->query("UPDATE videos SET rented = " .
		$oppositeStatus .
		" WHERE id = " .
		$_GET["checkToggle"]);
	header('Location: ./');
}

// Delete row

if (isset($_GET["delete"])) {
	$mysqli->query("DELETE FROM videos WHERE id = " .
		$_GET["delete"]);
	header('Location: ./');
}

// Delete all

if (isset($_GET["deleteAllVideos"])) {
	$mysqli->query("TRUNCATE TABLE videos");
	header('Location: ./');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Video Database</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<section id="add">
		<h2>Add Video</h2>
		<form action="./" method="POST">
			<p>Name: <input type="text" name="name"></p>
			<?php
				if ($noName && !isset($_POST["categoryPick"])) {
					echo "<p class=\"badInput\">Name cannot be empty</p>\n";
				}
				else if ($badAddVideo && $badAddVideoCode == 1062) {
					echo "<p class=\"badInput\">Video name already in database</p>\n";
				}
			?>
			<p>Category: <input type="text" name="category"></p>
			<p>Length: <input type="number" name="length"></p>
			<?php
				if ($badLength && !isset($_POST["categoryPick"])) {
					echo "<p class=\"badInput\"> Length must be positive</p>\n";
				}
			?>
			<p><input type="submit" value="Add"></p>
		</form>
	</section>
	<hr>
	<section id="table">
		<h2>Video Database</h2>
		<?php

		// Create Table - ONLY EXECUTED ONCE, PRESERVED FOR REFERENCE

		// if (!$mysqli->query("DROP TABLE IF EXISTS videos") ||
		// 		!$mysqli->query(
		// 			"CREATE TABLE videos(
		// 				id INT PRIMARY KEY AUTO_INCREMENT,
		// 				name VARCHAR(255) NOT NULL UNIQUE,
		// 				category VARCHAR(255),
		// 				length INT,
		// 				rented INT NOT NULL
		// 			)")) {
		// 	echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
		// }

		// Determine possible categories

		$categories = array();

		if (!($getCatStmt = $mysqli->prepare(
			"SELECT DISTINCT category
			FROM videos
			ORDER BY category ASC"))) {
		    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		if (!$getCatStmt->execute()) {
		     echo "Execute failed: (" . $getCatStmt->errno . ") " .
		     	$getCatStmt->error;
		}
		if (!($getCatStmt->bind_result($fetchedCategory))) {
		    echo "Binding results failed: (" . $getCatStmt->errno . ") " .
		    	$getCatStmt->error;
		}
		$index = 0;
		while ($getCatStmt->fetch()) {
			if (!empty($fetchedCategory)) {
				$categories[$index] = $fetchedCategory;
				$index++;
			}
		}

		// Generate Table

		if (!($getVideosStmt = $mysqli->prepare(
			"SELECT id, name, category, length, rented
			FROM videos
			WHERE category LIKE ?
			ORDER BY id ASC"))) {
		    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}

		if (!isset($_POST["categoryPick"]) || $_POST["categoryPick"] == "all")
			$categoryPicked = "%";
		else
			$categoryPicked = $_POST["categoryPick"];

		if (!$getVideosStmt->bind_param("s", $categoryPicked)) {
    	echo "Binding parameters failed: (" . $getVideosStmt->errno . ") " .
    		$getVideosStmt->error;
		}
		if (!$getVideosStmt->execute()) {
		     echo "Execute failed: (" . $getVideosStmt->errno . ") " .
		     	$getVideosStmt->error;
		}
		if (!($getVideosStmt->bind_result(
			$id, $name, $category, $length, $rented))) {
		    echo "Binding results failed: (" . $getVideosStmt->errno . ") " .
		    	$getVideosStmt->error;
		}

		if ($getVideosStmt->fetch()) {

			echo "<p><form action=\"./\" method=\"POST\">\n";
				echo "<select name = \"categoryPick\">\n";
					echo "<option value=\"all\">All Movies</option>\n";
					for ($index = 0; $index < count($categories); $index++) {
						echo "<option value=\"$categories[$index]\">" .
							ucwords($categories[$index]) . "</option>\n";
					}
				echo "</select>\n";
				echo "<input type=\"submit\" value=\"Filter\">\n";
			echo "</form></p>\n";

			if ($rented) {
				$availability = "Checked Out";
				$checkText = "Check In";
			}
			else {
				$availability = "Available";
				$checkText = "Check Out";
			}

			echo "<table>\n";
				echo "<thead>\n";
					echo "<tr>\n";
						echo "<th>ID</th>\n";
						echo "<th>Name</th>\n";
						echo "<th>Category</th>\n";
						echo "<th>Length</th>\n";
						echo "<th>Availability</th>\n";
						echo "<th>Check Out/In</th>\n";
						echo "<th>Delete</th>\n";
					echo "</tr>\n";
				echo "</thead>\n";
				echo "<tbody>\n";
					echo "<tr>\n";
						echo "<td>$id</td>\n";
						echo "<td>$name</td>\n";
						echo "<td>$category</td>\n";
						echo "<td>$length</td>\n";
						echo "<td>$availability</td>\n";
						echo "<td><a href=\"./?
							checkToggle=$id&
							rented=$rented\">$checkText</a></td>\n";
						echo "<td><a href=\"./?delete=$id\">Delete</a></td>\n";
					echo "</tr>\n";

				while($getVideosStmt->fetch()) {

					if ($rented) {
						$availability = "Checked Out";
						$checkText = "Check In";
					}
					else {
						$availability = "Available";
						$checkText = "Check Out";
					}

					echo "<tr>\n";
						echo "<td>$id</td>\n";
						echo "<td>$name</td>\n";
						echo "<td>$category</td>\n";
						echo "<td>$length</td>\n";
						echo "<td>$availability</td>\n";
						echo "<td><a href=\"./?
							checkToggle=$id&
							rented=$rented\">$checkText</a></td>\n";
						echo "<td><a href=\"./?delete=$id\">Delete</a></td>\n";
					echo "</tr>\n";
				}

				echo "</tbody>\n";
			echo "</table>\n";
			echo "<p><a href=\"./?deleteAllVideos\">Delete All Videos</a></p>\n";
		}
		else {
			echo "<p>There are no videos currently in the database.</p>\n";
		}

		$getVideosStmt->close();
		$mysqli->close();

		?>
	</section>
</body>
</html>