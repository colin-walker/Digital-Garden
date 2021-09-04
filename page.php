<?php
/*
	Name: Garden
*/

// Initialise session
session_start();

define('APP_RAN', '');

require_once('../config.php');
require_once('../content_filters.php');
require_once('../Parsedown.php');
require_once('../ParsedownExtra.php');

date_default_timezone_set('' . TIMEZONE . '');

// Get auth string from database

$authsql = $connsel->prepare("SELECT Option_Value FROM " . OPTIONS . " WHERE Option_Name = 'Auth' ");
$authsql->execute();
$authresult = mysqli_stmt_get_result($authsql);
$row = $authresult->fetch_assoc();
$dbauth = $row["Option_Value"];
$authsql->close();




$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$prev_date = date('Y-m-d', strtotime($date .' -1 day'));
$next_date = date('Y-m-d', strtotime($date .' +1 day'));

$year = date('Y', strtotime($date));
$month = date('m', strtotime($date));
$day = date('d', strtotime($date));

$dbdate = $year . '-' . $month . '-' . $day;

$title = $_GET['t'];


// Export Page

	if ( isset($_POST['savePage']) && ($_POST['savePage']) =='yes' ) {
		$ID = $_POST['pageID'];
		
		$export_sql = $connsel->prepare("SELECT ID, Title, Content FROM " . GARDEN . " WHERE ID=?");
		$export_sql->bind_param("i", $ID);
		$export_sql->execute();
		$export_result = mysqli_stmt_get_result($export_sql);
		while ($row = $export_result->fetch_assoc()) {
			$export_title = $row["Title"];
			
			$export_content = $row["Content"];
		
			//$root = $_SERVER['DOCUMENT_ROOT'];
			$file = '../export/' . $export_title . '.md';
			
			if(!file_exists("../export")) {
   				mkdir("../export");
			}
			opendir("../export/");
			if ( file_exists( $file ) ) {
			  unlink( $file );
			}
		
			$export_file = fopen('../export/' . $file, 'w');
		
			fwrite($export_file, $export_content);
			fclose($export_file);
		
			if(file_exists($file)) {
				ob_start();
			
				header('Content-Description: File Transfer');
				header('Content-Type: text/markdown');
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
				header('Content-Length: ' . filesize($file));

				readfile($file,true);
				ob_end_flush();
				exit();
			}
		}
	}


// Update page

if ( isset($_POST['updatepost']) && ($_POST['newcontent'] !='') ) {
    if ($_SESSION['auth'] == $dbauth) {
	    $newcontent = $_POST['newcontent'];
	    //$now = date("D, d M Y H:i:s");
	    $now = date("Y-M-D H:i:s");
	    $ID = $_POST['id'];
	    $updatesql = $conn->prepare("UPDATE " . GARDEN . " SET Content=?, Updated=NOW() WHERE ID=?");
	    $updatesql->bind_param("si", $newcontent, $ID);
	    $updatesql->execute();
	    $updatesql->close();
	}
}

?>

<!DOCTYPE html>
<html lang="en-GB">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#eeeeee">
	<title>Garden - <?php echo $title; ?></title>
	<link defer rel="stylesheet" href="/bigfoot/bigfoot-bottom.css" type="text/css" media="all">
	<link rel="stylesheet" href="../style.css" type="text/css" media="all">
	<script type="text/javascript" src="../script.js"></script>
</head>

<body>
    <div id="page" class="hfeed h-feed site">
        <header id="masthead" class="site-header">
            <div class="site-branding">
                <h1 class="site-title">
                    <a href="/blog.php" rel="home">
                        <span class="p-name"><?php echo $title; ?></span>
                    </a>
                </h1>
            </div>
        </header>


		<div id="primary" class="content-area">
			<main id="main" class="site-main today-container">
			
				<div style="float: left; margin-bottom: 20px;">
					<a class="indexlink" style="position: relative; top: -2px; font-weight: bold; font-size: 18px; text-decoration: none;" href='../garden/'>INDEX</a>
				</div>

