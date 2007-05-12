<?php
include ("lib/defines.php");
include ("lib/module.access.php");

if (! has_rights (ACX_ACCESS)){
      Header ("HTTP/1.0 401 Unauthorized");
      Header ("Location: PP_error.php?c=accessdenied");
      die();
}



//if (!$A2B->config["webcustomerui"]['invoice']) exit();

getpost_ifset(array('customer', 'posted', 'Period', 'frommonth', 'fromstatsmonth', 'tomonth', 'tostatsmonth', 'fromday', 'fromstatsday_sday', 'fromstatsmonth_sday', 'today', 'tostatsday_sday', 'tostatsmonth_sday', 'dsttype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'dst', 'src', 'clid', 'fromstatsmonth_sday', 'fromstatsmonth_shour', 'tostatsmonth_sday', 'tostatsmonth_shour', 'srctype', 'src', 'choose_currency','exporttype'));

//$customer = $_SESSION["pr_login"];
$vat = $_SESSION["vat"];

//require (LANGUAGE_DIR.FILENAME_INVOICES);

if (($_GET[download]=="file") && $_GET[file] ) 
{
	
	$value_de=base64_decode($_GET[file]);
	$dl_full = MONITOR_PATH."/".$value_de;
	$dl_name=$value_de;

	if (!file_exists($dl_full))
	{ 
		echo gettext("ERROR: Cannot download file $dl_full , it does not exist").'<br>';
		exit();
	}
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=$dl_name");
	header("Content-Length: ".filesize($dl_full));
	header("Accept-Ranges: bytes");
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-transfer-encoding: binary");
			
	@readfile($dl_full);
	
	exit();

}



if (!isset ($current_page) || ($current_page == "")){	
		$current_page=0; 
	}


// this variable specifie the debug type (0 => nothing, 1 => sql result, 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME="cc_call t1";

// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_HEAD_COLOR = "#D1D9E7";

$FG_TABLE_EXTERN_COLOR = "#7F99CC"; //#CC0033 (Rouge)
$FG_TABLE_INTERN_COLOR = "#EDF3FF"; //#FFEAFF (Rose)

// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#F2F8FF";

$yesno = array(); 	$yesno["1"]  = array( "Yes", "1");	 $yesno["0"]  = array( "No", "0");

$DBHandle  = DbConnect();

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array();


/*******
Calldate Clid Src Dst Dcontext Channel Dstchannel Lastapp Lastdata Duration Billsec Disposition Amaflags Accountcode Uniqueid Serverid
*******/

$FG_TABLE_COL[]=array (gettext("Call date"), "starttime", "18%", "center", "SORT", "19", "", "", "", "", "", "display_dateformat");
$FG_TABLE_COL[]=array (gettext("Source"), "src", "10%", "center", "SORT", "30");
$FG_TABLE_COL[]=array (gettext("Called number"), "calledstation", "18%", "center", "SORT", "30", "", "", "", "", "", "remove_prefix2");
$FG_TABLE_COL[]=array (gettext("Destination"), "destination", "18%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Duration"), "sessiontime", "8%", "center", "SORT", "30", "", "", "", "", "", "display_minute");

if (!(isset($customer)  &&  ($customer>0)) && !(isset($entercustomer)  &&  ($entercustomer>0))){
	//FIXME!
	$FG_TABLE_COL[]=array (gettext("Card used"), "username", "11%", "center", "SORT", "30");
}

//if ($_SESSION["is_admin"]==1) $FG_TABLE_COL[]=array ("Con_charg", "connectcharge", "12%", "center", "SORT", "30");
//if ($_SESSION["is_admin"]==1) $FG_TABLE_COL[]=array ("Dis_charg", "disconnectcharge", "12%", "center", "SORT", "30");
//if ($_SESSION["is_admin"]==1) $FG_TABLE_COL[]=array ("Sec/mn", "secpermin", "12%", "center", "SORT", "30");


//if ($_SESSION["is_admin"]==1) $FG_TABLE_COL[]=array ("Buycosts", "buycosts", "12%", "center", "SORT", "30");
//-- $FG_TABLE_COL[]=array ("InitialRate", "calledrate", "10%", "center", "SORT", "30", "", "", "", "", "", "display_2dec");
$FG_TABLE_COL[]=array (gettext("Cost"), "sessionbill", "9%", "center", "SORT", "30", "", "", "", "", "", "display_2bill");

//-- if (LINK_AUDIO_FILE == 'YES') 
//-- 	$FG_TABLE_COL[]=array ("", "uniqueid", "1%", "center", "", "30", "", "", "", "", "", "linkonmonitorfile");

// ??? cardID
$FG_TABLE_DEFAULT_ORDER = "t1.starttime";
$FG_TABLE_DEFAULT_SENS = "DESC";
	
// This Variable store the argument for the SQL query

$FG_COL_QUERY='t1.starttime, t1.src, t1.calledstation, t1.destination, t1.sessiontime  ';
if (!(isset($customer)  &&  ($customer>0)) && !(isset($entercustomer)  &&  ($entercustomer>0))){
	$FG_COL_QUERY.=', t1.username';
}
$FG_COL_QUERY.=', t1.sessionbill';
if (LINK_AUDIO_FILE == 'YES') 
	$FG_COL_QUERY .= ', t1.uniqueid';

$FG_COL_QUERY_GRAPH='t1.callstart, t1.duration';

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY=500;

// Number of column in the html table
$FG_NB_TABLE_COL=count($FG_TABLE_COL);

// The variable $FG_EDITION define if you want process to the edition of the database record
$FG_EDITION=true;

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION) $FG_TOTAL_TABLE_COL++;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE=" - Call Logs - ";

//This variable define the width of the HTML table
$FG_HTML_TABLE_WIDTH="90%";

	if ($FG_DEBUG >= 3) echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_COL_QUERY";
	$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);
	$instance_table_graph = new Table($FG_TABLE_NAME, $FG_COL_QUERY_GRAPH);


if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}

