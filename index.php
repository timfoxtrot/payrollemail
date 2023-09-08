<?php

/*****************************************************************************
*	File: 		index.php
*	Purpose: 	main page. login and then page functions
*	Author:		Tim Dominguez (timfox@coufu.com)
******************************************************************************/

include 'functions.php';

//Page Handling
switch ($_GET[action]){
    default:			  home();          break;
    case "logout";        logout();        break;
    case "submit";        loginsubmit();   break;
    case "filepath";      pathsubmit();    break;
    case "activate";      activate();      break;
    case "deactivate";    deactivate();    break;
    case "ppe";           ppesubmit();     break;
}

//default page
function home(){

    //display top header
    payroll_top(NULL, 400);

    //debuginfo();

    if(!$_COOKIE[userid]){
		$table = new Ctable;
		$table->pushth( "LOGIN" );
		$table->push( '<font size="5">username</font>', inputtext( "username", '',0,0, "login"));
		$table->push( '<font size="5">password</font>', inputpw ( "password", '',0,"login" ) );
		echo "<form action=\"index.php?action=submit\" method=\"post\">";
		$table->show();
		echo "<br>";
		echo "<input type=\"submit\" value=\"Login\" /> ";
		echo "<input type=\"reset\" value=\"Reset\" /> ";
		echo "</form>";
		
	}else{
        main();
    }
}

//login authentication
function loginsubmit(){

    //connecting to database
    $db = new MyDB;
    $db->query("SELECT * FROM users where username = '$_POST[username]'");
    $row = $db->getrow();

    //error check
    $errors = array();
    
    if(!row)                                    array_push($errors, "username does not exist");
    if(md5($_POST[password]) != $row[password]) array_push($errors, "wrong password");
    if($row[active] != 1)                       array_push($errors, "username does not exist");

    //hard stop if errors exist
    if($errors){
        
        payroll_top("ERROR LOGGING IN");

        $table = new Ctable;
		$table->setwidth ( "400" );
		$table->pushth ( "Error Logging In" );
		$table->show();
		
		//Printing List of Errors
		foreach ( $errors as $error ) 
		{
			echo "<li>$error \n";
			echo "<br><br>";
			ticket_bottom();
			exit;
		}
    }

    //Logging
	$insertlog[userid] = $row[id];
	$insertlog[date]   = time();
	$db = new MyDB;
	$db->insertarray("log", $insertlog);
	
	//Setting Cookie (2hours)
	setcookie ( "userid", $row[id], time()+7200); 

    //Redirect
    redirect("index.php", 0, "Login Success", "");
}

//logout
function logout(){
    setcookie("userid");
    redirect("index.php", 0, "", "");
}

//path update
function pathsubmit(){
    if (!$_POST[path]){
		redirect ('index.php', 2, 'Error', 'No Fields');
		exit;
	}
	else{
        $str = str_replace('\\', '/', $_POST[path]);
        $str .= "/";
		$insert[path]   = $str;
		$db = new MyDB;
		$db->insertarray( "folderpath" , $insert );
		redirect ('index.php', 0);
	}	
}

//activate employee
function activate(){
    $db = new MyDB;
    $db->query("UPDATE employee_tbl SET status = 1 WHERE empno = " . $_GET[empno] . " LIMIT 1" );
	redirect( "index.php", 0 );
}

//deactivate employee
function deactivate(){
    $db = new MyDB;
    $db->query("UPDATE employee_tbl SET status = 0 WHERE empno = " . $_GET[empno] . " LIMIT 1" );
	redirect( "index.php", 0 );
}

