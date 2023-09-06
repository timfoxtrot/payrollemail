<?php
/*****************************************************************************
*	File: 		users.php
*	Purpose: 	Contains pages that deal with user management.
*	Author:		Tim Dominguez (timfox@coufu.com)
*   Date:       8/30/2023
******************************************************************************/

include 'functions.php';

switch($_GET[action]){
    case "changepw";   users_only(); changepw();      break;
    case "submit";     users_only(); usersubmit();    break;
    case "resetpw";	   users_only(); resetpw();		  break;
    case "deleteuser"; users_only(); deleteuser();    break;
    default:           users_only(); users_default(); break;
}

//Default Page for Users
function users_default(){

    payroll_top("User Management");
    
    //Changing Password
	$table = new CTable; 
	$table->setwidth(600);
	$table->pushth( "Change Password" );
	$table->push ( "New Password: ", inputpw( "password" ) );
	$table->push ( "Retype Password: ", inputpw( "password2" ) );
	
	echo "<form action=\"users.php?action=changepw\" method=\"post\">";
	$table->show();
	addcoolline(600);
	echo "<input type=\"submit\" value=\"Change Password\" /> ";
	echo "<input type=\"reset\" value=\"Reset\">";
	echo "</form>";
	echo "<br><br>";

    //Add User
    $table = new CTable;
    $table->setwidth(600);
    $table->pushth ( "Add User" );
    $table->push ( "Username: ", inputtext( "username" ) );
    $table->push ( "Full Name: ",inputtext( "fullname" ) );
    $table->push ( "Email: ", inputtext( "email" ) );

    echo "<form action=\"users.php?action=submit\" method=\"post\">";
    $table->show();
    addcoolline(600);
    echo "<input type=\"submit\" value=\"Add User\" /> ";
    echo "<input type=\"reset\" value=\"Reset\">";
    echo "</form>";

    //User Management
    sql_connect();
    $result1 = mysql_query ( "SELECT * FROM users WHERE active ='1'");
    
    $table = new CTable;
    $table->setwidth(600);
    $table->setspacing(0);
    $table->setpadding(6);			
    $table->setcolprops( 'width="100" class="tdtable"', 'width="355" class="tdtable"', 'class="tdtableright"');
    $table->pushth( "Existing Users" );
    while ( $row1 = mysql_fetch_array( $result1, MYSQL_ASSOC ) )
        $table->push ( "$row1[username]", "$row1[email]<br>", "<center><font size=\"1\"><a href=\"users.php?action=resetpw&id=$row1[id]\">Reset PW</a><br><a href=\"users.php?action=deleteuser&id=$row1[id]\">Delete</a>");
    $table->show();

}

//Change Password Submit
function changepw(){
    if($_POST[password] != $_POST[password2]) {
        redirect( "users.php", 2, "Error", "Passwords don't match" );
		exit;
    }else{
        $newpass = md5 ( $_POST[password] );
		$db = new MyDB;
		$result = mysql_query ( "UPDATE users SET password = '$newpass' WHERE id = '$_COOKIE[userid]' LIMIT 1");
		redirect ( "users.php", 0, "Success", "You have successfully changed your password" );
    }
}

//Add User Submit
function usersubmit(){
    
    //Error Check
	$errors = array();
	if (!$_POST[username]) 						array_push ( $errors, "No username");
	if (!$_POST[fullname]) 						array_push ( $errors, "No fullname");
	if (!$_POST[email])							array_push ( $errors, "No Email" );
	
	//If there are errors this prints out a list of them and kills the script
	if ( $errors )
	{
		ticket_top( "Error");
		echo "Error Adding User";
		foreach ( $errors as $err )
		echo "<li>$err \n";
		exit;
	}
	//If there are no errors this adds the user to the database
	$insert[username] = "$_POST[username]"; 
	$insert[fullname] = "$_POST[fullname]";
	$insert[password] = md5("Gmha2023!");
	$insert[email]	  = "$_POST[email]";
	$insert[groupid]  = "1";	
	$insert[active]   = "1";

	$db = new MyDB;
	$db->insertarray( "users" , $insert );

	payroll_top ();
	echo "Added";
}

//Reset Password Submit
function resetpw(){
    
    $password = "Gmha2023!";
	$newpw 	  =  md5($password);

	$db = new MyDB;
	$db->query ("UPDATE users SET password = '$newpw' WHERE id = '$_GET[id]'");
	
	redirect ("users.php", 0, "Success", "The password has been reset to $password");
}

//Delete User
function deleteuser(){
	$db = new MyDB;
	$db->query('SELECT * FROM users WHERE id = '.$_GET[id].'');
	$row = $db->getrow();
	if ($row[id] != 1){
		$db->query( "UPDATE users SET active = 0 WHERE id = " . $_GET[id] . " LIMIT 1" );
		redirect( "users.php", 0 );
	}else{
		//redirect ("admin.php", 4, "", "");
	}
}

?>