if ($posted==1){
  
  function do_field($sql,$fld,$dbfld){
  		$fldtype = $fld.'type';
		global $$fld;
		global $$fldtype;		
        if ($$fld){
                if (strpos($sql,'WHERE') > 0){
                        $sql = "$sql AND ";
                }else{
                        $sql = "$sql WHERE ";
                }
				$sql = "$sql t1.$dbfld";
				if (isset ($$fldtype)){                
                        switch ($$fldtype) {							
							case 1:	$sql = "$sql='".$$fld."'";  break;
							case 2: $sql = "$sql LIKE '".$$fld."%'";  break;
							case 3: $sql = "$sql LIKE '%".$$fld."%'";  break;
							case 4: $sql = "$sql LIKE '%".$$fld."'";  break;
							case 5:	$sql = "$sql <> '".$$fld."'";  
						}
                }else{ $sql = "$sql LIKE '%".$$fld."%'"; }
		}
        return $sql;
  }  
  $SQLcmd = '';
  
  $SQLcmd = do_field($SQLcmd, 'src', 'src');
  $SQLcmd = do_field($SQLcmd, 'dst', 'calledstation');
	
  
}


$date_clause='';
// Period (Month-Day)
if (DB_TYPE == "postgres"){		
	 	$UNIX_TIMESTAMP = "";
}else{
		$UNIX_TIMESTAMP = "UNIX_TIMESTAMP";
}


$lastdayofmonth = date("t", strtotime($tostatsmonth.'-01'));

if ($Period=="Month"){
		
		
		if ($frommonth && isset($fromstatsmonth)) $date_clause.=" AND $UNIX_TIMESTAMP(t1.starttime) >= $UNIX_TIMESTAMP('$fromstatsmonth-01')";
		if ($tomonth && isset($tostatsmonth)) $date_clause.=" AND $UNIX_TIMESTAMP(t1.starttime) <= $UNIX_TIMESTAMP('".$tostatsmonth."-$lastdayofmonth 23:59:59')"; 
		
}else{
		if ($fromday && isset($fromstatsday_sday) && isset($fromstatsmonth_sday) && isset($fromstatsmonth_shour) && isset($fromstatsmonth_smin) ) $date_clause.=" AND $UNIX_TIMESTAMP(t1.starttime) >= $UNIX_TIMESTAMP('$fromstatsmonth_sday-$fromstatsday_sday $fromstatsmonth_shour:$fromstatsmonth_smin')";
		if ($today && isset($tostatsday_sday) && isset($tostatsmonth_sday) && isset($tostatsmonth_shour) && isset($tostatsmonth_smin)) $date_clause.=" AND $UNIX_TIMESTAMP(t1.starttime) <= $UNIX_TIMESTAMP('$tostatsmonth_sday-".sprintf("%02d",intval($tostatsday_sday))." $tostatsmonth_shour:$tostatsmonth_smin')";
}

//echo "<br>$date_clause<br>";
// fromstatsmonth_sday, fromstatsmonth_shour, tostatsmonth_sday, tostatsmonth_shour
/*
Month
fromday today
frommonth tomonth (true)
fromstatsmonth tostatsmonth

fromstatsday_sday
fromstatsmonth_sday
tostatsday_sday
tostatsmonth_sday
*/


  
if (strpos($SQLcmd, 'WHERE') > 0) { 
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause; 
}elseif (strpos($date_clause, 'AND') > 0){
	$FG_TABLE_CLAUSE = substr($date_clause,5); 
}


if (!isset ($FG_TABLE_CLAUSE) || strlen($FG_TABLE_CLAUSE)==0){
		
		$cc_yearmonth = sprintf("%04d-%02d-%02d",date("Y"),date("n"),date("d")); 	
		$FG_TABLE_CLAUSE=" $UNIX_TIMESTAMP(t1.starttime) >= $UNIX_TIMESTAMP('$cc_yearmonth')";
}


if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
$FG_TABLE_CLAUSE .= 'username IN( SELECT username FROM cc_card,cc_agent_cards WHERE '.
	'cc_card.id = cc_agent_cards.card_id AND cc_agent_cards.agentid ='. $DBHandle->Quote($_SESSION['agent_id']) .')';
	
if (isset($customer)  &&  ($customer>0)){
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
	$FG_TABLE_CLAUSE.="t1.username='$customer'";
}else{
	if (isset($entercustomer)  &&  ($entercustomer>0)){
		if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
		$FG_TABLE_CLAUSE.="t1.username='$entercustomer'";
	}
}


if (!$nodisplay){
	$list = $instance_table -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY);
}
$_SESSION["pr_sql_export"]="SELECT $FG_COL_QUERY FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE";

/************************/
//$QUERY = "SELECT substring(calldate,1,10) AS day, sum(duration) AS calltime, count(*) as nbcall FROM cdr WHERE ".$FG_TABLE_CLAUSE." GROUP BY substring(calldate,1,10)"; //extract(DAY from calldate)


$QUERY = "SELECT substring(t1.starttime,1,10) AS day, sum(t1.sessiontime) AS calltime, sum(t1.sessionbill) AS cost, count(*) as nbcall FROM $FG_TABLE_NAME WHERE ".$FG_TABLE_CLAUSE." GROUP BY substring(t1.starttime,1,10) ORDER BY day"; //extract(DAY from calldate)
//echo "Query: $QUERY <br><br>";

