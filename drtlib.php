<?php
//
// 	Copyright (c) DRT Web Solutions. All rights reserved.
//
//--------------------------------------------------------------------------------
//
// 	File: drtlib.php
//
// 	Contains all common functions used by DRT Web Solutions 
//
//--------------------------------------------------------------------------------


//--------------------------------------------------------------------------------
// 	Function Declarations
//
//	NULL	debuginfo(NULL)
//	NULL	error(STRING $message, BOOL $fatal)
//	STRING	input ............... <---------- need to document
//	INT		rcut(STRING &$string, STRING $tocut)
//	NULL	redirect(STRING $url)
//	ARRAY	stripslashesfromarray(ARRAY $array) <---- NEED TO DOCUMENT
//	STRING 	validate_email(STRING $email_raw)
//


//--------------------------------------------------------------------------------
// 	Class Declarations
//
//	class CError
//	class CMySql
//	class CPage
//	class CTable

//Disabling notice error reporting (xammpp)
error_reporting(E_ERROR | E_WARNING | E_PARSE);

//--------------------------------------------------------------------------------
// 	Definitions
define( NL,			"<br />\n" );
define( REFERER,		$_SERVER['HTTP_REFERER'] );
define( SELF,			$_SERVER['PHP_SELF'] );

//--------------------------------------------------------------------------------
//	Functions


/********************************************************************************
 *
 *	FUNCTION:		openform
 *
 *	DESCRIPTION:	Nothing... lol ok
 *
 *	PARAMETERS:
 *					None OLOL
 *
 *	RETURNS:
 *					None LOLOLOL
 *
 ********************************************************************************/
function openform($filename, $defaults = NULL, $action = NULL, $method = NULL, $submitvalue = NULL, $name = 'form')
{
	$tbl			= new CTable;			// HTML table clsas
	$file 			= file($filename);		// opening the file
	$hasfilefield	= false; 				// has a file field wtf?
	
	if(!$defaults) $defaults = $_POST;
	
	// formatting the table
	$tbl->setwidth("100%");
	$tbl->setcolwidths("100%","1");
	$tbl->setextra('class="glabform"');
	
	//////////////////////////////////////////
	// ARE THERE ANY FILE FIELDS???
	foreach($file as $line)
	{
		// extracting the varialbles outof th eline
		$vars = parsepairs($line, "\t", "===");
		if($vars['type'] == "file")
			$hasfilefield = true;	// YES IT DOES!!!!
		
	}
	
	// putting the form thing if they put an action
	if($action)	echo "<form name=\"$name\" class=\"form-$name\" action=\"$action\" method=\"".($method?$method:"post")."\"".($hasfilefield?" enctype=\"multipart/form-data\"":"").">";

	
	///////////////////////////////////////////////
	// CREATING THE FORM...
	foreach($file as $line)
	{
		// resetting the fields variable
		$fields = array();
		
		// checking if the line is commented out
		if(substr($line, 0, 2) == '//')		continue;

		// Putting everything into  variables...
		$fields = parsepairs($line, "\t", "===");
		
		// Adding a class to the title
		$fields['title'] = '<div class="fieldtitle">'.$fields['title'].'</div>';
		
		// adding subtitle
		if($fields['subtitle'])
			$fields['title'] = $fields['title'] . "<p class=\"formsubtitle\">$fields[subtitle]</p>";
		
		// now going thru the options
		switch($fields['type'])
		{
			/////////////////////////
			//	CREATING <form ...>
			case 'action':		
			
				// chekcing if they passed an action... if they did, then quit this
				if($action) break;
				
				// Checking if there's a query string
				if(strstr($fields['target'],"?"))
				{
					$action = "";
					
					list($filename,$querystring) = split("\?",$fields['target']);
					
					$action .= "$filename" . "?";
					
					$pairs = split("\&",$querystring);
					foreach($pairs as $pair)
					{
						list($var,$val) = split("\=",$pair);
						
						$action .= "$var=";
						
						
						// checking if there needs a GET
						if(strstr($val,"|||"))
						{
							list($vartype,$valtype) = split("\|\|\|", $val);
							if($vartype=="GET") $action .= $_GET[$valtype];
							
						}
						else { $action .= "$val"; }
						
						$action .= "&";
						
					}
					
					// Taking out the last ampersand
					rcut($action, "&");
				}
				else { $action = $fields['target']; }
				
				// Creating the <form> tag
				echo 	"\n\n".													// some newlines...
						"<form action=\"$action\" method=\"".					// starting field <form ...
						($fields['method']?$fields['method']:"post")."\"".		// default method is POST
						($fields['id']?" id=\"".$fields['id']."\"":"").			// adding ID field
						($hasfilefield?" enctype=\"multipart/form-data\"":"").	// adding enctype if it has a filefield
						">\n\n";												// ending field ...>
			break;
			
			/////////////////////////////////
			// ERRORS
			case 'errors':
				// kill if no query
				if(!$_GET['er'])break;
			
				// getting the error messages
				$pairs = split("&",$fields['values']);
				foreach($pairs as $pair)
				{
					list($var,$val) = split("=",$pair);
					$errormessage[$var] = $val;
				}
				
				// showing the error message(s)
				echo "<div class=\"error\">";
				if($_GET['er'])
				{
					$errors = split(",",$_GET['er']);
					foreach($errors as $error)
						echo $fields['before'] . $errormessage[$error] . $fields['after'];
				}
				echo "</div>";
			break;
			
			/////////////////////////////////
			// DROPDOWN MENU
			case 'dropdown':
				$options = explode(",", $fields['options']);
				$tbl->push($fields['title'], inputselect($fields['name'],$options,$defaults[$fields['name']]));
			break;
			
			///////////////////////////////
			// CREATING FIELDS....
			case 'header':		$tbl->push("<div class=\"formheader\">$fields[text]</div>");																break;
			case 'text':		$tbl->push($fields['title'],inputtext($fields['name'],htmlentities($defaults[$fields['name']]),$fields['size'])); 			break;
			case 'password':	$tbl->push($fields['title'],inputpw($fields['name'],$defaults[$fields['name']],$fields['size'])); 							break;
			case 'textarea':	$tbl->push($fields['title'],inputtextarea($fields['name'],$defaults[$fields['name']],$fields['width'],$fields['height'])); 	break;
			case 'date':		$tbl->push($fields['title'],inputdate($fields['name'],$defaults[$fields['name']]));											break;
			case 'time':		$tbl->push($fields['title'],inputtime($fields['name'],$defaults[$fields['name']]));											break;
			case 'submit':		$tbl->push(inputsubmit(($submitvalue?$submitvalue:$fields['value'])));														break;
			case 'spacer':		$tbl->push("&nbsp;");																										break;
			case 'file':		$tbl->push($fields['title'],inputfile($fields['name'],$fields['maxfilesize'],$fields['quantity']));							break;
			case 'captcha':		$tbl->push(inputcaptcha());																									break;
			default:																																		break;
		}
		
	}
	$tbl->show();
	echo "</form>";
}


/********************************************************************************
 *
 *	FUNCTION:		debuginfo
 *
 *	DESCRIPTION:	This function shows all the contents of $_POST, $_COOKIE, 
 *					and $_GET in a nice format. Very usefull for development
 *					purposes.
 *
 *	PARAMETERS:
 *					None
 *
 *	RETURNS:
 *					None
 *
 ********************************************************************************/
function debuginfo()
{
	for($i=0;$i<100;$i++)	echo "\n";
	for($i=0;$i<10;$i++)	echo "<!------------------------------------------------------------->\n";
	
	echo "<div style=\"border: solid 10px black; background-color: white; color: black; margin: 150px; padding: 20px; clear: both; position: relative; top: 50px; \">";
	echo "<h1>DEBUG INFO...</h1>";
	/* Getting the variables */
	$c = $_COOKIE;
	$p = $_POST;
	$q = $_GET;
	
	/* Creating a new table and setting properties */
	$tbl = new CTable(0,3,0,"100%");
	$tbl->setaltrows( "class", array( 1,2 ) );
	$tbl->setcolprops( "width=50%", "width=0", "width=50%" );

	/* If cookie(s) exist */
	if( $c )
	{
		$tbl->pushth( "Cookies" );
		while( list( $var, $val ) = each( $c ) )
			$tbl->push( $var, " => ", $val );
		$tbl->show();
		echo "<br>";
	}

	/* If postdata exists */
	if( $p )
	{
		$tbl->popall(); // just in case
		$tbl->pushth( "Post Data" );
		while( list( $var, $val ) = each( $p ) )
			$tbl->push( $var, " => ", $val );
		$tbl->show();
		echo "<br>";
	}

	/* If a query string exists */
	if( $q )
	{
		$tbl->popall(); // just in case
		$tbl->pushth( "Query String" );
		while( list( $var, $val ) = each( $q ) )
			$tbl->push( $var, " => ", $val );
		$tbl->show();
		echo "<br>";
	}
	
	if( $_FILES )
	{
		$tbl->popall(); // just in case
		$tbl->pushth( "Files" );
		while( list( $var, $val ) = each( $_FILES ) )
			$tbl->push( $var, " => ", '<pre>'.print_r($val,true).'</pre>' );
		$tbl->show();
		echo "<br>";
	}
	
	echo "<h1>\$_SERVER variable...</h1>";
	echo "<pre>";
	print_r($_SERVER);
	echo "</pre>";
	
	echo "</div>";

}

