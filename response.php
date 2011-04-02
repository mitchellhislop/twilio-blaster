<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include('includes/twilio.php');
$from=$_REQUEST['From'];
$case = $_REQUEST['Body'];
//$case = "EVENT";

$case=strtoupper($case);

if ($case == "STOP")
{   $new_number=$_REQUEST['From'];
    $number = ereg_replace("[^0-9]", "", $new_number );
    //TODO: Make this $wpdb
    $query = "DELETE FROM numbers WHERE number='".mysql_real_escape_string($number)."'";
    $result=mysql_query($query);
    $message = "You have been unsubscribed. Txt Add to subscribe again";
}



?>