if (!$nodisplay){
		$res = $DBHandle -> query($QUERY);
		if ($res)
			$num = $res -> numRows();
		else $num = 0;
		for($i=0;$i<$num;$i++)
		{
			$list_total_day [] =$res -> fetchRow();
		}



if ($FG_DEBUG >= 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
$nb_record = $instance_table -> Table_count ($DBHandle, $FG_TABLE_CLAUSE);
if ($FG_DEBUG >= 1) var_dump ($list);

}//end IF nodisplay


// GROUP BY DESTINATION FOR THE INVOICE


$QUERY = "SELECT destination, sum(t1.sessiontime) AS calltime, 
sum(t1.sessionbill) AS cost, count(*) as nbcall FROM $FG_TABLE_NAME WHERE ".$FG_TABLE_CLAUSE." GROUP BY destination";

if (!$nodisplay){
		$res = $DBHandle -> query($QUERY);
		if ($res) $num = $res -> numRows();
		else $num = 0;
		for($i=0;$i<$num;$i++)
		{				
			$list_total_destination [] =$res -> fetchRow();				 
		}


if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
if ($FG_DEBUG >= 1) var_dump ($list_total_destination);


}//end IF nodisplay





if ($nb_record<=$FG_LIMITE_DISPLAY){
	$nb_record_max=1;
}else{ 
	if ($nb_record % $FG_LIMITE_DISPLAY == 0){
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY));
	}else{
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY)+1);
	}	
}


if ($FG_DEBUG == 3) echo "<br>Nb_record : $nb_record";
if ($FG_DEBUG == 3) echo "<br>Nb_record_max : $nb_record_max";


/*******************   TOTAL COSTS  *****************************************

$instance_table_cost = new Table($FG_TABLE_NAME, "sum(t1.costs), sum(t1.buycosts)");		
if (!$nodisplay){	
	$total_cost = $instance_table_cost -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, null, null, null, null, null, null);
}
*/



/*************************************************************/



if ((isset($customer)  &&  ($customer>0)) || (isset($entercustomer)  &&  ($entercustomer>0))){

	$FG_TABLE_CLAUSE = "";
	if (isset($customer)  &&  ($customer>0)){		
		$FG_TABLE_CLAUSE =" username='$customer' ";
	}elseif (isset($entercustomer)  &&  ($entercustomer>0)){
		$FG_TABLE_CLAUSE =" username='$entercustomer' ";
	}



	$instance_table_customer = new Table("cc_card", "id,  username, lastname, firstname, address, city, state, country, zipcode, phone, email, fax");
	
	
	
	$info_customer = $instance_table_customer -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, "id", "ASC", null, null, null, null);
	
	// if (count($info_customer)>0){
}



/************************************************************/


$date_clause='';

if ($Period=="Month"){		
		if ($frommonth && isset($fromstatsmonth)) $date_clause.=" AND $UNIX_TIMESTAMP(t1.creationdate) >= $UNIX_TIMESTAMP('$fromstatsmonth-01')";
		if ($tomonth && isset($tostatsmonth)) $date_clause.=" AND  $UNIX_TIMESTAMP(t1.creationdate) <= $UNIX_TIMESTAMP('".$tostatsmonth."-$lastdayofmonth 23:59:59')"; 
}else{
		if ($fromday && isset($fromstatsday_sday) && isset($fromstatsmonth_sday) && isset($fromstatsmonth_shour) && isset($fromstatsmonth_smin) ) $date_clause.=" AND  $UNIX_TIMESTAMP(t1.creationdate) >= $UNIX_TIMESTAMP('$fromstatsmonth_sday-$fromstatsday_sday $fromstatsmonth_shour:$fromstatsmonth_smin')";
		if ($today && isset($tostatsday_sday) && isset($tostatsmonth_sday) && isset($tostatsmonth_shour) && isset($tostatsmonth_smin)) $date_clause.=" AND  $UNIX_TIMESTAMP(t1.creationdate) <= $UNIX_TIMESTAMP('$tostatsmonth_sday-".sprintf("%02d",intval($tostatsday_sday))." $tostatsmonth_shour:$tostatsmonth_smin')";
}

// if (strpos($date_clause, 'AND') > 0){
// 	$date_clause = ' WHERE '.substr($date_clause,5); 
// }


$QUERY = "SELECT substring(t1.creationdate,1,10) AS day, sum(t1.amount) AS cost, count(*) as nbcharge FROM cc_charge t1, cc_agent_cards WHERE id_cc_card = cc_agent_cards.card_id AND cc_agent_cards.agentid = ". $_SESSION['agent_id'] ."  ".$date_clause." GROUP BY substring(t1.creationdate,1,10) ORDER BY day"; //extract(DAY from calldate)

if (!$nodisplay){
		$res = $DBHandle -> query($QUERY);
		if ($res) $num = $res -> numRows();
		else $num = 0;
		for($i=0;$i<$num;$i++)
		{				
			$list_total_day_charge [] =$res -> fetchRow();				 
		}

		if ($FG_DEBUG >= 1) var_dump ($list_total_day_charge);

}//end IF nodisplay



?>
<?php if($exporttype!="pdf"){?>

<?php
	include("PP_header.php");
?>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}

//-->
</script>


