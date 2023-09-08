<?php

//Tim Dominguez 9/5/2023 (timfox@coufu.com)

//dependencies
include 'functions.php';

payroll_top();

//Date Form
$reportdate = '<r><input type="text" name="reportdate" id="datepicker" size="28">';

$table = new Ctable;
$table->pushth("Report Date: $_POST[reportdate]");
$table->push(''.$reportdate.'' );
echo "<form action=\"report.php\" method=\"post\">";
$table->show();
echo "<br>";
echo "<input type=\"submit\" value=\"SUBMIT\" /> ";
echo "<input type=\"reset\" value=\"RESET\" /> ";
echo "</form>";

//displaying report
if($_POST){

    ?>
    <div class="container">
        <form method='post' action='export.php'>
            <input type='submit' value='export' name='export'>
    
            <table border='1' style='border-collapse:collapse;'>
                <tr>
                    <th>REPORTDATE</th>
                    <th>DATETIME</th>
                    <th>EMPNO</th>
                    <th>DEPTNO</th>         
                    <th>Email</th>
                    <th>STATUS</th>
                    <th>Sender</th>
                </tr>
        <?php

        //database
        sql_connect();
        $result = mysql_query( "SELECT * FROM email_log WHERE reportdate = '$_POST[reportdate]'");
        $user_arr = array();

        while($row = mysql_fetch_array ($result, MYSQL_ASSOC)){

            $reportdate = $row[reportdate];
            $emaildate  = date('m/d/y, g:ia', $row[datetime]);
            $empno      = $row[empno];
            $deptno     = $row[deptno];
            $email      = $row[email];
            $status     = $row[status];
            $user       = getusername($row[sender]);
            
            $user_arr[] = array($reportdate,$emaildate, $empno, $deptno, $email, $status, $user);
            
            ?>
                <tr>
                    <td><?php echo $reportdate; ?></td>
                    <td><?php echo $emaildate; ?></td>
                    <td><?php echo $empno; ?></td>
                    <td><?php echo $deptno; ?></td>                    
                    <td><?php echo $email; ?></td>
                    <td><?php echo $status; ?></td>
                    <td><?php echo $user; ?></td>
                </tr>
            <?php
        }
        echo "</table>";

        $serialize_user_arr = serialize($user_arr);

        ?>
            <textarea name='export_data' style='display: none;'><?php echo $serialize_user_arr; ?></textarea>
        
        </form>

    </div><?php
}

?>