//set ppedate
function ppesubmit(){
    if (!$_POST[ppe]){
		redirect ('index.php', 2, 'Error', 'No Fields');
		exit;
	}
	else{
        $str = str_replace('\\', '/', $_POST[ppe]);
		$insert[ppe]   = $str;
		$db = new MyDB;
		$db->insertarray( "ppedate" , $insert );
		redirect ('index.php', 0);
	}	
}
//main page
function main(){

    $table = new Ctable;
    $table->pushth("SPECIFY FOLDER PATH");
    $table->push( '<font size="5"></font>', inputtext( "path", '',0,0, "path"));
    echo "<form action=\"index.php?action=filepath\" method=\"post\">";
    $table->show();
    echo "<br>";
    echo "<input type=\"submit\" value=\"SUBMIT\" /> ";
    echo "<input type=\"reset\" value=\"RESET\" /> ";
    echo "</form>";

    $displaypath = 'Most Current Path:<br>';
    $displaypath .= getfolderpath();
    
    echo $displaypath; 
    echo '<br><br>';

    $ppe = '<r><input type="text" name="ppe" id="datepicker" size="28">';

    $ppedate .= getppedate();

    $table = new Ctable;
    $table->pushth("Current PPE Date: $ppedate");
    $table->push(''.$ppe.'' );
    echo "<form action=\"index.php?action=ppe\" method=\"post\">";
    $table->show();
    echo "<br>";
    echo "<input type=\"submit\" value=\"UPDATE\" /> ";
    echo "<input type=\"reset\" value=\"RESET\" /> ";
    echo "</form>";

    echo '<a href="email.php"><b>SEND MASS EMAIL</b></a>';

    echo '<br><br>';


    $db = new MyDB;
    $db->query("SELECT * from department ORDER by deptno ASC");

    //view menu
    viewmenu();

    while($rowdept = $db->getrow()){

        //default view
        $viewquery = "SELECT * from employee_tbl WHERE deptno = '$rowdept[deptno]' ORDER by empno ASC";

        //active view
        if($_GET[view] == "active")     $viewquery = "SELECT * from employee_tbl WHERE deptno = '$rowdept[deptno]' AND status = 1 ORDER by empno ASC";
        if($_GET[view] == "inactive")   $viewquery = "SELECT * from employee_tbl WHERE deptno = '$rowdept[deptno]' AND status = 0 ORDER by empno ASC";
        
        $db2 = new MyDB;
        $db2->query($viewquery);

        if($db2->getnumrows() > 0){


            $deptname  = getdeptname($rowdept[deptno]);
            $empcount = $db2->getnumrows();

            $table = new CTable;
            $table->setwidth(800);
            $table->setspacing(0);
            $table->setpadding(6);
            $table->setcolprops( 'width="200" class="tdtable"', 'width="230" bgcolor="ebebeb" class="tdtable"', 
                                'width="100" bgcolor="white" class="tdtable"', 
                                'width="100" bgcolor="ebebeb" class="tdtable"', 
                                'width="20" class="tdtable"','width="200" class="tdtableright" bgcolor="ebebeb"');
            $table->pushth( "$deptname","", "<a href=\"email.php?action=deptemail&deptno=$rowdept[deptno]\">[send dept email]</a>", "Count: $empcount");

            while($emprow = $db2->getrow()){
                if($emprow[status] == 1){
                    $status = getstatus($emprow[status]);
                    $table->push("$emprow[lastname] $emprow[firstname]",
                                 "$emprow[email]","<a href=\"email.php?action=oneemail&empno=$emprow[empno]\"><font size=\"1\">Send Email</a></font>", "$status <font size=1><a href=\"index.php?action=deactivate&empno=$emprow[empno]\">(deactivate)</a>");
                }
                if($emprow[status] == 0){
                    $status = getstatus($emprow[status]);
                    $table->push("<s>$emprow[lastname] $emprow[firstname]</s>",
                                 "<s>$emprow[email]</s>","<s><font size=\"1\">Send Email</a></s></font>", "$status <font size=1><a href=\"index.php?action=activate&empno=$emprow[empno]\">(activate)</a>");
                }
             }
            $table->show();
            echo '<br><br>';

        } 
    }
}
?>