<br/><br/>
<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
	<center>
	<FORM METHOD=POST ACTION="<?= $PHP_SELF?>?s=1&t=0&order=<?= $order?>&sens=<?= $sens?>&current_page=<?= $current_page?>">
	<INPUT TYPE="hidden" NAME="posted" value=1>
	<INPUT TYPE="hidden" NAME="current_page" value=0>	
		<table class="bar-status" width="95%" border="0" cellspacing="1" cellpadding="2" align="center">
			<tbody>
			
			<tr>
        		<td class="bar-search" align="left" bgcolor="#555577">

					<input type="radio" name="Period" value="Month" <?php  if (($Period=="Month") || !isset($Period)){ ?>checked="checked" <?php  } ?>> 
					<font face="verdana" size="1" color="#ffffff"><b><?= gettext("Selection of the month");?></b></font>
				</td>
      			<td class="bar-search" align="left" bgcolor="#cddeff">
					<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#cddeff"><tr><td>
	  				<input type="checkbox" name="frommonth" value="true" <?php  if ($frommonth){ ?>checked<?php }?>> 
					<?= gettext("FROM");?> : <select name="fromstatsmonth">
					<?php 	$year_actual = date("Y");  	
						for ($i=$year_actual;$i >= $year_actual-1;$i--)
						{	
							$monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							if ($year_actual==$i){
								$monthnumber = date("n")-1; // Month number without lead 0.
							}else{
								$monthnumber=11;
							}		   
							for ($j=$monthnumber;$j>=0;$j--){	
								$month_formated = sprintf("%02d",$j+1);
								if ($fromstatsmonth=="$i-$month_formated"){$selected="selected";}else{$selected="";}
								echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";
							}
						}
					?>		
					</select>
					</td><td>&nbsp;&nbsp;
					<input type="checkbox" name="tomonth" value="true" <?php  if ($tomonth){ ?>checked<?php }?>> 
					<?= gettext("TO");?> : <select name="tostatsmonth">
					<?php 	$year_actual = date("Y");  	
						for ($i=$year_actual;$i >= $year_actual-1;$i--)
						{	
							$monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							if ($year_actual==$i){
								$monthnumber = date("n")-1; // Month number without lead 0.
							}else{
								$monthnumber=11;
							}		   
							for ($j=$monthnumber;$j>=0;$j--){	
								$month_formated = sprintf("%02d",$j+1);
								if ($tostatsmonth=="$i-$month_formated"){$selected="selected";}else{$selected="";}
								echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";						   }
						}
					?>
					</select>
					</td></tr></table>
	  			</td>
    		</tr>
			
			<tr>
        		<td align="left" bgcolor="#000033">
					<input type="radio" name="Period" value="Day" <?php  if ($Period=="Day"){ ?>checked="checked" <?php  } ?>> 
					<font face="verdana" size="1" color="#ffffff"><b><?= gettext("Selection of the day");?></b></font>
				</td>
      			<td align="left" bgcolor="#acbdee">
					<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#acbdee"><tr><td>
	  				<input type="checkbox" name="fromday" value="true" <?php  if ($fromday){ ?>checked<?php }?>> <?= gettext("FROM");?> :
					<select name="fromstatsday_sday">
						<?php  
							for ($i=1;$i<=31;$i++){
								if ($fromstatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
								echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
							}
						?>	
					</select>
				 	<select name="fromstatsmonth_sday">
					<?php 	$year_actual = date("Y");  	
						for ($i=$year_actual;$i >= $year_actual-1;$i--)
						{	
							$monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							if ($year_actual==$i){
								$monthnumber = date("n")-1; // Month number without lead 0.
							}else{
								$monthnumber=11;
							}		   
							for ($j=$monthnumber;$j>=0;$j--){	
								$month_formated = sprintf("%02d",$j+1);
								if ($fromstatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
								echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";
							   }
						}
					?>
					</select>
					<select name="fromstatsmonth_shour">
					<?php  
						if (strlen($fromstatsmonth_shour)==0) $fromstatsmonth_shour='0';
						for ($i=0;$i<=23;$i++){	
							if ($fromstatsmonth_shour==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}						
							echo '<option value="'.sprintf("%02d",$i)."\" $selected>".sprintf("%02d",$i).'</option>';
						}
					?>					
					</select>:<select name="fromstatsmonth_smin">
					<?php  
						if (strlen($fromstatsmonth_smin)==0) $fromstatsmonth_smin='0';
						for ($i=0;$i<=59;$i++){	
							if ($fromstatsmonth_smin==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}						
							echo '<option value="'.sprintf("%02d",$i)."\" $selected>".sprintf("%02d",$i).'</option>';
						}
					?>					
					</select>
					</td><td>&nbsp;&nbsp;
					<input type="checkbox" name="today" value="true" <?php  if ($today){ ?>checked<?php }?>> <?= gettext("TO");?> :
					<select name="tostatsday_sday">
					<?php  
						for ($i=1;$i<=31;$i++){
							if ($tostatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
							echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
						}
					?>						
					</select>
				 	<select name="tostatsmonth_sday">
					<?php 	$year_actual = date("Y");  	
						for ($i=$year_actual;$i >= $year_actual-1;$i--)
						{	
							$monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							if ($year_actual==$i){
								$monthnumber = date("n")-1; // Month number without lead 0.
							}else{
								$monthnumber=11;
							}		   
							for ($j=$monthnumber;$j>=0;$j--){
								$month_formated = sprintf("%02d",$j+1);
								if ($tostatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
								echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";
							   }
						}
					?>
					</select>
					<select name="tostatsmonth_shour">
					<?php  
						if (strlen($tostatsmonth_shour)==0) $tostatsmonth_shour='23';
						for ($i=0;$i<=23;$i++){	
							if ($tostatsmonth_shour==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}						
							echo '<option value="'.sprintf("%02d",$i)."\" $selected>".sprintf("%02d",$i).'</option>';
						}
					?>					
					</select>:<select name="tostatsmonth_smin">
					<?php  
						if (strlen($tostatsmonth_smin)==0) $tostatsmonth_smin='59';
						for ($i=0;$i<=59;$i++){	
							if ($tostatsmonth_smin==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}						
							echo '<option value="'.sprintf("%02d",$i)."\" $selected>".sprintf("%02d",$i).'</option>';
						}
					?>					
					</select>
					</td></tr></table>
	  			</td>
    		</tr>
			<tr>
				<td class="bar-search" align="left" bgcolor="#555577">			
					<font face="verdana" size="1" color="#ffffff"><b>&nbsp;&nbsp;<?= gettext("DESTINATION");?></b></font>
				</td>				
				<td class="bar-search" align="left" bgcolor="#cddeff">
				<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="dst" value="<?= $dst?>"></td>
				<td class="bar-search" align="center" bgcolor="#cddeff"><input type="radio" NAME="dsttype" value="1" <?php if((!isset($dsttype))||($dsttype==1)){?>checked<?php }?>> <?= gettext("Exact");?></td>
				<td class="bar-search" align="center" bgcolor="#cddeff"><input type="radio" NAME="dsttype" value="2" <?php if($dsttype==2){?>checked<?php }?>><?= gettext("Begins with");?></td>
				<td class="bar-search" align="center" bgcolor="#cddeff"><input type="radio" NAME="dsttype" value="3" <?php if($dsttype==3){?>checked<?php }?>><?= gettext("Contains");?></td>
				<td class="bar-search" align="center" bgcolor="#cddeff"><input type="radio" NAME="dsttype" value="4" <?php if($dsttype==4){?>checked<?php }?>><?= gettext("Ends with");?></td>
				<td class="bar-search" align="center" bgcolor="#cddeff"><input type="radio" NAME="dsttype" value="5" <?php if($sourcetype==5){?>checked<?php }?>><?= gettext("Is not");?></td>
				</tr></table></td>
			</tr>			
			<tr>
				<td align="left" bgcolor="#000033">					
					<font face="verdana" size="1" color="#ffffff"><b>&nbsp;&nbsp;<?= gettext("SOURCE");?></b></font>
				</td>				
				<td class="bar-search" align="left" bgcolor="#acbdee">
				<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#acbdee"><tr><td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="src" value="<?= "$src";?>"></td>
				<td class="bar-search" align="center" bgcolor="#acbdee"><input type="radio" NAME="srctype" value="1" <?php if((!isset($srctype))||($srctype==1)){?>checked<?php }?>><?= gettext("Exact");?></td>
				<td class="bar-search" align="center" bgcolor="#acbdee"><input type="radio" NAME="srctype" value="2" <?php if($srctype==2){?>checked<?php }?>><?= gettext("Begins with");?></td>
				<td class="bar-search" align="center" bgcolor="#acbdee"><input type="radio" NAME="srctype" value="3" <?php if($srctype==3){?>checked<?php }?>><?= gettext("Contains");?></td>
				<td class="bar-search" align="center" bgcolor="#acbdee"><input type="radio" NAME="srctype" value="4" <?php if($srctype==4){?>checked<?php }?>><?= gettext("Ends with");?></td>
				<td class="bar-search" align="center" bgcolor="#acbdee"><input type="radio" NAME="srctype" value="5" <?php if($srctype==5){?>checked<?php }?>><?= gettext("Is not");?></td>
				</tr></table></td>
			</tr>
			<tr>
        		<td class="bar-search" align="left" bgcolor="#000033"> </td>

				<td class="bar-search" align="center" bgcolor="#acbdee">
					<input class="form_enter" style="border: 2px outset rgb(204, 51, 0);" value=" <?= gettext("Search");?> " type="submit">
					&nbsp;&nbsp;&nbsp;&nbsp;
					<?= gettext("Result");?> : <?= gettext("Minutes");?><input type="radio" NAME="resulttype" value="min" <?php if((!isset($resulttype))||($resulttype=="min")){?>checked<?php }?>> - <?= gettext("Seconds");?> <input type="radio" NAME="resulttype" value="sec" <?php if($resulttype=="sec"){?>checked<?php }?>>
	  			</td>
    		</tr>
			<tr>
				<td class="bar-search" align="left" bgcolor="#555577">			
					<font face="verdana" size="1" color="#ffffff"></font>
				</td>				
				<td class="bar-search" align="left" bgcolor="#cddeff">
				<center>
					<?= gettext("See Invoice in HTML");?><input type="radio" NAME="exporttype" value="html" <?php if((!isset($exporttype))||($exporttype=="html")){?>checked<?php }?>>
					<?= gettext("or Export PDF");?> <input type="radio" NAME="exporttype" value="pdf" <?php if($exporttype=="pdf"){?>checked<?php }?>>
					 &nbsp;&nbsp; - &nbsp;&nbsp;
						<?= gettext("Currency");?> :
					<select NAME="choose_currency" size="1" class="form_enter" style="border: 2px outset rgb(204, 51, 0);">
						<?php
							$currencies_list = get_currencies();
							if (!isset($choose_currency))
								$choose_currency=$_SESSION['currency'];
							foreach($currencies_list as $key => $cur_value) {
						?>
							<option value='<?= $key ?>' <?php if ($choose_currency==$key){?>selected<?php } ?>><?= $cur_value[1].' ('.$cur_value[2].')' ?>
							</option>
						<?php 	} ?>
					</select>
				</center>
				</td>
			</tr>			
		
		</tbody></table>
	</FORM>
</center>

<br><br>


<?php  
}else{
   require('pdf-invoices/html2pdf/html2fpdf.php');
   ob_start();

} ?>

<table width="20%" align="center">
<tr>
<td> <img src="pdf-invoices/images/companylogo.gif"/> </td>
</tr>
</table>

<?php if (count($info_customer)>0 ){ /*FIXME*/ ?> 
<table width="100%"  bgcolor="#f3f3f3">

	<tr>
		<td width="75%">
		&nbsp;
		</td>		
		<td width="25%">
<br/>

<font color="#000000" face="verdana" size="3">

<?php  if (strlen($info_customer[0][3].$info_customer[0][2])>0) echo $info_customer[0][3].' '.$info_customer[0][2].' <br>'; ?>
<?php  if (strlen($info_customer[0][4].$info_customer[0][8])>0) echo $info_customer[0][4].' '.$info_customer[0][8].' <br>'; ?>
<?php  if (strlen($info_customer[0][5].$info_customer[0][6])>0) echo $info_customer[0][5].' '.$info_customer[0][6].' <br>'; ?>
<?php  if (strlen($info_customer[0][7])>0) echo $info_customer[0][7].'<br><br>'; ?>

<b><?php  if (strlen($info_customer[0][9])>0) echo gettext("Phone :").$info_customer[0][9]; ?></b><br>
<b><?php  if (strlen($info_customer[0][11])>0) echo gettext("Fax  :").$info_customer[0][11]; ?></b><br>
</font>
			
		</td>
	</tr>
</table>

<?php } ?>


<br><hr width="350"><br><br>

<table width="100%">
<tr>
<?php if (SHOW_ICON_INVOICE){?><td align="left"><img src="pdf-invoices/images/desktop.gif"/> </td><?php }?>
<td align="center"  bgcolor="#fff1d1"><font color="#000000" face="verdana" size="5"> <b><?= str_dblspace(gettext("BILLING SERVICE"));?> : <?php  if (strlen($info_customer[0][2])>0) echo $info_customer[0][2]; ?> </b> </td>
</tr>
</table>


<br><br>

<?php 
if (is_array($list_total_day_charge) && count($list_total_day_charge)>0){

$totalcharge=0;
$totalcost=0;
foreach ($list_total_day_charge as $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcharge+=$data[2];
	$totalcost+=$data[1];
}

?>


<!-- FIN TITLE GLOBAL MINUTES //-->
		
<table border="0" cellspacing="1" cellpadding="2" width="70%" align="center">
	<tr>	
		<td align="center" bgcolor="#600101"></td>
    	<td bgcolor="#b72222" align="center" colspan="4"><font color="#ffffff"><b><?= gettext("EXTRA CHARGE");?></b></font></td>
    </tr>
	<tr>

		<td align="center" bgcolor="#F2E8ED"><font face="verdana" size="1" color="#000000"><b><?= gettext("DATE");?></b></font></td>
        <td align="center"><font face="verdana" color="#000000" size="1"><b><?= gettext("NB CHARGE");?></b></font></td>
		<td align="center"><font face="verdana" color="#000000" size="1"><b><?= gettext("TOTALCOST");?></b></font></td>
 
<?php  		
		$i=0;
		foreach ($list_total_day_charge as $data){	
		$i=($i+1)%2;		
		 
		
	?>
	</tr>
	<tr>
		<td align="center" bgcolor="#D2D8ED"><font face="verdana" size="1" color="#000000"><?= $data[0]?></font></td>

        <td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center"><font face="verdana" color="#000000" size="1"><?= $data[2]?></font></td>
        
		<td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center"><font face="verdana" color="#000000" size="1"><?php  display_2bill($data[1]) ?></font></td>
     <?php 	 }	 	


	 ?>                   	
	</tr>	
	<tr bgcolor="#600101" >
		<td align="center" width="40%"></td>		
		<td align="center"><font color="#ffffff"><b><?= $totalcharge?></b></font></td>
		<td align="center"><font color="#ffffff"><b><?php  
display_2bill($totalcost) ?></b></font></td>
	</tr>
	
	<tr>		
		<td align="center" bgcolor="#BA5151" colspan="3"><font color="#ffffff">
		<b>TOTAL = 

<?php  
$prvat = ($vat / 100) * $totalcost;

display_2bill($totalcost + $prvat);
if ($vat>0) echo  " (".$vat." % ".gettext("VAT").")";

 ?>
 </b></font></td>
	</tr>
</table>
<?php  } ?>

<br><br><hr width="350"><br><br>

<?php 
if (is_array($list_total_destination) && count($list_total_destination)>0){


$mmax=0;
$totalcall=0;
$totalminutes=0;
$totalcost=0;
foreach ($list_total_destination as $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcall+=$data[3];
	$totalminutes+=$data[1];
	$totalcost+=$data[2];

}

?>

<!-- FIN TITLE GLOBAL MINUTES //-->
		
<table border="0" cellspacing="1" cellpadding="2" width="70%" align="center">
	<tr>	
		<td align="center" bgcolor="#600101"></td>
    	<td bgcolor="#b72222" align="center" colspan="4"><font color="#ffffff"><b><?= gettext("CALLS PER DESTINATION");?></b></font></td>
    </tr>
	<tr>
		<td align="right" bgcolor="#F2E8ED"><font face="verdana" size="1" color="#000000"><?= gettext("DESTINATION");?></font></td>
		<td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("DUR");?></font></td>
        <td align="center"><font face="verdana" color="#000000" size="1"><?= gettext("GRAPHIC");?> </font> </td>
        <td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("CALL");?></font></td>
		<td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("TOTAL COST");?></font></td>

   
<?php  		
		$i=0;
		foreach ($list_total_destination as $data){	
		$i=($i+1)%2;		
		$tmc = $data[1]/$data[3];
		
		if ((!isset($resulttype)) || ($resulttype=="min")){  
			$tmc = sprintf("%02d",intval($tmc/60)).":".sprintf("%02d",intval($tmc%60));		
		}else{
		
			$tmc =intval($tmc);
		}
		
		if ((!isset($resulttype)) || ($resulttype=="min")){  
				$minutes = sprintf("%02d",intval($data[1]/60)).":".sprintf("%02d",intval($data[1]%60));
		}else{
				$minutes = $data[1];
		}
		if ($mmax>0) 	$widthbar= intval(($data[1]/$mmax)*200); 
		
	?>
	</tr>
	<tr>
		<td align="right" bgcolor="#D2D8ED"><font face="verdana" size="1" color="#000000"><?= $data[0]?></font></td>
		<td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?= $minutes?> </font></td>
        <td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="left">
        	<img src="pdf-invoices/images/sidenav-selected.gif" height="6" width="<?= $widthbar?>">
		</td>
        <td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?= $data[3]?></font></td>
        
		<td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?php  display_2bill($data[2]) ?></font></td>
     <?php 	 }	 	 	
	 	
		if ((!isset($resulttype)) || ($resulttype=="min")){  
			$total_tmc = sprintf("%02d",intval(($totalminutes/$totalcall)/60)).":".sprintf("%02d",intval(($totalminutes/$totalcall)%60));				
			$totalminutes = sprintf("%02d",intval($totalminutes/60)).":".sprintf("%02d",intval($totalminutes%60));
		}else{
			$total_tmc = intval($totalminutes/$totalcall);			
		}
	 ?>                   	
	</tr>	
	<tr bgcolor="#600101">
		<td align="right"><font color="#ffffff"><b><?= gettext("TOTAL");?></b></font></td>
		<td align="center" colspan="2"><font color="#ffffff"><b><?= $totalminutes?> </b></font></td>
		<td align="center"><font color="#ffffff"><b><?= $totalcall?></b></font></td>
		<td align="center"><font color="#ffffff"><b><?php  display_2bill($totalcost) ?></b></font></td>
	</tr>
	<tr >		
		<td align="center" bgcolor="#BA5151" colspan="5"><font color="#ffffff">
		
		<b><?= gettext("TOTAL");?>&nbsp; =

<?php  
$prvat = ($vat / 100) * $totalcost;

display_2bill($totalcost + $prvat);
if ($vat>0) echo  " (".$vat." % ".gettext("VAT").")";

 ?>
 </b></font></td>
	</tr>
</table>

<br><hr width="350"><br><br>

<table width="100%">
<tr>
<?php if (SHOW_ICON_INVOICE){?><td align="left"><img src="pdf-invoices/images/stock_landline-phone.gif"/> </td><?php } ?>
<td align="center"  bgcolor="#fff1d1"><font color="#000000" face="verdana" size="5"> <b><?= str_dblspace(gettext("BILL EVOLUTION"));?></b> </td>
</tr>
</table>

<br><br>

<?php 
if (is_array($list_total_day) && count($list_total_day)>0){

$mmax=0;
$totalcall=0;
$totalminutes=0;
$totalcost=0;
foreach ($list_total_day as $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcall+=$data[3];
	$totalminutes+=$data[1];
	$totalcost+=$data[2];
}
?>
<!-- FIN TITLE GLOBAL MINUTES //-->
		
<table border="0" cellspacing="1" cellpadding="2" width="70%" align="center">
	<tr>	
	<td align="center" bgcolor="#600101"></td>
    	<td bgcolor="#b72222" align="center" colspan="4"><font color="#ffffff"><b></b></font></td>
    </tr>
	<tr>
		<td align="right" bgcolor="#F2E8ED"><font face="verdana" size="1" color="#000000"><?= gettext("DATE");?></font></td>
		<td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("DUR");?> </font></td>
        <td align="center"><font face="verdana" color="#000000" size="1"><?= gettext("GRAPHIC");?> </font> </td>
        <td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("CALL");?></font></td>
		<td align="right"><font face="verdana" color="#000000" size="1"><?= gettext("TOTAL COST");?></font></td>
	 
<?php  		
		$i=0;
		foreach ($list_total_day as $data){	
		$i=($i+1)%2;		
		$tmc = $data[1]/$data[3];
		
		if ((!isset($resulttype)) || ($resulttype=="min")){  
			$tmc = sprintf("%02d",intval($tmc/60)).":".sprintf("%02d",intval($tmc%60));		
		}else{
		
			$tmc =intval($tmc);
		}
		
		if ((!isset($resulttype)) || ($resulttype=="min")){  
				$minutes = sprintf("%02d",intval($data[1]/60)).":".sprintf("%02d",intval($data[1]%60));
		}else{
				$minutes = $data[1];
		}
		if ($mmax>0) 	$widthbar= intval(($data[1]/$mmax)*200); 
		
	?>
	</tr>
	<tr>
		<td align="right" bgcolor="#D2D8ED"><font face="verdana" size="1" color="#000000"><?= $data[0]?></font></td>
		<td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?= $minutes?> </font></td>
        <td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="left">
        	<img src="pdf-invoices/images/sidenav-selected.gif" height="6" width="<?= $widthbar?>">
		</td>
        <td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?= $data[3]?></font></td>
        
		<td bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right"><font face="verdana" color="#000000" size="1"><?php  display_2bill($data[2]) ?></font></td>
     <?php 	 }	 	 	
	 	
		if ((!isset($resulttype)) || ($resulttype=="min")){  
			$total_tmc = sprintf("%02d",intval(($totalminutes/$totalcall)/60)).":".sprintf("%02d",intval(($totalminutes/$totalcall)%60));				
			$totalminutes = sprintf("%02d",intval($totalminutes/60)).":".sprintf("%02d",intval($totalminutes%60));
		}else{
			$total_tmc = intval($totalminutes/$totalcall);			
		}
	 
	 ?>                   	
	</tr>	
	<tr bgcolor="#600101">
		<td align="right"><font color="#ffffff"><b><?= gettext("TOTAL");?></b></font></td>
		<td align="center" colspan="2"><font color="#ffffff"><b><?= $totalminutes?> </b></font></td>
		<td align="center"><font color="#ffffff"><b><?= $totalcall?></b></font></td>
		<td align="center"><font color="#ffffff"><b><?php  display_2bill($totalcost) ?></b></font></td>
	</tr>
	<tr>		
		<td align="center" bgcolor="#BA5151" colspan="5"><font color="#ffffff">
		<b>TOTAL = 

<?php  
$prvat = ($vat / 100) * $totalcost;

display_2bill($totalcost + $prvat);
if ($vat>0) echo  " (".$vat." % ".gettext("VAT").")";

 ?>
 </b></font></td>
	</tr>
</table>
	  
<?php  } ?>


<br><br><hr width="350"><br><br>

<table width="100%">
<tr>
<?php if (SHOW_ICON_INVOICE){?> <td align="left"><img src="pdf-invoices/images/kfind.gif"/> </td> <?php } ?>
<td align="center"  bgcolor="#fff1d1"><font color="#000000" face="verdana" size="5"> <b><?= str_dblspace(gettext("CALLS DETAIL"));?></b> </td>
</tr>
</table>

<br><br>


<!-- ** ** ** ** ** Part to display the CDR ** ** ** ** ** -->

		<center><?= gettext("Number of call");?> : <?php  if (is_array($list) && count($list)>0){ echo $nb_record; }else{echo "0";}?></center>
		<TABLE border=0 cellPadding=0 cellSpacing=0 width="<?= $FG_HTML_TABLE_WIDTH?>" align="center">
                <TR bgColor=#F0F0F0>
		  <TD width="7%" class="tableBodyRight" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px"><?= gettext("nb");?></TD>
                  <?php 
				  	if (is_array($list) && count($list)>0){
					
				  	for($i=0;$i<$FG_NB_TABLE_COL;$i++){ 
					?>				
				  
                  <TD width="<?= $FG_TABLE_COL[$i][2]?>" align=middle class="tableBody" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px">
                    <center>
                    <?= $FG_TABLE_COL[$i][0]?>
                   
                  </center></TD>
				   <?php } ?>		
				   <?php if ($FG_DELETION || $FG_EDITION){ ?>
				   <?php } ?>		
                </TR>
				<?php
				  	 $ligne_number=0;					 
				  	 foreach ($list as $recordset){ 
						 $ligne_number++;
				?>
				
               		 <TR bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>">
						<TD align="<?= $FG_TABLE_COL[$i][3]?>" class=tableBody><?php  echo $ligne_number+$current_page*$FG_LIMITE_DISPLAY; ?></TD>
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ 
							if ($FG_TABLE_COL[$i][6]=="lie"){
								$instance_sub_table = new Table($FG_TABLE_COL[$i][7], $FG_TABLE_COL[$i][8]);
								$sub_clause = str_replace("%id", $recordset[$i], $FG_TABLE_COL[$i][9]);	
								$select_list = $instance_sub_table -> Get_list ($DBHandle, $sub_clause, null, null, null, null, null, null);
									
									
								$field_list_sun = split(',',$FG_TABLE_COL[$i][8]);
								$record_display = $FG_TABLE_COL[$i][10];
									
								for ($l=1;$l<=count($field_list_sun);$l++){										
									$record_display = str_replace("%$l", $select_list[0][$l-1], $record_display);	
								}
								
							}elseif ($FG_TABLE_COL[$i][6]=="list"){
									$select_list = $FG_TABLE_COL[$i][7];
									$record_display = $select_list[$recordset[$i]][0];
							
							}else{
									$record_display = $recordset[$i];
							}
							
							
							if ( is_numeric($FG_TABLE_COL[$i][5]) && (strlen($record_display) > $FG_TABLE_COL[$i][5])  ){
								$record_display = substr($record_display, 0, $FG_TABLE_COL[$i][5]-3)."";  
															
							}
							
				 		 ?>
                 		 <TD align="<?= $FG_TABLE_COL[$i][3]?>" class=tableBody><?php
						 if (isset ($FG_TABLE_COL[$i][11]) && strlen($FG_TABLE_COL[$i][11])>1){
						 	call_user_func($FG_TABLE_COL[$i][11], $record_display);
						 }else{
						 	echo stripslashes($record_display);
						 }						 
						 ?></TD>
				 		 <?php  } ?>
                  
					</TR>
				<?php
					 }//foreach ($list as $recordset)
					 if ($ligne_number < $FG_LIMITE_DISPLAY)  $ligne_number_end=$ligne_number +2;
					 while ($ligne_number < $ligne_number_end){
					 	$ligne_number++;
				?>
					<TR bgcolor="<?= $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>">
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ 
				 		 ?>
                 		 <TD>&nbsp;</TD>
				 		 <?php  } ?>
                 		 <TD align="center">&nbsp;</TD>				
					</TR>
									
				<?php					 
					 } //END_WHILE
					 
				  }else{
				  		echo gettext("No data found !!!");
				  }//end_if
				 ?>
                             
            </TABLE>
			
			

<?php  if (is_array($list) && count($list)>0 && 3==4){ ?>
<!-- ************** TOTAL SECTION ************* -->
			<br/>
			<div style="padding-right: 15px;">
			<table cellpadding="1" bgcolor="#000000" cellspacing="1" width="<?php if ($_SESSION["is_admin"]==1){ ?>450<?php }else{?>200<?php }?>" align="right">
				<tbody>
                <tr class="form_head">                   									   
				   <td width="33%" align="center" class="tableBodyRight" bgcolor="#600101" style="padding: 5px;"><strong>TOTAL COSTS</strong></td>
				   <?php if ($_SESSION["is_admin"]==1){ ?><td width="33%" align="center" class="tableBodyRight" bgcolor="#600101" style="padding: 5px;"><strong><?= gettext("TOTAL BUYCOSTS");?></strong></td><?php }?>
				   <?php if ($_SESSION["is_admin"]==1){ ?><td width="33%" align="center" class="tableBodyRight" bgcolor="#600101" style="padding: 5px;"><strong><?= gettext("DIFFERENCE");?></strong></td><?php }?>
                </tr>
				<tr>
				  <td valign="top" align="center" class="tableBody" bgcolor="white"><b><?= $total_cost[0][0]?></b></td>
				  <?php if ($_SESSION["is_admin"]==1){ ?><td valign="top" align="center" class="tableBody" bgcolor="#66FF66"><b><?= $total_cost[0][1]?></b></td><?php }?>
				  <?php if ($_SESSION["is_admin"]==1){ ?><td valign="top" align="center" class="tableBody" bgcolor="#FF6666"><b><?= $total_cost[0][0]-$total_cost[0][1]?></b></td><?php }?>

				</tr>
			</table>
			</div>
			<br/><br/>
					
<!-- ************** TOTAL SECTION ************* -->
<?php  } ?>

<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br><br>

<!--
<br/>
<table width="60%"><tr><td>
<a href="export_pdf.php" target="_blank"><img src="../Images/pdf.gif	" border="0"/> Export PDF file</a>
</td>
<td>
<a href="export_csv.php" target="_blank" ><img src="../Images/excel.gif" border="0"/> Export CSV file</a>
</td></tr></table>-->

<?php  }else{ ?>
	<center><h3><?= gettext("No calls in your Selection");?>.</h3></center>
<?php  } ?>
</center>

<?php  if($exporttype!="pdf"){ ?>

<?php
	include("PP_footer.php");
?>

<?php  }else{
// EXPORT TO PDF

	$html = ob_get_contents();
	// delete output-Buffer
	ob_end_clean();
	
	$pdf = new HTML2FPDF();
	
	$pdf -> DisplayPreferences('HideWindowUI');
	
	$pdf -> AddPage();
	$pdf -> WriteHTML($html);
	
	$html = ob_get_contents();
	
	$pdf->Output('CC_invoice_'.date("d/m/Y-H:i").'.pdf', 'I');



} ?>