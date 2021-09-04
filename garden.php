<?php
/**
 * Name: Garden Home
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



if (isset($_POST['deletepage'])) {
		$delete_id = $_POST['deletepage'];
		$delete_sql = $conn->prepare("DELETE FROM " . GARDEN . " WHERE ID=?");
		$delete_sql->bind_param("i", $delete_id );
		$delete_sql->execute();
		$delete_sql->close();
}

?>


<!DOCTYPE html>
<html lang="en-GB">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#eeeeee">
	<title>Garden</title>
	<link rel="stylesheet" href="../style.css" type="text/css" media="all">
	<script type="text/javascript" src="../script.js"></script>
</head>

<body>
    <div id="page" class="hfeed h-feed site">
        <header id="masthead" class="site-header">
            <div class="site-branding">
                <h1 class="site-title">
                    <a href="/blog.php" rel="home">
                        <span class="p-name">Garden</span>
                    </a>
                </h1>
            </div>
        </header>


		<div id="primary" class="content-area">
			<main id="main" class="site-main today-container">

				<div style="float: left; margin-bottom: 30px;"><br/></div>

<?php
if ($_SESSION['auth'] == $dbauth) {
?>

<div style="float: right; margin-left: 20px; margin-bottom: 40px; position: relative; top: -5px;" >
<form name="newpage" id="newpage" action="dopage.php" method="post" >
  <input accesskey="p" type="text" name="pagetitle" id="pagetitle" placeholder="Page" style="width: 145px; font-size: 14px;" required>
<input accesskey="n" type="submit" name="submit2" id="submit2" value="New" style="border-color: #ccc; color: #aaa; top: 0px; font-size: 85%; height: 25px;" >
</form>
</div>

<?php } ?>

<span accesskey="t" id="toggleAll" style="position: relative; top: -1px; left:0px; float: left; cursor: pointer;" onclick="toggleAll();"><picture style="width: 22px; display: inline;"><source srcset="../images/select_dark.png" media="(prefers-color-scheme: dark)" style="width: 22px;"><img style="width: 22px;" src="../images/select.png" alt="Toggle all"></picture></span>

<?php

$first = 1;

$sql = $connsel->prepare("SELECT ID, Title, Content, Updated, Archive FROM " . GARDEN . " ORDER BY Updated DESC");
$sql->execute();
$result = mysqli_stmt_get_result($sql);

while($row = $result->fetch_assoc()) {
	$ID = $row["ID"];
	$title = $row["Title"];
  	$content = $row["Content"];
    $updated = $row["Updated"];
  	$is_archive = $row["Archive"]; 	
  	$parent_pos = strpos($content, "Parent:");
  	$raw = $content;
  	
  	if ($parent_pos === 0) {
  	  $getlines = preg_split('/\r\n|\r|\n/', $content, 2);
  	  $parent_name = substr($getlines[0], 10, -2);
  	}
  	
  	$parent_exists = 'no';
  	$parent_check_sql = $connsel->prepare("SELECT Title FROM " . GARDEN );
  	$parent_check_sql->execute();
  	$check_result = mysqli_stmt_get_result($parent_check_sql);
  	while($checkrow = $check_result->fetch_assoc()) {
  	  $check_title = $checkrow["Title"];
  	  if($check_title == $parent_name) {
  	    $parent_exists = "yes";
  	  }
  	}
  	$parent_check_sql->close();

		if ($first == 1) {
?>
			<div style="clear:both;"></div><div style="text-align: right; float: right; font-size: 12px; position: relative; top: -15px; margin-bottom: 0px;">Last update: <?php echo date('d/m, H:i', strtotime($updated)); ?><br />(<a class="last_update" href="page.php?t=<?php echo $title; ?>"><?php echo $title; ?></a>)</div><div style="clear:both;"></div>
			
			
<?php
			echo '<ul class="list_pages">';
			$first = 0;
		}
		
		if ($parent_pos === false || $parent_pos != 0 || $parent_exists == 'no') {
			echo '<li class="page_item" style="font-weight: bold !important; font-size: 17px;"><a href="' . BASE_URL . '/garden/page.php?t=' . $title . '">' . $title. '</a><span style="display: none; position: relative; top: -1px; float: right; font-weight: normal; cursor: pointer;" id="expand' . $ID . '" onclick="toggleChild(' . $ID . ')"><picture style="width: 22px; display: inline;"><source srcset="../images/select_dark.png" media="(prefers-color-scheme: dark)" style="width: 22px;"><img style="width: 22px;" src="../images/select.png" alt="Toggle all"></picture></span></li>';
		//}
		
		  $child = 1;
			$child_sql = $connsel->prepare("SELECT ID, Title, Content, Updated, Archive FROM " . GARDEN . " ORDER BY Updated DESC");
			$child_sql->execute();
			$child_result = mysqli_stmt_get_result($child_sql);
			while($child_row = $child_result->fetch_assoc()) {
				$child_title = $child_row["Title"];
  				$child_content = $child_row["Content"];
  				$child_archive = $child_row["Archive"]; 	
  				$parent_pos_child = strpos($child_content, "Parent:");
  				if ($parent_pos_child === 0) {
  					$lines = preg_split('/\r\n|\r|\n/', $child_content, 2);
  					$parent = substr($lines[0], 10, -2);
  					if($child == 1 && $parent === $title) {
						$child = 0;
						?>
						<script>
						document.getElementById("expand<?php echo $ID; ?>").style.display = "inline";
						</script>
						<?php
					}
  					if($parent === $title) {
	  					echo '<li class="page_item child child' . $ID . '" style="margin-left: 30px; display: none;"><a class="flash" style="font-weight: normal !important; " href="' . BASE_URL . '/garden/page.php?t=' . $child_title . '">' . $child_title . '</a></li>';
	  			
						$grandchild_sql = $connsel->prepare("SELECT ID, Title, Content, Updated, Archive FROM " . GARDEN . " ORDER BY Updated DESC");
						$grandchild_sql->execute();
						$grandchild_result = mysqli_stmt_get_result($grandchild_sql);
						while($grandchild_row = $grandchild_result->fetch_assoc()) {
							$grandchild_title = $grandchild_row["Title"];
  							$grandchild_content = $grandchild_row["Content"];
  							$grandchild_archive = $grandchild_row["Archive"]; 	
  							$parent_pos_grandchild = strpos($grandchild_content, "Parent:");
  							if ($parent_pos_grandchild === 0) {
  								$gclines = preg_split('/\r\n|\r|\n/', $grandchild_content, 2);
  								$gcparent = substr($gclines[0], 10, -2);
   								if($gcparent === $child_title) {
		  							echo '<li class="page_item child grandchild child' . $ID . '" style="margin-left: 60px; display: none; list-style-type: disc;"><a class="flash" style="font-weight: normal !important; " href="' . BASE_URL . '/garden/page.php?t=' . $grandchild_title . '">' . $grandchild_title . '</a></li>';
		  						}
 							}
  						}
					}
				}
  			}		
		}	
	
}

echo '</ul>';

$grandchild_sql->close();
$child_sql->close();
$sql->close();



echo '<div style="clear: both; margin-top: 0px;">';
echo '<ul class="list_pages"></ul>';
echo '</div>';




 ?>


</div>

		</main><!-- #main -->
	</div><!-- #primary -->

	</div>
</div>


	
	<div class="linksDiv day-links"><a accesskey="s" style="text-decoration: none;" title="Search" href="/garden/search.php"><picture class="searchicon"><source srcset="../images/search_dark.png" media="(prefers-color-scheme: dark)"><img class="searchicon" src="../images/search_light.png" alt="Search the garden"></picture></a>
	</div>

 <style>

	.page_item {
		margin-bottom: 15px;
		margin-left: 5px;
	}

	.list_pages li a {
		text-decoration: none;
		font-weight: bold;
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
	
	@media screen and (prefers-color-scheme: dark) {
		.list_pages li a, .indexlink {
			color: var(--grey-b);
		}
		
		.last_update {
			color: var(--grey-9);
		}
	} 
	
<?php if ($_SESSION['auth'] != $dbauth) { ?>
	ul {
		margin: 1.5em 0 1.5em .5em;
	}
	
	.list_pages {
		margin: 2.5em 0 1.5em .5em;	
	}
	
	.list_pages li a {
		margin-left: 10px !important;
	}
<?php } ?>

}

    </style>

    <script>
        function toggleChild(e) {
            childtoggle = "child" +e;
            page_items = document.getElementsByClassName(childtoggle);
				for ($i=0; $i < page_items.length; $i++) {
					if(page_items[$i].style.display == "none") {
						page_items[$i].style.display = "list-item";
					} else {
						page_items[$i].style.display = "none";
					}
				}
        }

        function toggleAll() {
            allDivs = document.getElementsByClassName('child');
            if (allDivs[0].style.display != 'list-item') {
            	for ($i=0; $i < allDivs.length; $i++) {
                    allDivs[$i].style.display = 'list-item';
                }
            } else {
            	for ($i=0; $i < allDivs.length; $i++) {
                    allDivs[$i].style.display = 'none';
                }
                page_items = document.getElementsByClassName('page_item_has_children');
				for ($i=0; $i < page_items.length; $i++) {
					page_items[$i].style.textIndent = '10px !important';
				}
            }
        }

    </script>

<?php
	$pageDesktop = "157";
	$pageMobile = "229";
	$copyright = "yes";
	include('../footer.php');
?>