<?php
if ($_SESSION['auth'] == $dbauth) {
?>				
				<div style="float: right; margin-left: 20px; margin-bottom: 25px; position: relative; top: -5px;" >
						<form name="newpage" id="newpage" action="dopage.php" method="post" >
  						<input accesskey="p" type="text" name="pagetitle" id="pagetitle" placeholder="Page" style="width: 145px; font-size: 14px;" required>
  						<input type="hidden" name="parent" value="<?php echo $title; ?>">
						<input accesskey="n" type="submit" name="submit2" id="submit2" value="New" style="border-color: #ccc; color: #aaa; top: 0px; font-size: 85%; height: 25px;" >
					</form>
				</div>
				<div style="clear:both;"></div>
<?php
}

	$page_sql = $connsel->prepare("SELECT ID, Content, Updated FROM " . GARDEN . " WHERE Title=?");
	$page_sql->bind_param("s", $title);
	$page_sql->execute();
	$page_result = mysqli_stmt_get_result($page_sql);
	
	$rowcount=mysqli_num_rows($page_result);
	if($rowcount == 0) {
	  echo '<br/><h3>Page does not exist</h3>';
	}
	
	while ($row = $page_result->fetch_assoc()) {
		$ID = $row["ID"];
		$day = $row["Day"];
		$content = $row["Content"];
		$updated = $row["Updated"];
		$page_sql->close();
		$raw_content = $content;
		$content = filters($content);
		
		$Parsedown = new ParsedownExtra();
		$content = $Parsedown->text($content);
		
		$content = str_replace('@@', '<a><span style=" margin-right: 10px;">#</span></a>', $content);
	
			echo '<div style="display: block; float: right; font-size: 12px;">Last update: ' . date('d/m, H:i', strtotime($updated)) . '</div><h3><br/></h3>';
			if ($_SESSION['auth'] == $dbauth) {
				echo '<form method="post" style="float: right; position: relative; top: -0.5px; margin-left: 20px;"><input type="hidden" name="savePage" value="yes"><input type="hidden" name="pageID" value="' . $ID . '"><input type="image" src="https://colinwalker.blog/wp/wp-content/uploads/2021/03/markdown2.png" style="height: 22px; cursor: pointer;" /></form>';
				echo '<form action="/garden/" method="post" style="float: right; position: relative; top: 2px; margin-bottom: 10px;"><input type="hidden" name="deletepage" value="' . $ID . '"><input onClick="javascript: return confirm(\'Delete ' . $title . ' - Are you sure?\');" type="image" src="https://colinwalker.blog/images/red-cross.png" style="position: relative; top: 1px; width: 16px;"></form>';
			}
			echo '<article id="post">';		
			echo '<div class="section">';
			echo '<div style="word-wrap: break-word;" class="entry-content e-content">';
			if ($_SESSION['auth'] == $dbauth) {
				$editSpan .= '<a class="editicon" onclick="toggleUpdate();" style="display: inline-block; "><picture class="noradius" style="width: 12px; position: relative; top: -1px;"><source srcset="' . BASE_URL . '/images/edit_dark.png" media="(prefers-color-scheme: dark)"><img class="noradius"  style="width: 12px; position: relative; top: -1px;" src="' . BASE_URL . '/images/edit_light.png" /></picture></a>';
			} else {
				$editSpan = '';
			}
			echo $editSpan . $content;
			echo '</div>'; // entry-content
			echo '</div>'; // section
			echo '</article>';			
			echo '<div id="editdiv" class="editdivs">' . PHP_EOL;


?> 
    <form name="form" method="post">
        <input type="hidden" id="updatepost" name="updatepost">
        <input type="hidden" id="id" name="id" value="<?php echo $ID; ?>">
        <textarea autofocus oninput="insertLink(event)" rows="10" id="newcontent" name="newcontent" class="newcontent text"><?php echo $raw_content; ?></textarea>
        <a id="quit" onclick="toggleUpdate();"><img  style="width: 20px; float: left; position: relative; top: -1px; cursor: pointer;" src="../images/cancel.png" /></a>
        <input style="float:right; font-size: 75%" type="submit" name="submit" id="submit" value="Update" accesskey="s">
    </form>
<?php
	echo '</div>';
	echo '<div id="countdiv" style="float: right; margin-top: 20px; font-size: 12px;"></div><br/>';
	}

$first = 1;
$child_sql = $connsel->prepare("SELECT ID, Title, Content, Updated, Archive FROM " . GARDEN . " ORDER BY Updated DESC");
$child_sql->execute();
$child_result = mysqli_stmt_get_result($child_sql);

