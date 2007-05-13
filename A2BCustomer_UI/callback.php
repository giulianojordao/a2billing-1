<?php
include ("lib/defines.php");
include ("lib/module.access.php");
include ("lib/Class.RateEngine.php");	 
include (dirname(__FILE__)."/lib/phpagi/phpagi-asmanager.php");


getpost_ifset(array('callback', 'called', 'calling'));

if (!$A2B->config["webcustomerui"]['callback']) exit();

if (! has_rights (ACX_ACCESS)){
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

$FG_DEBUG = 0;
$color_msg = 'red';

$QUERY = "SELECT  username, credit, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, lastuse, activated FROM cc_card WHERE username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'";

$DBHandle_max  = DbConnect();
$numrow = 0;
$resmax = $DBHandle_max -> Execute($QUERY);
if ($resmax)	
	$numrow = $resmax -> RecordCount();

if ($numrow == 0) exit();
$customer_info =$resmax -> fetchRow();

if( $customer_info [13] != "t" && $customer_info [13] != "1" ) {
	exit();
}


if ($callback){
	$called=ereg_replace("^\+","011",$called);
	$calling=ereg_replace("^\+","011",$calling);
	
	$called=ereg_replace("[^0-9]","",$called);
	$calling=ereg_replace("[^0-9]","",$calling);
	
	$called=ereg_replace("^01100","011",$called);
	$calling=ereg_replace("^01100","011",$calling);
	
	$called=ereg_replace("^00","011",$called);
	$calling=ereg_replace("^00","011",$calling);
	
	$called=ereg_replace("^0111","1",$called);
	$calling=ereg_replace("^0111","1",$calling);
	
	if (strlen($called)>4 && strlen($calling)>4 && is_numeric($called) && is_numeric($calling)){
				
			$A2B -> DBHandle = DbConnect();
			$instance_table = new Table();
			$A2B -> set_instance_table ($instance_table);
			$A2B -> cardnumber = $_SESSION["pr_login"];
				
			if ($A2B -> callingcard_ivr_authenticate_light ($error_msg)){
			
				$RateEngine = new RateEngine();
				$RateEngine -> webui = 0;
				// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION
				
				$A2B ->agiconfig['accountcode']=$_SESSION["pr_login"];
				$A2B ->agiconfig['use_dnid']=1;
				$A2B ->agiconfig['say_timetocall']=0;						
				$A2B ->dnid = $A2B ->destination = $called;
				
				$resfindrate = $RateEngine->rate_engine_findrates($A2B, $called, $_SESSION["tariff"]);
				
				// IF FIND RATE
				if ($resfindrate!=0){				
					$res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);
					if ($res_all_calcultimeout){							
						
						// MAKE THE CALL
						if ($RateEngine -> ratecard_obj[0][34]!='-1'){
							$usetrunk = 34; 
							$usetrunk_failover = 1;
							$RateEngine -> usedtrunk = $RateEngine -> ratecard_obj[$k][34];
						} else {
							$usetrunk = 29;
							$RateEngine -> usedtrunk = $RateEngine -> ratecard_obj[$k][29];
							$usetrunk_failover = 0;
						}
						
						$prefix			= $RateEngine -> ratecard_obj[0][$usetrunk+1];
						$tech 			= $RateEngine -> ratecard_obj[0][$usetrunk+2];
						$ipaddress 		= $RateEngine -> ratecard_obj[0][$usetrunk+3];
						$removeprefix 	= $RateEngine -> ratecard_obj[0][$usetrunk+4];
						$timeout		= $RateEngine -> ratecard_obj[0]['timeout'];	
						$failover_trunk	= $RateEngine -> ratecard_obj[0][40+$usetrunk_failover];
						$addparameter	= $RateEngine -> ratecard_obj[0][42+$usetrunk_failover];
						
						$destination = $called;
						if (strncmp($destination, $removeprefix, strlen($removeprefix)) == 0) $destination= substr($destination, strlen($removeprefix));
						
						$pos_dialingnumber = strpos($ipaddress, '%dialingnumber%' );
						$ipaddress = str_replace("%cardnumber%", $A2B->cardnumber, $ipaddress);
						$ipaddress = str_replace("%dialingnumber%", $prefix.$destination, $ipaddress);
						
						if ($pos_dialingnumber !== false){					   
							$dialstr = "$tech/$ipaddress".$dialparams;
						}else{
							if ($A2B->agiconfig['switchdialcommand'] == 1){
								$dialstr = "$tech/$prefix$destination@$ipaddress".$dialparams;
							}else{
								$dialstr = "$tech/$ipaddress/$prefix$destination".$dialparams;
							}
						}	
						
						//ADDITIONAL PARAMETER 			%dialingnumber%,	%cardnumber%	
						if (strlen($addparameter)>0){
							$addparameter = str_replace("%cardnumber%", $A2B->cardnumber, $addparameter);
							$addparameter = str_replace("%dialingnumber%", $prefix.$destination, $addparameter);
							$dialstr .= $addparameter;
						}
						
						$channel= $dialstr;
						$exten = $calling;
						$context = $A2B -> config["callback"]['context_callback'];
						$id_server_group = $A2B -> config["callback"]['id_server_group'];
						$priority=1;
						$timeout = $A2B -> config["callback"]['timeout']*1000;
						$application='';
						$callerid = $A2B -> config["callback"]['callerid'];
						$account = $_SESSION["pr_login"];
						
						$uniqueid 	=  MDP_NUMERIC(5).'-'.MDP_STRING(7);
						$status = 'PENDING';
						$server_ip = 'localhost';
						$num_attempt = 0;
						$variable = "CALLED=$called|CALLING=$calling|CBID=$uniqueid|LEG=".$A2B->cardnumber;
						
						$QUERY = " INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, channel, exten, context, priority, variable, id_server_group, callback_time, account, callerid, timeout ) VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '$channel', '$exten', '$context', '$priority', '$variable', '$id_server_group',  now(), '$account', '$callerid', '30000')";
						$res = $A2B -> DBHandle -> Execute($QUERY);
						
						if (!$res){
							$error_msg= gettext("Cannot insert the callback request in the spool!");
						}else{
							$error_msg = gettext("Your callback request has been queued correctly!");
							$color_msg = 'green';
						}
						
						/*
						$as = new AGI_AsteriskManager();
						
						// && CONNECTING  connect($server=NULL, $username=NULL, $secret=NULL)
						
						$res = $as->connect(MANAGER_HOST,MANAGER_USERNAME,MANAGER_SECRET);
						
						if	($res){
							$channel= $dialstr;
							$exten = $calling;
							$context = $A2B -> config["callback"]['context_callback'];
							$priority=1;
							$timeout = $A2B -> config["callback"]['timeout']*1000;
							$application='';
							$callerid = $A2B -> config["callback"]['callerid'];
							$account=$_SESSION["pr_login"];

							$variable = "CALLED=$called|CALLING=$calling";
							$res = $as->Originate($channel, $exten, $context, $priority, $application, $data, $timeout, $callerid, $variable, $account, $async, $actionid);							
							if($res["Response"]=='Error'){
								if (is_numeric($failover_trunk) && $failover_trunk>=0){
									$QUERY = "SELECT trunkprefix, providertech, providerip, removeprefix FROM cc_trunk WHERE id_trunk='$failover_trunk'";
									$A2B->instance_table = new Table();
									$result = $A2B->instance_table -> SQLExec ($A2B -> DBHandle, $QUERY);
									if (is_array($result) && count($result)>0){
											
											//DO SELECT WITH THE FAILOVER_TRUNKID											
											$prefix			= $result[0][0];
											$tech 			= $result[0][1];
											$ipaddress 		= $result[0][2];
											$removeprefix 	= $result[0][3];
											
											$pos_dialingnumber = strpos($ipaddress, '%dialingnumber%' );
											$ipaddress = str_replace("%cardnumber%", $A2B->cardnumber, $ipaddress);
											$ipaddress = str_replace("%dialingnumber%", $prefix.$destination, $ipaddress);
											if (strncmp($destination, $removeprefix, strlen($removeprefix)) == 0) $destination= substr($destination, strlen($removeprefix));
											$dialparams = str_replace("%timeout%", $timeout *1000, $A2B->agiconfig['dialcommand_param']);
									
											$A2B->agiconfig['switchdialcommand']=1;
											$dialparams='';
											
											if ($pos_dialingnumber !== false){					   
												   $dialstr = "$tech/$ipaddress".$dialparams;
											}else{
												if ($A2B->agiconfig['switchdialcommand'] == 1){
													$dialstr = "$tech/$prefix$destination@$ipaddress".$dialparams;
												}else{
													$dialstr = "$tech/$ipaddress/$prefix$destination".$dialparams;
												}
											}											
											$channel= $dialstr;											
											$res = $as->Originate($channel, $exten, $context, $priority, $application, $data, $timeout, $callerid, $variable, $account, $async, $actionid);
									}
								}
							}
							
							// && DISCONNECTING	
							$as->disconnect();
						}else{
							$error_msg= gettext("Cannot connect to the asterisk manager!<br>Please check the manager configuration...");
						}
						*/
						
					}else{
						$error_msg = gettext("Error : You don t have enough credit to call you back!");
					}
				}else{
					$error_msg = gettext("Error : There is no route to call back your phonenumber!");
				}
				
			}else{
				// ERROR MESSAGE IS CONFIGURE BY THE callingcard_ivr_authenticate_light
			}
		}else{
			$error_msg = gettext("Error : You have to specify your phonenumber and the number you wish to call!");
		
		}
}
$customer = $_SESSION["pr_login"];


