<?php
/**
 * Name: Create Garden page
 */
 
 // Initialise session
session_start();

define('APP_RAN', '');

require_once('../config.php');


// Get auth string from database

$authsql = $connsel->prepare("SELECT Option_Value FROM " . OPTIONS . " WHERE Option_Name = 'Auth' ");
$authsql->execute();
$authresult = mysqli_stmt_get_result($authsql);
$row = $authresult->fetch_assoc();
$dbauth = $row["Option_Value"];
$authsql->close();


// create page

$pagetitle = $_POST['pagetitle'];
$now = date("D, d M Y H:i:s");
$parent = $_POST['parent'];
if ($parent != '') {
	$content = 'Parent: [[' . $parent . ']]';
} else {
	$content = '';
}

$check_sql = $connsel->prepare("SELECT * FROM " . GARDEN . " WHERE Title=?");
$check_sql->bind_param("s", $pagetitle);
$check_sql->execute();
$check_result = mysqli_stmt_get_result($check_sql);
$check_sql->close();

$rowcount=mysqli_num_rows($check_result);
if($rowcount == 0) {

	if ($_SESSION['auth'] == $dbauth) {
		$page_sql = $conn->prepare("INSERT INTO " . GARDEN . " (Title, Content, Updated) VALUES (?, ?, NOW())");
		$page_sql->bind_param("ss", $pagetitle, $content);
		$page_sql->execute();
		$Page_ID = $page_sql->insert_id;
		$page_sql->close();
		
		header("location: page.php?t=" . $pagetitle);
		exit;
	}
} else {
?>

<!DOCTYPE html>
<html lang="en-GB">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#eeeeee">
	<title>Garden - page already exists</title>
	<link rel="stylesheet" href="../style.css" type="text/css" media="all">
	<script type="text/javascript" src="../script.js"></script>
</head>

<body>
    <div id="page" class="hfeed h-feed site">
        <header id="masthead" class="site-header">
            <div class="site-branding">
                <h1 class="site-title">
                    <a href="/blog.php" rel="home">
                        <span class="p-name"><?php echo $pagetitle; ?> already exists</span>
                    </a>
                </h1>
            </div>
        </header>
        
        <div id="primary" class="content-area">
			<main id="main" class="site-main today-container">
			
				<div style="float: left; margin-bottom: 20px;">
					<a class="indexlink" style="position: relative; top: -2px; font-weight: bold; font-size: 18px; text-decoration: none;" href='../garden/'>INDEX</a>
				</div>
				<div style="clear:both;"></div>
		
				<article>
				<div class="section">
				<form name="newpage" id="newpage" action="dopage.php" method="post" >
  					<input accesskey="p" type="text" name="pagetitle" id="pagetitle" placeholder="Page" style="width: 145px; font-size: 14px;" required>
  					<input type="hidden" name="parent" value="<?php echo $parent; ?>">
					<input accesskey="n" type="submit" name="submit2" id="submit2" value="New name" style="border-color: #ccc; color: #aaa; top: 0px; font-size: 85%; height: 25px;" >
				</form>
				</div>
				</article>
				
			</main>
		</div>
	</div>
	<style>
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
		
		
		<?php
}

?>