//-----------------------------------------------------------------------------
//	Function:	warning
//	Purpose:	Posts a small HTML warning in red background and white text
//-----------------------------------------------------------------------------
function warning( $message )
{
	echo "<div style=\"background-color: red; color: white; padding: 2; margin: 1; \"><b>Warning:</b> $message</div>";
}

function mysql_time_format($time, $format = 'g:ia')
{
	list($hour,$min,$sec) = split(':',$time);
	$mktime = mktime($hour,$min,$sec,12,12,2000);
	return date($format,$mktime);
}

function mysql_date_format($date, $format = 'long', $return = 'string')
{
	switch($format){
		case 'short': $format = 'D, M j, Y'; break;
		case 'long': $format = 'l, F jS, Y'; break;
		default: break;
	}
	
	/* TODO!!! */	
	list($year,$month,$day) = split('-',$date);
	$mktime = mktime(0,0,0,$month,$day,$year);
	return date($format,$mktime);
}


/********************************************************************************
 *
 *	FUNCTION:		error
 *
 *	DESCRIPTION:	Displays an error message. Kills script if $fatal is true
 *
 *	PARAMETERS:
 *					STRING $message - Error message
 *					BOOL $fatal - If 'true', kills script.
 *
 *	RETURNS:
 *					None
 *
 ********************************************************************************/
function error($message, $fatal = true)
{
	/* Starting up an error class */
	$err = new CError;

	/* Sets a string depending on the kind of error */
	$errtype = ( $fatal ? "Fatal Error" : "Error" );

	/* Pushing the error and then showing it */
	$err->push( $errtype, $message );
	$err->show();
	
	// send an e-mail
	//mail(
	$headers = 	'MIME-Version: 1.0' . "\r\n" .
				'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: GuamCollegeBooks.com <no-reply@guamcollegebooks.com>' . "\r\n" .
	'Reply-To: GuamCollegeBooks.com <no-reply@guamcollegebooks.com>' . "\r\n" .
	'X-Mailer: PHP/' . phpversion();
	
	$mail_to = 'dc@gecko-labs.com';
	$mail_subj = 'GCB ERROR';
	
	$message = print_r($_SERVER,true);
	$message .= print_r($_COOKIE,true);
	
	$fp = fopen(time().'.txt','w');
	fwrite($fp, $message);
	fclose($fp);
	
	// mail($mail_to,$mail_subj,$message,$headers);
	
	/* If it was a fatal error then end prematurely */
	if( $fatal )
		exit;
}

//----------------------------------------------------------------------
//----------------------------------------------------------------------
function ordinal($cdnl){
    $test_c = abs($cdnl) % 10;
	$ext = ((abs($cdnl) %100 < 21 && abs($cdnl) %100 > 4) ? 'th'
            : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1)
            ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
    return $cdnl.$ext;
}  

//-----------------------------------------------------------------------------
//
//-----------------------------------------------------------------------------
function formatphoneno($phoneno)
{
	// taking out all characters they might have used:
	$stripchars = array("-","(",")", " ", "/", "\\");

	$phoneno = str_replace($stripchars,"",$phoneno);
	
	// making sure the phone number is 10 numbers long
	if(strlen($phoneno) != 10)
		return false;
		
	// formatting the phone number
	$newphoneno  = "(";
	$newphoneno .= substr($phoneno, 0, 3);
	$newphoneno .= ")";
	$newphoneno .= substr($phoneno, 3, 3);
	$newphoneno .= "-";
	$newphoneno .= substr($phoneno, 6, 4);
	
	return $newphoneno;
	
}


//-----------------------------------------------------------------------------
//	Function:	filtertime
//	Purpose:	Takes a postdata and filters out the split times into a MySQL
//				formatted time.
//-----------------------------------------------------------------------------
function filterdatetime( &$post )
{
	// Creating an array to store errors
	$errs = array();

	
	// going thru postdata... 
	while( list( $var, $val ) = each( $post ) )
	{
		///////////////////////////////////////////////////////////////
		//	FILTERING TIME
		///////////////////////////////////////////////////////////////
		// if there's a match
		if( preg_match( "/^drt_hour_/", $var ) ) 
		{
			// Getting the variable name
			$name = str_replace( "drt_hour_", "", $var );
			
			// If Everything Okay...
			if(	$post['drt_hour_'.$name] 		>= 1 	&& 	// HOUR BETWEEN 1 and 12
			   	$post['drt_hour_'.$name] 		<= 12 	&&	
			   	$post['drt_minute_'.$name]		>= 0 	&&	// MINUTE BETWEEN 0 and 50
			   	$post['drt_minute_'.$name]		<= 59	&&
			   	( $post['drt_ampm_'.$name]	== "AM"		||	// EITHER HAS TO BE AM or PM
				  $post['drt_ampm_'.$name]	== "PM")
			) 
			{ 
				// Converting AM/PM to 24 hour time
				if($post['drt_ampm_'.$name] == "AM" && $post['drt_hour_'.$name] == 12)
					$post['drt_hour_'.$name] = 0;

				if($post['drt_ampm_'.$name] == "PM" && $post['drt_hour_'.$name] != 12)
					$post['drt_hour_'.$name] += 12;
					
				$post[$name] = $post['drt_hour_'.$name] . ":" . $post['drt_minute_'.$name] . ":00"; 
			} 
			
			// If not okay, then error
			else { $post[$name] = $post['drt_hour_'.$name] . ":" . $post['drt_minute_'.$name] . " " . $post['drt_ampm_'.$name];  $errs[$name] = "yes"; }
		
		}


		///////////////////////////////////////////////////////////////
		//	FILTERING DATE
		///////////////////////////////////////////////////////////////
		// if there's a match
		if( preg_match( "/^drt_month_/", $var ) ) 
		{
			// Getting the variable name
			$name = str_replace( "drt_month_", "", $var );

			// setting the date to MySQL 
			// If Everything Okay...
			if(	$post['drt_year_'.$name] 		>= 1000	&&	// YEAR BETWEEN 1000 and 3000
			   	$post['drt_year_'.$name] 		<= 3000	&&	
			   	$post['drt_month_'.$name]		>= 1 	&&	// MONTH BETWEEN 1 and 12
			   	$post['drt_month_'.$name]		<= 12	&&
			   	$post['drt_day_'.$name]			>= 1	&&	// DAY BETWEEN 1 and 31
				$post['drt_day_'.$name]			<= 31 
			) { $post[$name] = $post['drt_year_'.$name] . "-" . $post['drt_month_'.$name] . "-" . $post['drt_day_'.$name]; } 
			
			// If not okay, then error
			else { $post[$name] = $post['drt_year_'.$name] . "-" . $post['drt_month_'.$name] . "-" . $post['drt_day_'.$name]; $errs[$name] = "yes"; }
			
		}


	}

	
	return $errs;
}


//-----------------------------------------------------------------------------
//	Function:	form
//	Purpose:	Posts the HTML form tag
//-----------------------------------------------------------------------------
function form( $action, $method = "post", $name = "form" )
{
	$result = "<form action=\"$action\" method=\"$method\" name=\"$name\">";
	return $result;
}
function formend() { return "</form>"; }
function endform() { return "</form>"; }


//-----------------------------------------------------------------------------
//	Function:	inputcaptcha
//	Purpose: 	Put a captcha YAYAYA
//-----------------------------------------------------------------------------
function inputcaptcha()
{

	$return = NL .'<img src="securimage_show.php?sid='.md5(uniqid(time())).'" id="cc" align="absmiddle" />';
	$return .= '<a href="securimage_play.php" style="font-size: 13px"><img src="images/audio_icon.gif" border="0" /></a>';
	$return .= "<a href=\"#\" onclick=\"document.getElementById('cc').src = 'securimage_show.php?sid=' + Math.random(); return false\"><img src=\"images/refresh.gif\" border=\"0\" \></a><br />";
	$return .= 'Copy Above Characters: <input type="text" name="code" size="4" autocomplete="off" />';
	
	return $return;

}

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
function checkcaptcha()
{
	include("securimage.php");
	$img = new Securimage();
	$valid = $img->check($_POST['code']);
	
	return $valid;
}

