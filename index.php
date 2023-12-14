<?php
# If you are reading this, I was overwealmed at first until I understood how it works.

# session start stuff
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

# initialize stuff
extract($_SESSION);

# check to see $_REQUEST
extract($_REQUEST);

# do we have a name?
if (isset($name)) { 
	# prevent HTML injection by quoting the $name
	# (should make no change if it is a reasonable name)
	$name = "$name" ;
	$name = htmlentities($name);
	$_SESSION['name'] = $name;
	# are we preparing the page for the user to use to chat?
	# or are we receiving the submission from that page?
	if (isset($ajax)) {
		# we are receiving the submission from the page
		if ("$input_text" == "kill site") {
			session_unset();
    		session_destroy();
			echo '<h4><a href="https://puff.mnstate.edu/~kb8125mc/private/">The main private page</a><br></h4>' ;
			echo "<h1>The chat below no longer works, please go back to main private page</h1>" ;
			echo "<br><br><br><br>" ;
		} else if ($input_text) {
			# we have the text that we need to add to the chat
			add_to_chat($name,$input_text);
		};
		# we return what is in the chat
		current_chat_text();
	} else {
		# we are providing the page for the user to chat
		chat_as($name);
	};
} else {
	prompt_for_name();
};

# functions

function add_to_chat($name,$input_text) {

	# read in the entire chat_text.txt file
	# into an array, one line per array element
	require 'password.php' ;
	$conn = mysqli_connect("localhost",$username,$password,$dbname);
	//$chat_lines = file("chat_text.txt");

	$sql = "INSERT INTO log (name, message) VALUES ( '$name', '$input_text');" ;
	$result = mysqli_query($conn,$sql);
        if (!$result) {

            echo "Error executing query (1): " . mysqli_error($conn);
        }


	$sql = "SELECT CONCAT(name, ': ', message, '\n ') AS skip_sp_blw
			FROM log
			LIMIT 10 " ;

	$result = mysqli_query($conn,$sql);
	if (!$result) {

		echo "Error executing query (2) : " . mysqli_error($conn);
	}
	else {
		$chat_lines = array() ;
		while ($row = mysqli_fetch_assoc($result)) { 
			//Fetch the next row of a result set as an associative array
			$chat_lines[] = $row['skip_sp_blw'];
		}
	}
	# then add this to the end
	// $chat_lines[] = $name . ": " . $input_text . "\n";

	# then overwrite the chat_text.txt file with just the last 10 elements
	
	# first get the last 10 elements
	$chat_lines = array_slice($chat_lines,-10);

	# now we get ready to write to the file

	# first we open the file for writing
	//$f = fopen("chat_text.txt","w");

	# then we write out each line
	// foreach ($chat_lines as $chat_line) {
	// 	fwrite($f,$chat_line);
	// };

	# then we close the file
	// fclose($f);
	mysqli_close($conn);
};

function current_chat_text() {
	# output the entire content of the chat_text.txt file
	# but insert <br/> in-between each line
	# and prevents HTML injection by quoting it using HTML entities
	echo "<div><h1>Chat Text</h1>";
	require 'password.php' ;
	$conn = mysqli_connect("localhost",$username,$password,$dbname);
	$sql = "SELECT CONCAT(name, ': ', message, '\n ') AS skip_sp_blw
			FROM log " ;

	$result = mysqli_query($conn,$sql);
	if (!$result) {

		echo "Error executing query (2) : " . mysqli_error($conn);
	}
	else {
		$chat_lines = array() ;
		while ($row = mysqli_fetch_assoc($result)) { 
			//Fetch the next row of a result set as an associative array
			$chat_lines[] = $row['skip_sp_blw'];
		}
	}
	//$chat_lines = file("chat_text.txt");
	$chat_lines = array_slice($chat_lines, -10) ;
	foreach ($chat_lines as $chat_line) {
		echo htmlentities($chat_line)."<br/>";
	};
	echo "</div>";
	mysqli_close($conn);
};

function prompt_for_name() {
	echo <<< HERE
	<html>
	<body>
	<form>
	What is your name?
	<input type="text" name="name" />
	<input type="submit" />
	</form>
	</body>
	</html>
HERE;

};

function chat_as($name) {
	# here we output all the HTML, JS, etc. needed to actually do the chatting
	# and we need to remember that the ajax submitted stuff needs to have
	# $ajax as true and the chat line in $input_text

    $dump = highlight_file("index.php", true) ;
	echo <<< HERE
<!DOCTYPE html>
<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
\$(document).ready(function(){
  \$("#chat").click(function(){
		  \$.post("index.php",{

		  name: "$name",
		  ajax: 1,
		  input_text: \$("#input_text").val()

	  },function(data,status){
		  \$("#data").html(data);
		  // \$("#status").html(status+\$("#input_text").val());
	  });
  });
  \$("#clearDebug").click(function(){
	  \$("#status").html("");
  });
});
</script>
	<style>
	#status , #clearDebug { display: none; }
	</style>
</head>
<body>

<div id="data">Chat Text Here, say something to see chat</div>

<div id="status">Debugging Information Here</div>
<a>[enter 'kill site' to stop the sesion]<br><br></a>
<input id="input_text" type="text" name="input_text" />

<button id="chat">Chat</button>

<button id="clearDebug">Clear Debugging</button>
<HR>
$dump
</body>
</html>


HERE;
};
?>
