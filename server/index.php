<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	date_default_timezone_set('America/Los_Angeles');
	$date = new DateTime();
	$jsdf = 'Y\, m\-\1 \, d';
	$dbdf = 'Y\-m\-d';
	$fetchDate = $date->getTimestamp();
	$morning = FALSE;
	$night = FALSE;

	$user = 'user' //changed these for commit, I'm not *that* dumb
	$pass = 'pass'

	$conn = new PDO('mysql:host:localhost;dbname=catfood', $user, $pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//if submitted, we want to fetch the data for the old date in addition to writing
		if (isset($_POST['submit'])) {
			$fetchDate = strtotime($_POST['date']);
			$updateData = [
	                        'morning' => (isset($_POST['morning']) ? 1 : 0),
	                        'night' => (isset($_POST['night']) ? 1 : 0),
	                        'date' => date($dbdf, $fetchDate)];

			//check to see if the data for the day to add already exists
	                $stmt = $conn->prepare('SELECT morning, night FROM catfood.fed WHERE date=?');
	                $stmt->execute([$updateData['date']]);
	                $data = $stmt->fetch();

			//if the date is not already in the database, insert
			if (!$data)
				$stmt = $conn->prepare('INSERT INTO catfood.fed VALUES (:morning, :night, :date)');
			//if date is already in database, update it
			else
				$stmt = $conn->prepare('UPDATE catfood.fed SET morning=:morning, night=:night WHERE date=:date');
	                $stmt->execute($updateData);
		}

		elseif (isset($_POST['back']) || isset($_POST['fore'])) {
			if (isset($_POST['back']))
				$fetchDate = strtotime('-1 day', strtotime($_POST['date']));
			if (isset($_POST['fore']))
				$fetchDate = strtotime('+1 day', strtotime($_POST['date']));
		}

		elseif (isset($_POST['today'])) $fetchDate = $date->getTimestamp();

		else $fetchDate = strtotime($_POST['date']);

	}

	//update with whatever date data
	$stmt = $conn->prepare('SELECT morning, night FROM catfood.fed WHERE date=?');
	$stmt->execute([date($dbdf, $fetchDate)]);
	$data = $stmt->fetch();
	$morning = $data['morning'];
	$night = $data['night'];

	$conn = null;
?>
<HTML>
<head>
	<title>ShoobaFood</title>
</head>
<link rel="apple-touch-icon-precomposed" href="shoescrem256.jpg"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"/>
<link rel="stylesheet" href="style.css">
<script type="text/javascript">
	var date = new Date();
	window.onload = onLoad;

	function onLoad() {
		//this is here twice to prevent the buttons from moving before php finishes executing 
		document.getElementById("dateField").innerHTML = date.toDateString();

		date = new Date(<?php echo date($jsdf, $fetchDate);?>);
		document.getElementById("dateField").innerHTML = date.toDateString();
		document.getElementById("date").value = date.toLocaleString().split(',')[0];
	}
</script>
<body>
	<div id="headbar" class="fw headbar">
		<form method="POST" id="mainForm">
		        <span class="he">
		        	<input class="butt" type="submit" name="back" value="<<"/>
		        </span>
		        <span class="he">
		        	<h3 id="dateField"></h3>
		        </span>
		        <span class="he">
		        	<input class="butt" type="submit" name="fore" value=">>"/>
		        </span>
			<input type="hidden" name="date" id="date" value=""/>
	</div>
	<div class="fw">
		<label for="morning" style="color:<?php echo ($morning) ? '#217c1d' : '#f94231';?>" >Morning</label>
	       	<input name="morning" type="checkbox" <?php echo ($morning) ? 'checked' : '';?>><br>
       		<label for="night" style="color:<?php echo ($night) ? '#217c1d' : '#f94231';?>" >Evening</label>
       		<input name="night" type="checkbox" <?php echo ($night) ? 'checked' : '';?>><br>
	</div>
	<div class="fw">
		<input type="submit" class="butt" value="Submit" name="submit"/>
	</div>
	<div class="fw" style="padding-top: 50px;">
		<input type="submit" class="butt" value="Refresh"/>
	</div>
	<div class="fw">
		<input type="submit" class="butt" name="today" value="Today"/>
	</div>
	<div class="fw">
		<input type="button" class="butt" onclick="window.location.href='about.php'" value="More Info"/>
	</div>
	</form>
</body>
</html>