//-----------------------------------------------------------------------------
//	Function:	inputfile
//	Purpose:	makes a file input html
//-----------------------------------------------------------------------------
function inputfile($name, $maxfilesize = NULL, $quantity = NULL)
{
	$return		= "";
	
	// setting defaults
	if(!$maxfilesize)	$maxfilesize = 1000000; // 1 megabyte
	if(!$quantity)		$quantity = 1;
	
	// putting the hidden field
	$return .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"$maxfilesize\" />\n";
	
	// Putting the <input> fields
	if($quantity > 1)
	{
		for($i=0; $i<$quantity; $i++)
		{
			$return .= "<input type=\"file\" name=\"$name"."[]\" />" . NL; 
		}
	}
	else
	{
		$return .= "<input type=\"file\" name=\"$name\" />\n";		
	}
	
	return $return;
}

//-----------------------------------------------------------------------------
//	Function:	inputtime
//	Purpose:	Returns 3 select input strings, one for hour, minute, and ampm
//-----------------------------------------------------------------------------
function inputtime($name = NULL, $value = NULL)
{
	// if they put a value.. translate it
	if($value) {
		list($hm,$ampm) = split(" ",$value);
		list($h,$m)		= split(":", $hm);		
		
		// if it's in 24 hour time, convert to old format
		if(!$ampm)
		{
			if($h==0) { $h+=12; $ampm = "AM"; }
			elseif($h<12) { $ampm = "AM"; }
			elseif($h==12)	{ $ampm = "PM"; }
			elseif($h>12)	{ $h-=12; $ampm = "PM"; }
		}
	}
	
	return	inputtext("drt_hour_" . $name, 		($value?$h:NULL),	2, 2) . " : " .
			inputtext("drt_minute_" . $name,	($value?$m:NULL),	2, 2) . " " .
			inputselect("drt_ampm_" . $name, 	array("AM/PM","AM","PM"),					($value?$ampm:NULL),	4, 4);

	/* old
	return	inputtext("drt_hour_" . $name, 		($value?$_POST['drt_hour_'.$value]:NULL),	2, 2) . " : " .
			inputtext("drt_minute_" . $name,	($value?$_POST['drt_minute_'.$value]:NULL),	2, 2) . " " .
			inputselect("drt_ampm_" . $name, 	array("AM/PM","AM","PM"), 					($value?$_POST['drt_ampm_'.$value]:NULL),	4);
	*/
	
}

