<?php
/*****************************************************************************
*	File: 		functions.php
*	Purpose: 	contains all functions in the payroll email system
*	Author:		Tim Dominguez (timfox@coufu.com)
*   Updated:    8/29/2023
******************************************************************************/

//Library and dependencies
include 'drtlib.php';
include 'config.php';

//displays top header
function payroll_top($title = NULL, $width = 600, $refresh = NULL){
    
    echo "<html>";
	echo "<head>";

    //Refresh on admin page
    if ($refresh == "YES") echo "<meta http-equiv=\"refresh\" content=\"10\">";
	
	//Begin html/java
	?>
	
	<link rel="stylesheet" type="text/css" href="style.css">
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<link rel="stylesheet" href="js/jquery-ui-1.10.2.custom.min.css" />
			<script src="https://code.jquery.com/jquery-3.6.3.js"></script>
			<script src="js/jquery-ui-1.10.2.custom.min.js"></script>
			<link rel="stylesheet" href="/resources/demos/style.css" />
			<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.js"></script>
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.css">
			<script>
				$(function() {
				$( "#datepicker" ).datepicker();
				$( "#datepicker2" ).datepicker();
			});
			</script>
			<script>
				$(function (){
				$("#phonenumber").mask("(999) 999-9999");
				});
			</script>
			<script>
    			$(document).ready(function () {
        		$("#formABC").submit(function (e) {

            //stop submitting the form to see the disabled button effect
            //e.preventDefault();

            //disable the submit button
            $("#btnSubmit").attr("disabled", true);

            //disable a normal button
            $("#btnTest").attr("disabled", true);

            return true; });});</script>
            
            <script type="text/javascript">
            function selectFolder(e) {
                var theFiles = e.target.files;
                var relativePath = theFiles[0].webkitRelativePath;
                var folder = relativePath.split("/");
                alert(folder[0]);
            }
            </script>
	<?php

	$logo = logo();

	echo "<title>GMHA PAYROLL ";
	if($title)	echo "[ $title ]";
	echo "</title>";
	echo "</head>";
	echo "<body>";
	echo "<center>";
	$tbl = new Ctable;
	$tbl->push( array ( "<a href=\"index.php\">$logo</a>" ) );
	$tbl->show();
	echo "<br>";

    if( $_COOKIE[userid]) navigation();

}

//Adding a cool looking line
function addcoolline($width = 450){
	$table = new Ctable;
	$table->setwidth( "$width" );
	$table->pushth( " " );
	$table->show();
}

//login check
function users_only(){
    
    if (!$_COOKIE[userid]) $error = TRUE; 

    if ($error){
		redirect("index.php", 2,  "Access Denied", "You do not have correct privileges to view this page");
		exit;
	}
}

//navigation
function navigation(){
    $username = getusername($_COOKIE[userid]);
    $table = new Ctable;
    $table->setwidth("$width");
    $table->push( "Hello, <b>$username</b>", "| <a href=\"index.php\">Home</a>", "| <a href=\"users.php\">Users</a>", "| <a href=\"report.php\">Report</a>",  "| <a href=\"index.php?action=logout\">Logout</a>" );
    $table->show();
}

//View menu
function viewmenu(){
	$table = new Ctable;
    $table->setwidth( "$width" );
    $table->push( "<a href=\"index.php\">View All</a>", "| <a href=\"index.php?view=active\">View Active Only</a>", "| <a href=\"index.php?view=inactive\">View Inactive</a>");
    $table->show();
}

//Getting the username from the userid
function getusername($userid){
	sql_connect();
	$result   = mysql_query("SELECT fullname FROM users WHERE id = '$userid'");
	$row      = mysql_fetch_array ($result, MYSQL_ASSOC);
	$username = $row[fullname];
	
	return $username;
}

//Get departname from number
function getdeptname($deptno){
    sql_connect();
	$result     = mysql_query("SELECT deptdesc FROM department WHERE deptno = '$deptno'");
	$row        = mysql_fetch_array ($result, MYSQL_ASSOC);
	$department = $row[deptdesc];
	
	return $department;
}

//Get Active Status
function getstatus($active){

	if($active == 0) $status = "INACTIVE";
	if($active == 1) $status = "ACTIVE";

	return $status;
}

//Get most current folderpath
function getfolderpath(){
    sql_connect();
    $result = mysql_query( "SELECT * FROM folderpath ORDER BY id DESC LIMIT 1" );
	$row    = mysql_fetch_array ($result, MYSQL_ASSOC);
	$path   = $row[path];
	
	return $path;
}

//Get most current folderpath
function getppedate(){
    sql_connect();
    $result =   mysql_query( "SELECT * FROM ppedate ORDER BY id DESC LIMIT 1" );
	$row    = mysql_fetch_array ($result, MYSQL_ASSOC);
	$ppe    = $row[ppe];
	
	return $ppe;
}

?>