<?php
	include("PP_header.php");
?><br>
	<center>
	 <font class="fontstyle_007"> 
	 <font face='Arial, Helvetica, sans-serif' size='2' color='<?php echo $color_msg; ?>'><b>
	 	<?php echo $error_msg ?> 
	 </b></font>
	 <br><br>
	  <?php echo gettext("You can initiate the callback by entering your phonenumber and the number you wish to call!");?>
	  </center>
	   <table align="center"  border="0" width="75%" bgcolor="#eeeeee">
		<form name="theForm" action=<?php echo $PHP_SELF;?> method="POST" >
		<INPUT type="hidden" name="callback" value="1">
		<tr bgcolor="#cccccc">
		<td align="left" valign="bottom">
				<br/>
				<?php echo gettext("Your PhoneNumber");?> :
				<input class="form_enter" name="called" value="<?php echo $called; ?>" size="30" maxlength="40" style="border: 2px inset rgb(204, 51, 0);">
				<br/><br/>
				<?php echo gettext("The number you wish to call");?> :
				<input class="form_enter" name="calling" value="<?php echo $calling; ?>" size="30" maxlength="40" style="border: 2px inset rgb(204, 51, 0);">
				<br/><br/>
			</td>	
			<td align="center" valign="middle"> 
			<input class="form_enter" style="border: 2px outset rgb(204, 51, 0);" value="[ <?php echo gettext("Click here to Place Call");?> ]" type="submit"> 
			</td>
        </tr>
		</form>
      </table>
	  <br>
<br></br><br></br>
<?php
	include("PP_footer.php");
?>