// old as of 2009/04/22
function OLDinputtime( $name = NULL, $dhour = NULL, $dminute = NULL, $dampm = NULL, $class = NULL )
{
	$hours		= array();
	$minutes	= array();
	$ampms		= array();

	/* Putting the hours */
	for( $i = 1; $i <= 12; $i++ )
		array_push( $hours, $i );

	/* Putting the minutes */
	for( $i = 0; $i < 60; $i++ )
	{
		if( $i < 10 )
			$i = "0$i";
		array_push( $minutes, $i );
	}

	/* Putting AM and PM */
	array_push( $ampms, "AM" );
	array_push( $ampms, "PM" );

	/* Beginning the result */
	$result = "";
	$result .= "<table border=0 cellpadding=0 cellspacing=0>\n";
	$result .= "<tr>\n";
	$result .= "<td>\n";
		
	// Creating the result
	$result .= "<select name=\"hour$name\" class=\"$class\">\n";
	if( !$dhour ) $result .= " <option>Hour</option>";

	foreach( $hours as $hour )
	{
		if( $dhour == $hour )
			$result .= " <option selected=yes>$hour</option>";
		else
			$result .= " <option>$hour</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "<td>\n";
	$result .= "<select name=\"minute$name\" class=\"$class\">\n";
	if( !$dminute ) $result .= " <option>Minute</option>";

	foreach( $minutes as $minute )
	{
		if( $dminute == $minute )
			$result .= " <option selected=yes>$minute</option>";
		else
			$result .= " <option>$minute</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "<td>\n";
	$result .= "<select name=\"ampm$name\" class=\"$class\">\n";
	if( !$dampm ) $result .= " <option>AM/PM</option>";

	foreach( $ampms as $ampm )
	{
		if( $dampm == $ampm )
			$result .= " <option selected=yes>$ampm</option>";
		else
			$result .= " <option>$ampm</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "</tr>\n";
	$result .= "</table>\n";

	return $result;	

}

//-----------------------------------------------------------------------------
//	Function:	inputdate
//	Purpose:	Returns 3 select input strings, one for month, date, and year
//-----------------------------------------------------------------------------
function inputdate($name = NULL, $value = NULL /* MM/DD/YYYY */ )
{
	if($value){
		list($y,$m,$d) = split('-',$value);
		$y = trim($y,'0');
		$m = trim($m,'0');
		$d = trim($d,'0');
	}
	return	inputtext("drt_month_" . $name, 	($value?$m:NULL),	2, 2) . " / " .
			inputtext("drt_day_" . $name,		($value?$d:NULL),	2, 2) . " / " .
			inputtext("drt_year_" . $name, 		($value?$y:NULL),	4, 4);
	
}

// old as of 2009-04-22
function OLDinputdate( $name = NULL, $dmonth = NULL, $dday = NULL, $dyear = NULL, $startyear = NULL, $endyear = NULL, $class = "select" )
{
	// Creating our arrays for Months, Days, and Years
	$months = array();
	$days	= array();
	$years	= array();

	$monthword = array( NULL, "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" );

	/* Getting the number of months */
	for( $i = 1; $i <= 12; $i++ )
		array_push( $months, $i );

	/* Getting the number of days */	
	for( $i = 1; $i <= 31; $i++ )
		array_push( $days, $i );

	/* Checking if they put in a start and end year */
	if( !$startyear )	$startyear	= date( "Y", time() ) - 100;
	if( !$endyear )		$endyear	= $startyear + 100;

	/* Getting this year */
	$currentyear = date( "Y", time() );
	if( $startyear == "*" ) $startyear = $currentyear;
	if( $endyear == "*" ) $endyear = $currentyear;
	if( ereg( "^[+]", $startyear ) )
	{
		lcut( $startyear, "+" );
		$startyear = $currentyear + $startyear;
	}
	if( ereg( "^[-]", $startyear ) )
	{
		lcut( $startyear, "-" );
		$startyear = $currentyear - $startyear;
	}
	if( ereg( "^[+]", $endyear ) )
	{
		lcut( $startyear, "+" );
		$endyear = $startyear + $endyear;
	}
	if( ereg( "^[-]", $endyear ) )
	{
		lcut( $endyear, "-" );
		$endyear = $startyear + $endyear;
	}

	for( $i = $startyear; $i <= $endyear; $i = $i + 1 )
		array_push( $years, $i );
	
	$result = "";
	$result .= "<table border=0 cellpadding=0 cellspacing=0>\n";
	$result .= "<tr>\n";
	$result .= "<td>\n";
		
	// Creating the result
	$result .= "<select name=\"month$name\" class=\"$class\">\n";
	if( !$dmonth ) $result .= " <option>Month</option>";

	foreach( $months as $month )
	{
		if( $dmonth == $month )
			$result .= " <option selected=yes value=\"$month\">$monthword[$month]</option>";
		else
			$result .= " <option value=\"$month\">$monthword[$month]</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "<td>\n";
	$result .= "<select name=\"day$name\" class=\"$class\">\n";
	if( !$dday ) $result .= " <option>Day</option>";

	foreach( $days as $day )
	{
		if( $dday == $day )
			$result .= " <option selected=yes>$day</option>";
		else
			$result .= " <option>$day</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "<td>\n";
	$result .= "<select name=\"year$name\" class=\"$class\">\n";
	if( !$dyear ) $result .= " <option>Year</option>";

	foreach( $years as $year )
	{
		if( $dyear == $year )
			$result .= " <option selected=yes>$year</option>";
		else
			$result .= " <option>$year</option>";
	}

	$result .= "</select>\n";
	$result .= "</td>\n";
	$result .= "</tr>\n";
	$result .= "</table>\n";

	return $result;	

}

//-----------------------------------------------------------------------------
//	Function:	inputradio
//	Purpose:	Returns a single radio input button
//-----------------------------------------------------------------------------
function inputradio( $name, $value, $checked = false, $class = "radio" )
{
	$checkstring = ( $checked ? "checked=\"true\"" : "" );
	$result = "<input type=\"radio\" class=\"$class\" name=\"$name\" value=\"$value\" $checkstring />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputcheck
//	Purpose:	Returns a single check input button
//-----------------------------------------------------------------------------
function inputcheck( $name, $value, $checked = false, $class = "radio" )
{
	$checkstring = ( $checked ? "checked=\"true\"" : "" );
	$result = "<input type=\"checkbox\" class=\"$class\" name=\"$name\" value=\"$value\" $checkstring />";
	return $result;
}
//-----------------------------------------------------------------------------
//	Function:	inputtext
//	Purpose:	Returns a input text HTML tag
//-----------------------------------------------------------------------------
function inputtext( $name, $value = "", $size = 0, $maxlength = 0, $class = "text" )
{
	$sizestr = ( $size ? "size=\"$size\"" : "" );
	$maxlenstr = ( $maxlength ? "maxlength=\"$maxlength\"" : "" );
	$result = "<input type=\"text\" name=\"$name\" value=\"$value\" class=\"$class\" $sizestr $maxlenstr />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputtextarea
//	Purpose:	Returns a input text HTML tag
//-----------------------------------------------------------------------------
function inputtextarea( $name, $value = "", $cols = 50, $rows = 10, $class = "textarea", $placeholder = "" )
{
	$sizestr = ( $size ? "size=\"$size\"" : "" );
	$result = "<textarea name=\"$name\" cols=$cols rows=$rows class=$class placeholder=\"$placeholder\">$value</textarea>";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputpw
//	Purpose:	Returns a password HTML tag
//-----------------------------------------------------------------------------
function inputpw( $name = "password", $value = "", $size = 0, $class = "text" )
{
	$sizestr = ( $size ? "size=\"$size\"" : "" );
	$result = "<input type=\"password\" name=\"$name\" value=\"$value\" class=\"$class\" $sizestr />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputselect
//	Purpose:	Returns a input select when you input an array
//-----------------------------------------------------------------------------
function inputselect( $name, $array, $default = "", $class = "select" )
{
	/* Starting the HTML tag */
	$result  = "<select name=\"$name\" class=\"$class\">\n";

	/* Going thru the array to put the options */
	foreach( $array as $option )
	{
		if( $option == $default && $default != "" )
			$result .= " <option selected=\"selected\">$option</option>\n";
		else
			$result .= " <option>$option</option>\n";
	}

	/* Closing the HTML tag */
	$result .= "</select>\n";

	/* Returning the string */
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputhidden
//	Purpose:	Returns a hidden HTML input thingy
//-----------------------------------------------------------------------------
function inputhidden( $name, $value )
{
	$result = "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputsubmit
//	Purpose:	Returns a submit button 
//-----------------------------------------------------------------------------
function inputsubmit( $value = "Submit", $name = "", $class = "button" )
{
	$result = "<input type=\"submit\" class=\"$class\" name=\"$name\" value=\"$value\" />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	inputreset
//	Purpose:	Returns a reset button
//-----------------------------------------------------------------------------
function inputreset( $value = "Reset", $class = "button" )
{
	$result = "<input type=\"reset\" class=\"$class\" value=\"$value\" />";
	return $result;
}

//-----------------------------------------------------------------------------
//	Function:	parsepairs
//	Purpose:	Parses a string into pairs, such as the query string.
//-----------------------------------------------------------------------------
function parsepairs( $string, $and = "&", $equals = "=" )
{
	// trimming the line...
	$string = trim($string);
	
	/* Splitting the string by $and */
	$pairs = explode( $and, $string );
	
	/* Going thru each pair and then splitting by equals */
	foreach( $pairs as $pair )
	{
		list( $var, $val ) = explode( $equals, $pair );
		$result[$var] = $val;
	}
	
	/* Returning the result */
	return $result;
}


/********************************************************************************
 *
 *	FUNCTION:		rcut
 *
 *	DESCRIPTION:	Removes all $tocut from the right side of the string until
 *					there is no more. 
 *
 *	PARAMETERS:
 *					STRING &$string - The string to cut
 *					STRING $tocut - What to cut
 *
 *	RETURNS:
 *					INT $count - Number of times it cut
 *
 ********************************************************************************/
function rcut( &$string, $tocut )
{
	/* Setting the count to return */
	$count = 0;

	/* Getting the length of the string to cut */
	$strlen = strlen( $tocut );
	
	/* If the last part of the string matches the string to cut */
	while( substr( $string, -$strlen ) == $tocut )
	{
		/* Cut the last part of the string */
		$string = substr( $string, 0, -$strlen );

		/* Increment the count */
		$count++;
	}

	/* Returning the count */
	return $count;
}

/********************************************************************************
 *
 *	FUNCTION:		redirect
 *
 *	DESCRIPTION:	Makes a page to redirect to another page. If you use the 
 *					last 5 parameters, it will show a message. 
 *
 *	PARAMETERS:
 *					STRING $url - URL of site to redirect to
 *					STRING $wait - How many seconds to wait before redirecting
 *					STRING $title - OPTIONAL: If you want a title
 *					STRING $message - OPTIONAL: If you want to put a message
 *					STRING $bodystyle - OPTIONAL: CSS <body> tag
 *					STRING $headstyle - OPTIONAL: CSS for $title
 *					STRING $boxstyle - OPTIONAL: CSS for the Message Box
 *
 *	RETURNS:
 *					None
 *
 ********************************************************************************/
function redirect( 	$url, $wait = 0, $title = "", $message = "", $bodystyle = "", 
				  	$headstyle = "font-family: Tahoma, Verdana, Arial; font-weight: bold; font-size: 14pt; text-align: center;", 
					$boxstyle = "position: absolute; top: 20%; left: 20%; width: 60%; background-color: #00CCFF; border: black 1px solid; padding: 2; text-align: center; font-family: Tahoma, Verdana, Arial; font-size: 10pt; " 
					)
{
	echo "<html>\n";
	echo "<head>\n";
	echo "<meta http-equiv=refresh content=\"$wait; URL=$url\">\n";
	echo "</head>\n";
	echo "<body style=\"$bodystyle\">\n";

	/* Only show box if there is a title */
	if( $title )
	{
		echo "<div style=\"$boxstyle\">\n";
		echo "<div style=\"$headstyle\">$title</div>\n";
		echo "$message" . NL . NL;
		echo "If you don't get redirected, click <a href=$destination>here</a>\n";
		echo "</div>\n";
	}

	echo "</body>\n";
	echo "</html>\n";
	
	exit;
}

/********************************************************************************
 *
 *	FUNCTION:		stripslashesfromarray
 *
 *	DESCRIPTION:	This function is used to take an e-mail address in it will 
 *					tell you if it's in proper form. It will even check if the
 *					server after the "@" sign exists for extra security.
 *
 *	PARAMETERS:
 *					STRING $email_raw
 *					- E-mail Address
 *
 *	RETURNS:
 *					STRING
 *					- Error message if not valid
 *					- "valid" if valid
 *
 ********************************************************************************/
function stripslashesfromarray( $array )
{
	/* Check if it's an array */
	if( !is_array( $array ) )
	{
		warning( "Function stripslashesfromarray: You didn't pass an array" );
		return;
	}

	while( list( $var, $val ) = each( $array ) )
		$result[$var] = stripslashes( $array[$var] );
	
	return $result;
}

/********************************************************************************
 *
 *	FUNCTION:		validate_email
 *
 *	DESCRIPTION:	This function is used to take an e-mail address in it will 
 *					tell you if it's in proper form. It will even check if the
 *					server after the "@" sign exists for extra security.
 *
 *	PARAMETERS:
 *					STRING $email_raw
 *					- E-mail Address
 *
 *	RETURNS:
 *					STRING
 *					- Error message if not valid
 *					- "valid" if valid
 *
 ********************************************************************************/
function validate_email($email_raw)
{
    // replace any ' ' and \n in the email
    $email_nr = eregi_replace("\n", "", $email_raw);
    $email = eregi_replace(" +", "", $email_nr);
    $email = strtolower( $email );
	
    // do the eregi to look for bad characters
    if( !eregi("^[a-z0-9]+([_\\.-][a-z0-9]+)*". "@([a-z0-9]+([\.-][a-z0-9]+))*$",$email) )
	{
    	// okay not a good email
      	$feedback = 'Error: "' . $email . '" is not a valid e-mail address!';
      	return $feedback;
    } 
	else 
	{
      	// okay now check the domain
      	// split the email at the @ and check what's left
      	$item = explode("@", $email);
      	$domain = $item["1"];
      	if ( ( gethostbyname($domain) == $domain ) )
        {
          	if ( gethostbyname("www." . $domain) == "www." . $domain )
           	{
               	$feedback = 'Error: "' . $domain . '" is most probably not a valid domain!';
               	return $feedback;
          	}
        	// ?
       		$feedback = "valid";
       		return $feedback;
   		} 
		else 
		{
       		$feedback = "valid";
       		return $feedback;
   		}
	}
}

//-----------------------------------------------------------------------
//-----------------------------------------------------------------------
function error_message($errs)
{
	echo '<div class="error">';
	echo 'Encountered the following error(s):';
	echo '<ul>';
	foreach($errs as $err)
		echo '<li>'.$err.'</li>';
	echo '</ul>';
	echo '</div>';
}

function validate_url($url) 
{ 
	
	if(preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url))
	{
		$parsed = parse_url($url);
	
		if ( ( gethostbyname($parsed['host']) == $parsed['host'] ) )
		{
				if ( gethostbyname("www." . $parsed['host']) == "www." . $parsed['host'] )
				{
					return false;
				}
				// ?
				return true;
		} 
		return true;
	}
}  

//-----------------------------------------------------------------------
//-----------------------------------------------------------------------
function get_page_title($url)
{
	$page = file_get_contents($url);
	preg_match("/<title>(.*)<\/title>/i",$page,$matches);
	return $matches[1];
}


//-----------------------------------------------------------------------
//	Function:	howlongago
//	Purpose:	Compares the current time (in unix timestamp format)
//				to a previous time given. Returns in the format of:
//
//				xx seconds ago
//				xx minutes ago
//				xx hours ago
//				xx days ago, time
//				if more than a week, return the timestamp
//-----------------------------------------------------------------------
function howlongago($timestamp, $showtime = true, $timefmt = NULL )
{
	// Getting the current time
	$curtime = time();
	
	// setting the time format
	if(!$timefmt)
	{
		$timefmt = "F j, Y".($showtime?" @ g:ia":"");
	}
	
	// Getting the difference in time
	$difftime = $curtime - $timestamp;
	
	// If the timestamp is in the future, lolz ERROR
	if( $difftime < 0 )
		return $timestamp;
		
	// SECONDS
	if( $difftime < 60 )			
	{
		$result = floor( $difftime );
		if( $result == 1 )		
			return $result . " second ago";
		else
			return $result . " seconds ago";
	}
	// MINUTES
	elseif( $difftime < 60 * 60 )			
	{
		$result = floor( $difftime / 60 );
		if( $result == 1 )
			return $result . " minute ago";
		else
			return $result . " minutes ago";
	}
	// HOURS
	elseif( $difftime < 60 * 60 * 24 )		
	{
		$result = floor( $difftime / 60 / 60 ); 
		if( $result == 1 )
			return $result . " hour ago";
		else
			return $result . " hours ago";
	}
	// DAYS
	elseif( $difftime < 60 * 60 * 24 * 7 )	
	{
		$result = floor( $difftime / 60 / 60 / 24 );
		if( $result == 1 )
			return $result .  " day ago" .($showtime?" @ ".date( "g:ia", $timestamp + $timezonediff * 60 * 60):"");
		else
			return $result .  " days ago" .($showtime?" @ ".date( "g:ia", $timestamp + $timezonediff * 60 * 60):"");
	}
	else									
		return date( $timefmt, $timestamp );
}

//--------------------------------------------------------------------------------
//	Classes


/********************************************************************************
 *
 *	CLASS:			CError
 *
 *	DESCRIPTION:	Used to put a list of errors into a table, then shows the
 *					list of errors when called upon
 *
 ********************************************************************************/
class CError
{
	/* Some variables */
	var $error;		// Array of errors
	var $num;		// Number of errors

	//----------------------------------------------------------------------
	//	FUNCTION DECLARATION
	//
	//	NULL __construct()
	//
	//	NULL push(STRING $type, STRING $description)
	//	INT getnum()
	//	BOOL show()
	//----------------------------------------------------------------------

//-------------------------------------------------------------------------
	//	Function:	__construct
	//	Purpose:	Class constructor. Just setting variables to default vals.
	//-------------------------------------------------------------------------
	function __construct()
	{
		$this->error = array();
		$this->num = 0;
	}

	//-------------------------------------------------------------------------
	//	Function:	push
	//	Purpose:	Adds an error to the table
	//-------------------------------------------------------------------------
	function push( $type, $description )
	{
		array_push( $this->error, "$type\t$description" );
		$this->num++;
	}

	//-------------------------------------------------------------------------
	//	Function:	getnum
	//	Purpose:	Get the number of errors
	//-------------------------------------------------------------------------
	function getnum()
	{
		return $this->num;
	}

	//-------------------------------------------------------------------------
	//	Function:	show
	//	Purpose:	Shows the error(s)
	//-------------------------------------------------------------------------
	function show()
	{
		// If there are no errors, return false
		if(!$this->num)	return false;
			
		/* Check if it's plaural or singular */
		if( $this->num > 1 )
			$s = "s have";
		else
			$s = " has";

		/* Creating a table */
		$tbl = new CTable(0, 15, 0, "100%");
		$tbl->setcolprops( "width=100", "" );
		$tbl->setthextra( "style=\"background-color:blue;color:white;\"" );
		$tbl->pushth( "Error", "Description" );
		$tbl->setaltrows( "bgcolor=\"#EEEEEE\"", "bgcolor=\"#CCCCCC\"" );

		foreach( $this->error as $err )
		{
			/* Getting the type of error and description */
			list( $type, $desc ) = explode( "\t", $err );

			/* Push the values into the table */
			$tbl->push($type, $desc);
		}

		/* Showing the table */
		$tbl->show();
		
		// Return true.. there were errors
		return true;
	}
}


/********************************************************************************
 *
 *	CLASS:			CMySQL
 *
 *	DESCRIPTION:	This is to handle MySQL databases. Must be inhereted
 *
 ********************************************************************************/
class CMySQL
{
	private $link;			// Link identifier for the MySQL connection
	private $result;		// Result of a query
	private $numrows;		// Number of rows returned from a SELECT query

	private $lastquery;		// The last query string
	private $fields;		// Field Names
	private $numfields;		// Number of fields
	private $lastinsertid;	// Last Insert ID

	protected $hostname;		// Host address of the MySQL Server
	protected $username;		// Username
	protected $password;		// Password
	protected $database;		// Which database to use
	
	//----------------------------------------------------------------------
	//	FUNCTION DECLARATION
	//
	//	NULL 	__construct()
	//	NULL 	__destruct()
	//	
	//	ARRAY	getrow(STRING $type)
	//	NULL	insertarray(STRING $table, ARRAY $post)
	//	NULL 	query(STRING $query)
	//	NULL	updatearray(STRING $table, ARRAY $array, STRING $where)
	//
	//	ARRAY	getcol(STRING $col)
	//	ARRAY 	getenumoptions(STRING $table, STRING $field)
	//	ARRAY	getfields()
	//	INT		getinsertid()
	//	INT 	getnumfields()
	//	INT		getnumrows()
	//	STRING	getpairs(STRING $type)
	//	STRING	getpw(STRING $password)
	//	STRING 	getsingle()
	//
	//	NULL	showresult()
	//	NULL	swapid(STRING $table, INT $first, INT $second, STRING $idfield)
	//	BOOL	tableexists(STRING $table)
	//
	//----------------------------------------------------------------------
	
	/**********************************************************************
	 *	FUNCTION:		__construct
	 *	DESCRIPTION:	Class constructor. Connects and selects db.
	 *	PARAMETERS:		None.
	 *	RETURNS:		None.
	 **********************************************************************/
	function __construct($hostname = NULL, $database = NULL, $username = NULL, $password = NULL)
	{
		if(!$hostname) $hostname = $this->hostname;
		if(!$database) $database = $this->database;
		if(!$username) $username = $this->username;
		if(!$password) $password = $this->password;
		
		/* Connecting to the database */
		$this->link =	 
			mysql_connect(
				$hostname,
				$username,
				$password
			) or error( mysql_error() );

		/* Selecting the database */
		mysql_select_db( $database ) or error( mysql_error() );
		
	}

	/**********************************************************************
	 *	FUNCTION:		__destruct
	 *	DESCRIPTION:	Class destructor. Disconnects from Db
	 *	PARAMETERS:		None.
	 *	RETURNS:		None.
	 **********************************************************************/
	 function __destruct()
	 {
//		mysql_close( $this->link );
	 }
	 
	//-------------------------------------------------------------------------
	//	Function:	query
	//	Purpose:	Performs a MySQL query on the current database.
	//-------------------------------------------------------------------------
	function query( $query )
	{
		/* Performing the query */
		$query = trim( $query ); // first getting rid of whitespace around edges

		/* getting rid of tabs */
		$query = str_replace( "\t", " ", $query );


		$this->result = mysql_query( $query, $this->link )
							or error( "Error performing the following query:<br><br>$query<br><br> " . mysql_error() );

		/* If it was a select statement, get the number of rows returned */
		if( preg_match( "/^[s][e][l][e][c][t]/i", $query ) )
		{
			/* Getting the number of rows */
			$this->numrows = mysql_num_rows( $this->result );

			/* Getting the number of fields */
			$this->numfields = mysql_num_fields( $this->result );

			/* Getting the name of the fields */
			for( $i = 0; $i < $this->numfields; $i++ )
				$this->fields[$i] = mysql_field_name( $this->result, $i );
		}

		/* Setting the last query */
		$this->lastquery = $query;

	}

	//-------------------------------------------------------------------------
	//	Function:	getenumoptions
	//	Purpose:	Get the enumeration options from a certain field
	//-------------------------------------------------------------------------
	function getenumoptions( $table, $field )
	{
		$result = mysql_query( "SHOW COLUMNS FROM $table LIKE '$field'" );
		
		if( mysql_num_rows( $result ) > 0)
		{
			$field = mysql_fetch_row( $result );
			$options = explode( "','", preg_replace("/(enum|set)\('(.+?)'\)/" , "\\2" , $field[1] ) );
		}	
		
		return $options;
	}

	//-------------------------------------------------------------------------
	//	Function:	getnumfields
	//	Purpose:	Get the number of fields from the last query
	//-------------------------------------------------------------------------
	function getnumfields()
	{
		return $this->numfields;
	}

	//-------------------------------------------------------------------------
	//	Function:	getsingle
	//	Purpose:	Returns the first element from the table
	//-------------------------------------------------------------------------
	function getsingle($query = NULL)
	{
		if($query) $rows = $this->getresults($query);
		return $rows[0][0];
	}
	function getvar($query) { return $this->getsingle($query); }

	//-------------------------------------------------------------------------
	//	Function:	getsingle
	//	Purpose:	Returns the first element from the table
	//-------------------------------------------------------------------------
	function getresult($query = NULL, $object = false)
	{
		if($query) $rows = $this->getresults($query, $object);
		return $rows[0];
	}

	//-------------------------------------------------------------------------
	//	Function:	getcol
	//	Purpose:	Gets a single column (given the column number or name)
	//-------------------------------------------------------------------------
	function getcol( $col = 0 )
	{
		$result = array();

		while( $row = $this->getrow( MYSQL_BOTH ) )
			array_push( $result, $row[$col] );

		return $result;
	}

	//-------------------------------------------------------------------------
	//	Function:	swapid
	//	Purpose:	Swap two rows by changing the ID's
	//-------------------------------------------------------------------------
	function swapid( $table, $first, $second, $idfield = "id" )
	{
		/* Checking if the table exists */
		if( !$this->tableexists( $table ) )
		{
			warning( "CDB::swapid: You did not input a valid table" );
			return false;
		}

		/* Checking if the first and second ID's exist */
		$query = "SELECT * FROM $table WHERE $idfield = $first";
		$this->query( $query );
		if( !$this->getnumrows() )
		{
			warning( "CDB::swapid: The first ID does not exist" );
			return false;
		}

		/* Checking if the first and second ID's exist */
		$query = "SELECT * FROM $table WHERE $idfield = $second";
		$this->query( $query );
		if( !$this->getnumrows() )
		{
			warning( "CDB::swapid: The first ID does not exist" );
			return false;
		}

		/* Error checking is over... Let's get down to business */
		/* Checking the max of the table */
		$query = "SELECT MAX($idfield) FROM $table";
		$this->query( $query );
		$maxid = $this->getsingle();

		/* Setting a temp ID so we can swap */
		$tempid = $maxid + 1; 

		/* Changing first ID to the tempid */
		$query = "UPDATE $table SET $idfield = $tempid WHERE $idfield = $first";
		$this->query( $query );
		
		/* Changing the second ID to the first ID */
		$query = "UPDATE $table SET $idfield = $first WHERE $idfield = $second";
		$this->query( $query );

		/* Changing the tempid to the second ID */
		$query = "UPDATE $table SET $idfield = $second WHERE $idfield = $tempid";
		$this->query( $query );
	}


	//-------------------------------------------------------------------------
	//	Function:	getpairs
	//	Purpose:	Returns data from a MySQL table if it has pairs
	//-------------------------------------------------------------------------
	function getpairs( $type = MYSQL_ASSOC )
	{
		/* Traversing the result and setting the array */
		while( $row = mysql_fetch_array( $this->result, $type ) )
			$result[$row[0]] = $row[1];

		return $result;
	}

	//-------------------------------------------------------------------------
	//	Function:	getpw
	//	Purpose:	Get a password
	//-------------------------------------------------------------------------
	function getpw( $password )
	{
		/* Performing the query */
		$query = "SELECT PASSWORD('$password')";
		$result = mysql_query( $query, $this->link );

		/* Returning the hashed password */
		$row = mysql_fetch_array( $result, MYSQL_BOTH );
		return $row[0];
	}

	//-------------------------------------------------------------------------
	//	Function:	showresult
	//	Purpose:	Show the last result in a table
	//-------------------------------------------------------------------------
	function showresult()
	{
		/* Creating a table */
		$tbl = new CTable;
		$tbl->pushth( $this->fields );
		$tbl->setthextra( "style=\"background-color: black; color:white;" );
		$tbl->setaltrows(  "style=\"background-color: #EEEEEE;\"", "style=\"background-color: #CCCCCC;\"" );

		/* Pushing each row into the table */
		while( $row = $this->getrow( MYSQL_NUM ) )
			$tbl->push( $row );

		/* Showing a little header */
		echo NL . "<div style=\"font-size: 14pt; font-weight: bold;\">" . $this->lastquery . "</div>";

		/* Showing the table */
		$tbl->show();
	}

	//-------------------------------------------------------------------------
	//	Function:	getresults
	//	Purpose:	Get the fieldnames from the last query
	//-------------------------------------------------------------------------
	function getresults($query, $object = false)
	{
		$return = array();
		$this->query($query);
		while($row = $this->getrow(MYSQL_BOTH))
			array_push($return, ($object?(object)$row:$row));
			
		return $return;
	}
	
	//-------------------------------------------------------------------------
	//	Function:	getfields
	//	Purpose:	Get the fieldnames from the last query
	//-------------------------------------------------------------------------
	function getfields()
	{
		return $this->fields;
	}

	//-------------------------------------------------------------------------
	//	Function:	getrow
	//	Purpose:	Get the row from the result. Use like:
	//				while( $row = $db->getrow() )
	//-------------------------------------------------------------------------
	function getrow( $type = MYSQL_BOTH )
	{
		$row = mysql_fetch_array( $this->result, $type );
		if( is_array( $row ) ) $row = stripslashesfromarray( $row );
		return $row;
	}

	//-------------------------------------------------------------------------
	//	Function:	tableexists
	//	Purpose:	Check if a table exists in the database
	//-------------------------------------------------------------------------
	function tableexists( $table )
	{
		/* Setting a flag */
		$tableexists = false;

		/* Doing a query to get the tables */
		$this->query( "SHOW TABLES" );

		/* Going thru all the tables */
		while( $row = $this->getrow( MYSQL_NUM ) )
		{
			/* Setting flag if we found the table */
			if( $row[0] == $table )
				$tableexists = true;
		}

		/* Returning if the table exists or not */
		return $tableexists;
	}

	//-------------------------------------------------------------------------
	//	Function:	insertarray
	//	Purpose:	Used to insert an array of values into the MySQL table
	//-------------------------------------------------------------------------
	function insertarray( $table, $post )
	{
		/* First checking if the table exists */
		if( !$this->tableexists( $table ) )
		{
			warning( "The table you passed in DB::insertpost does not exist, therefore the function will not run." );
			return;
		}

		/* Filtering the dates out */
		filterdatetime( $post );
		
		/* Getting the fieldnames */
		$this->query( "SELECT * FROM $table" );

		/* Preparing the insert query */
		$query = "INSERT INTO $table VALUES(";
		for( $i = 0; $i < $this->numfields; $i++ )
		{
			/* If we're inserting a function */
			if( preg_match( "/[A-Za-z0-9]+[(].*[)]/", $post[$this->fields[$i]] ) && !strstr($post[$this->fields[$i]], "\n") )
				$query .= $post[$this->fields[$i]];

			/* If we're inserting a string */
			elseif( is_string( $post[$this->fields[$i]] ) )
				$query .= "'" . addslashes($post[$this->fields[$i]]) . "'";
				
			/* If we're inserting an integer */
			elseif( is_int( $post[$this->fields[$i]] ) )
				$query .= $post[$this->fields[$i]];

			/* If there's no value */
			else
				$query .= "NULL";

			/* If this isn't the last element then add a comma */
			if( $i + 1 < $this->numfields )
				$query .= ", ";
		}

		/* Closing the query */
		$query .= ")";
		
		
		/* Performing query */
		$this->query( $query );

		/* Making last insert ID */
		$this->lastinsertid = mysql_insert_id();
	}
	
	//-------------------------------------------------------------------------
	//	Function:	getinsertid
	//	Purpose:	Returns insert ID
	//-------------------------------------------------------------------------
	function getinsertid()
	{
		if($this->lastinsertid)
			return $this->lastinsertid;
		else
			return mysql_insert_id();
	}
	
	//-------------------------------------------------------------------------
	//	Function:	updatearray
	//	Purpose:	Update a table in the database using an array.
	//-------------------------------------------------------------------------
	function updatearray( $table, $array, $where )
	{
		/* Checking if the table exists */
		if( !$this->tableexists( $table ) )
		{
			warning( "Function DB::updatearray: Table $table doesn't exist" );
			return false;
		}
		
		/* Checking if they passed an array */
		if( !is_array( $array ) )
		{
			warning( "Function DB::updatearray: You did not pass an array" );
			return false;
		}
		
		/* Getting the fields from the table */
		$query = "SELECT * FROM $table";
		$result = $this->query( $query );
		$fields = $this->getfields();
		$numfields = $this->getnumfields();
		
		/* Getting the query ready */
		$query = "UPDATE $table SET ";
		
		/* Going thru the fields to check what we need to update */
		for( $i = 0; $i < $numfields; $i++ )
		{
			/* If we're inserting a function */
			if( preg_match( "/[A-Z]+[(].*[)]/", $array[$this->fields[$i]] ) )
				$query .= "$fields[$i] = " . $array[$this->fields[$i]] . ", ";

			/* If we're inserting an integer */
			elseif( is_int( $array[$this->fields[$i]] ) )
				$query .= "$fields[$i] = " . $array[$this->fields[$i]] . ", ";

			/* If we're inserting a string */
			elseif( $array[$this->fields[$i]] )
				$query .= "$fields[$i] = '" . addslashes($array[$this->fields[$i]]) . "', ";
				
			/* If it's empty */
			elseif( array_key_exists($fields[$i], $array) )
				$query .= "$fields[$i] = '', ";


			/* If the fieldname exists in the array */
			/*
			if( $array["$fields[$i]"] )
			{
				$query .= "$fields[$i] = '" . $array["$fields[$i]"] . "', ";
			}
			*/
		}
		
		/* Getting rid of the last comma */
		rcut( $query, ", " );
		
		$query .= " WHERE $where";

		/* Performing the query */
		$this->query( $query );
	}

	//-------------------------------------------------------------------------
	//	Function:	search
	//	Purpose:	Gets the number of rows from last query
	//-------------------------------------------------------------------------
	function search($table, $search, $where = NULL, $orderby = NULL, $fields = NULL)
	{
		/////////////////////////////////////////////////////////////////////
		// searching...
		
		// explode search words into an array
		$arraySearch = explode(" ", $search);
		
		// table fields to search
		if(!$fields)
		{
			$this->query("SELECT * FROM $table LIMIT 1");
			$fields = $this->getfields();
		}
		
		$countSearch = count($arraySearch);
		$a = 0;
		$b = 0;
		$query = "	SELECT 
						*
					FROM $table WHERE ((";
											  
		$countFields = count($fields);
		
		while ($a < $countFields)
		{
			while ($b < $countSearch)
			{
				$query = $query."$fields[$a] LIKE '%".addslashes($arraySearch[$b])."%'";
				$b++;
				if ($b < $countSearch)
				{
					$query = $query." AND ";
				}
			}
			$b = 0;
			$a++;
		
			if ($a < $countFields)
			{
				$query = $query.") OR (";
			}
		}
		
		$query = $query.")) ".($where?"AND ($where) ":"").($orderby?"ORDER BY $orderby":"")."";

		return $this->getresults($query);
		
		
	}
	
	//-------------------------------------------------------------------------
	//	Function:	getnumrows
	//	Purpose:	Gets the number of rows from last query
	//-------------------------------------------------------------------------
	function getnumrows()
	{
		return $this->numrows;
	}

}

/********************************************************************************
 *
 *	CLASS:			CTable
 *
 *	DESCRIPTION:	Easy way to use PHP to make HTML Tables
 *
 ********************************************************************************/
class CTable
{
	/* Table Properties */
	private $border;		// Border of the table
	private $padding;		// Cellpadding of the table
	private $spacing;		// Cellspacing of the table
	private $bgcolor;		// Background Color of the table
	private $align;			// Alignment of the table
	private $width;			// Width of the table
	private $extra;			// Extra for the <table ...> HTML tag

	/* Table Header Properties */
	private $thtype;		// bgcolor, class, id
	private $thval;			// Value of the type

	/* Table Row/Col Properties */
	private $altrows;		// alternate rows
	private $altcols;		// Value of the bgcolor, class, or id

	/* Table Data */
	private $th;			// Array of Table Headers
	private $maindata;		// The data of the table
	private $numrows;		// Number of rows in the table

	//----------------------------------------------------------------------
	// 	Function Declaration
	//
	//	NULL __construct(NULL)
	//	NULL __destruct(NULL)
	//
	//	NULL setborder(INT $x)
	//	NULL setpadding(INT $x)
	//	NULL setspacing(INT $x)
	//	NULL settblwidth(STRING $x)
	//	NULL settblbgcolor(STRING $x)
	//	NULL settblalign(STRING $x)
	//	NULL settblextra(STRING $x)
	//	NULL setthextra(STRING $extra)
	//	NULL setcolprops(STRING...)
	//	NULL setcolwidths(STRING...)
	//	NULL setaltrows(STRING...)
	//
	//	NULL push(STRING...)
	//	NULL pushth(STRING...)
	//	NULL popall(NULL)
	//	STRING gettable(NULL)
	//	INT	getnumrows(VOID)		NEED TO DOCUMENT
	//	NULL show(NULL)
	//----------------------------------------------------------------------

	/**********************************************************************
	 *	FUNCTION:		__construct
	 *	DESCRIPTION:	Class constructor. Sets all defaults
	 **********************************************************************/
	function __construct($border = 0, $padding = 2, $spacing = 1, 
						 $width = NULL, $bgcolor = NULL, $align = NULL, 
						 $extra = NULL)
	{
		$this->border		= $border;
		$this->padding		= $padding;
		$this->spacing		= $spacing;
		$this->bgcolor		= $bgcolor;
		$this->align		= $align;
		$this->width		= $width;
		$this->extra		= $extra;
		$this->thtype		= NULL;
		$this->thval		= NULL;
		$this->alttrtype	= NULL;
		$this->alttrvals	= array();
		$this->tdtype		= NULL;
		$this->tdval		= NULL;
		$this->colwidths	= array();
		$this->th			= array();
		$this->thextra		= NULL;
		$this->maindata		= array();
		$this->numrows		= 0;
	}
	
	/**********************************************************************
	 *	FUNCTION:		__destruct
	 *	DESCRIPTION:	Class destructor. Cleanup
	 **********************************************************************/
	 function __destruct()
	 {
	 }
	

	/**********************************************************************
	 *	FUNCTION:		Set functions
	 *	DESCRIPTION:	To set the properties of the table
	 **********************************************************************/
	function setborder( $x )		{ $this->border		= $x; }
	function setpadding( $x )		{ $this->padding	= $x; }
	function setspacing( $x )		{ $this->spacing	= $x; }
	function setwidth( $x )			{ $this->width		= $x; }
	function setbgcolor( $x )		{ $this->bgcolor	= $x; }
	function setalign( $x )			{ $this->align		= $x; }
	function setextra( $x )			{ $this->extra		= $x; }
	
	function setthextra( $x ) 		{ $this->thextra	= $x; }

	function setaltrows( /*...*/ )	{ $this->altrows 	= func_get_args(); }
	function setcolprops( /*...*/ )	{ $this->altcols 	= func_get_args(); }
	function setcolwidths( /*...*/ ){ $this->colwidths 	= func_get_args(); }

	//-------------------------------------------------------------------------
	//	Function:	pushth
	//	Purpose:	Set the table headers of the table
	//-------------------------------------------------------------------------
	function pushth( /*...*/ )
	{
		$x = func_get_args();
		if(is_array($x[0])) $x = $x[0];
		array_push( $this->th, $x );
	}

	//-------------------------------------------------------------------------
	//	Function:	push
	//	Purpose:	Push a row of data into the table
	//-------------------------------------------------------------------------
	function push( /*...*/ )
	{
		$args = func_get_args();

		/* Adding the number of rows and then pushing the row */
		$this->numrows++;
		
		if(is_array($args[0])) $args = $args[0];
		array_push( $this->maindata, $args );
	}
	
	//-------------------------------------------------------------------------
	//	Function:	unshift
	//	Purpose:	Push a row to the BEGINNING of the table
	//-------------------------------------------------------------------------
	function unshift( /*...*/ )
	{
		$args = func_get_args();

		/* Adding the number of rows and then pushing the row */
		$this->numrows++;
		
		if(is_array($args[0])) $args = $args[0];
		array_unshift( $this->maindata, $args );
	}
	
	//------------------------------------------------------------------
	function getnumrows()
	{
		return $this->numrows;
	}

	//-------------------------------------------------------------------------
	//	Function:	popall
	//	Purpose:	Pop all the rows out of the table (clears the table out)
	//-------------------------------------------------------------------------
	function popall()
	{
		/* Setting number of rows to 0 and then reseting the maindata var */
		$this->numrows = 0;
		$this->maindata = array();

		/* Clearing the table header */
		$this->th = array();
	}

	//-------------------------------------------------------------------------
	//	Function:	gettable
	//	Purpose:	The big function. This function compiles the table 
	//				according to all of the data set and in the rows pushed,
	//				and then returns it as a string
	//-------------------------------------------------------------------------
	function gettable()
	{
		/* Setting some variables to work with */
		$tablestring	= "";	// The big string the table will go into
		$maxcols		= 0;	// The number of columns in the table
		$tdwidth		= NULL;
		$tdstyle		= NULL;

		/* Checking what is the max number of columns */
		if( sizeof( $this->th ) )
		{
			/* Going through the rows of table headers */
			foreach( $this->th as $thisth )
			{
				$colcount = 0;

				/* Going through each table header cell */
				foreach( $thisth as $th )
				{
					/* Making a temporary thing to work with */
					$tempth = $th;

					/* Checking how many columns this table header spans */
					$colspan = rcut( $tempth, "~>" ) + 1; 
					
					/* Adding the colspan to the column count */
					$colcount += $colspan;
				}

				/* Setting maxcols to the greater of the two */
				$maxcols = max( $colcount, $maxcols );
			}
		}

		foreach( $this->maindata as $row )
		{
			/* Getting the size of this row */
			$rowsize = sizeof( $row );

			/* Checking if the size of this row is larger than the max
			   number of columns so far */
			$maxcols = ( $rowsize > $maxcols ? $rowsize : $maxcols );

			if( $rowsize > $maxcols )
				$rowsize;
			else
				$maxcols;
		}

		/*******************************/
		/*** PLACING THE <TABLE> TAG ***/
		/*******************************/
		$tablestring .= "\n\n<!-- Start Table Using DrtLib.PHP -->\n<table";

		/* Checking what we need to add to the tag */
		if( $this->border >= 0 )	$tablestring .= " border=\"$this->border\"";
		if( $this->padding >= 0 )	$tablestring .= " cellpadding=\"$this->padding\"";
		if( $this->spacing >= 0 )	$tablestring .= " cellspacing=\"$this->spacing\"";
		if( $this->width )			$tablestring .= " width=\"$this->width\"";
		if( $this->bgcolor )		$tablestring .= " bgcolor=\"$this->bgcolor\"";
		if( $this->align )			$tablestring .= " align=\"$this->align\"";
		if( $this->extra )			$tablestring .= " $this->extra";

		/* Closing the table HTML tag */
		$tablestring .= ">\n";
	
		/***********************************************/
		/*** ADDING THE TABLE HEADER <TH> TO THE MIX ***/
		/***********************************************/
		if( $this->th )
		{
			/* Checking what type and value to add to the table header */
			if( $this->thextra )				
				$thextra = " $this->thextra";

			/* Going through each row of table headers */
			foreach( $this->th as $thisth )
			{
				/* Start the table row <tr> for the table header */
				$tablestring .= " <tr>\n";

				/* Adding each <TH> */
				$thcount = 0;
				foreach( $thisth as $th )
				{
					$thwidth = "";
					/* Checking if they want to span this TH over columns */
					$colspan = rcut( $th, "~>" ) + 1;
					if( sizeof( $thisth ) == 1 )
					{
						$thcolspan = " colspan=\"$maxcols\"";
					}
					elseif( $colspan != 1 )
					{
						$thcolspan = " colspan=\"$colspan\"";
					}
					else
					{
						$thcolspan = "";
						$thwidth = " width=\"" . $this->colwidths[$thcount] . "\"";
					}

					$tablestring .= "  <th$thextra$thcolspan$thwidth>$th</th>\n";
					$thcount++;
				}

				/* Ending the table row */
				$tablestring .= " </tr>\n\n";
			}
		}

		/************************************************************/
		/*** ADDING THE REST OF THE TABLE INFORMATION <TD>'S HERE ***/
		/************************************************************/
		/* Getting the number of alternating table row styles */
		$numalttrs = sizeof( $this->altrows );

		for( $i = 0; $i < $this->numrows; $i++ )
		{
			$alttr = ""; 
			
			/* Getting the alttr style for this row */
			if( $numalttrs ) 
				$alttr = " " . $this->altrows[$i%$numalttrs];

			/* Opening the table row <tr> */
//			old, changed 8/12/2009
//			$tablestring .= " <tr$alttr>\n";
			$tablestring .= " <tr>\n";

			/* Putting in the columns <td> */
			for( $j = 0; $j < $maxcols; $j++ )
			{
				/* Checking the td style */
				if( $this->altcols )
				{
					/* Check how many values in tdval */
					$numtdstyles = sizeof( $this->altcols );
					if( $numtdstyles == 1 )	$tdstyle = " " . $this->altcols[0];
					if( $numtdstyles > 1 )	$tdstyle = " " . $this->altcols[$j];
				}

				/* Checking colspan for this cell */
				if( $colspan = rcut( $this->maindata[$i][$j], "~>" ) )
				{
					$tdcolspan = " colspan=\"" . ( $colspan + 1 ) . "\"";
				}
				else
					$tdcolspan = "";

	
				/* Adding the table cell */
//				old.. changed 8/12/2009
//				$tablestring .= "  <td$tdwidth$tdstyle$tdcolspan>" . $this->maindata[$i][$j] . "</td>\n";
				$tablestring .= "  <td$tdwidth$tdstyle$tdcolspan$alttr>" . $this->maindata[$i][$j] . "</td>\n";
				
				/* Adding colspan to J so we don't loop too much */
				$j = $j + $colspan;
			}

			/* Closing the table row */
			$tablestring .= " </tr>\n\n";
		}

		/* Closing the table */
		$tablestring .= "</table>\n<!-- End Table -->\n\n";

		/* Returning the table string */
		return $tablestring; 

	}

	//-------------------------------------------------------------------------
	//	Function:	show
	//	Purpose:	Shows the table. Just echos the string returned by the
	//				'gettable' function above.
	//-------------------------------------------------------------------------
	function show()
	{
		echo $this->gettable();
	}

}

function ddmmmyyyy_to_mysql($ddmmyyyy){
	/*
		DOUG CHAN
		2/9/2010
		
		Alias of ddmmmyyyy_to_yyyymmdd($ddmmmmyyyy)
	*/
	return ddmmmyyyy_to_yyyymmdd($ddmmyyyy);
}
function ddmmmyyyy_to_yyyymmdd($ddmmmyyyy){
	/*
		DOUG CHAN
		2/9/2010
		
		Changes DD MM YYYY to YYYY-MM-DD
	*/
	
	list($day,$month,$year) = split(' ',$ddmmmyyyy);
	switch($month){
		case 'Jan': $month = '01'; break;
		case 'Feb': $month = '02'; break;
		case 'Mar': $month = '03'; break;
		case 'Apr': $month = '04'; break;
		case 'May': $month = '05'; break;
		case 'Jun': $month = '06'; break;
		case 'Jul': $month = '07'; break;
		case 'Aug': $month = '08'; break;
		case 'Sep': $month = '09'; break;
		case 'Oct': $month = '10'; break;
		case 'Nov': $month = '11'; break;
		case 'Dec': $month = '12'; break;
		default: $month = 'ERROR WTF'; break;
	}
	
	return $year.'-'.$month.'-'.$day;
}
function tab($num){
	$return = '';
	for($i=0;$i<$num;$i++){
		$return .= "\t";
	}
	return $return;
}

// used to resize images
// taken from http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
class SimpleImage {
	
	//////////////////////////////
	// 	fcuntion list
	// 
	//	VOID 	load(STRING $filename)
	//	VOID 	save(STRING $filename[, IMAGETYPE_XXX $image_type[, INT $compression[, STRING $permissions]]])
	//	VOID 	output([IMAGETYPE_XXX $image_type])
	//
	//	INT		getWidth(VOID)
	//	INT		getHeight(VOID)
	//
	//	VOID	resizeToHeight(INT $height)
	//	VOID	resizeToWidth(INT $width)
	//	VOID	scale(INT $scale)
	//	VOID	resize(INT $width, INT $height)
   
   var $image;
   var $image_type;
 
   function load($filename) {
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }   
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image);
      }   
   }
   function getWidth() {
      return imagesx($this->image);
   }
   function getHeight() {
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100; 
      $this->resize($width,$height);
   }
   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;   
   }      
}


?>