while($child_row = $child_result->fetch_assoc()) {
	$child_title = $child_row["Title"];
  	$child_content = $child_row["Content"]; 	
  	$parent_pos_child = strpos($child_content, "Parent:");
  	if ($parent_pos_child === 0) {
  		$lines = preg_split('/\r\n|\r|\n/', $child_content, 2);
  		$parent = substr($lines[0], 10, -2);
  		if($parent === $title) {
  	
  			if ($first == 1) {
?>
				<div style="margin-top: 75px; margin-bottom: 25px; padding-top: 15px; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;">
					<div style="margin-bottom: 5px;">
					  <?php
					  	if($rowcount == 0) {
					  	  echo 'Orphaned pages:';
					  	} else {
					  	  echo 'Also see:';
					  	}
					  ?>

						<ul>
<?php
			}
	echo '<li style="line-height: 1.5em;"><a style="text-decoration: none;" href="page.php?t=' . $child_title . '"> ' . $child_title . '</a></li>';
	$first = 0;
  		}
  	}
}
	
if ($first == 0) {
	echo '</ul></div></div>';
}  ?>

			</main><!-- #main -->
		</div><!-- #primary -->
	</div><!-- #page -->

    <script>
    	function toggleUpdate() {
    		post = document.getElementById("post");
    		edit = document.getElementById("editdiv");
    		count = document.getElementById("countdiv");
    		
    		if (post.style.display != 'none') {
    			post.style.display = 'none';
    			edit.style.display = 'block';
    			var contentArea = document.getElementById('newcontent');
                var areaLen = contentArea.value.length;
                contentArea.setSelectionRange(areaLen, areaLen);
                contentArea.focus();
    		} else {
    			post.style.display = 'block';
    			edit.style.display = 'none';
    		}
    	}
    	
    	var target = document.getElementById("newcontent");
		target.addEventListener ('keydown',  doKeyEvent);

		function doKeyEvent(event) {
			if (event.ctrlKey  &&  event.altKey && event.key == 'Enter') {
				event.preventDefault();
				document.getElementById("submit").click();
			}

			if (event.ctrlKey  &&  event.altKey && event.key == 'Backspace') {
				event.preventDefault();
				document.getElementById("quit").click();
			}
		}
    	
    	function countWords(str) {
			str = str.replace('/',' ');
    		str = str.replace(/(^\s*)|(\s*$)|(\@\@)/gi,"");
    		str = str.replace(/[ ]{2,}/gi," ");
    		str = str.replace(/\n /,"\n");
    		str = str.replace('-','');
    		str = str.replace('- ','');
    		str = str.replace('= ','');
    		return str.trim().split(/\s+/).length;
		}
		
		function insertLink(event) {
			let contentArea = document.getElementById('newcontent');
			let pageText = contentArea.value;
   			let startPos = contentArea.selectionStart;
    		let endPos = contentArea.selectionEnd;
    		let contentLen = pageText.length;
    		
    		let str = document.getElementById("newcontent").value;
			document.getElementById("countdiv").innerHTML = "Approx word count: " + countWords(str) + " *<br/>";
		}

		let str = document.getElementById("newcontent").value;
		document.getElementById("countdiv").innerHTML = "Approx word count: " + countWords(str) + "<br/>";

        // autosave - thanks Jan-Lukas – https://jlelse.blog/dev/form-cache-localstorage
  		
  		var newcontent = document.getElementById("newcontent");
		var cached = localStorage.getItem("pagecontent");
		
		if (cached != null) {
			newcontent.value = cached;
		}
		newcontent.addEventListener("input", function () {
			localStorage.setItem("pagecontent", newcontent.value);
		})
		document.addEventListener("submit", function () {
             localStorage.removeItem("pagecontent");
         })
	</script>
    
<?php 
if ($_SESSION['auth'] == $dbauth && $content == '') {
?>
<script>
	toggleUpdate();
</script>
<?php 
}
?>

<script type="text/javascript" src="/jquery.slim.min.js"></script>
<script type="text/javascript" src="/bigfoot/bigfoot.min.js"></script>
<script type="text/javascript">
	var bigfoot = $.bigfoot( {
	  positionContent: true,
	  preventPageScroll: true
	} );
</script>

<style>
	@media screen and (prefers-color-scheme: dark) {
		.indexlink {
			color: var(--grey-b);
		}
		
		.last_update {
			color: var(--grey-9);
		}
	}
	
	input[type="text"] {
		color: #777;
		border: 1px solid #ccc;
		border-radius: 10px;
		padding-top: 3px;
		padding-bottom: 3px;
		padding-left: 8px;
		padding-right: 8px;
	}
</style>

<?php
	$pageDesktop = "102";
	$pageMobile = "147";
	$copyright = "yes";
	include('../footer.php');
?>