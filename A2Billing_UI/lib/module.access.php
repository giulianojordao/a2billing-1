<?php /* file module.access.php
	
	Module access - an access control module for back office areas


If you're using $_SESSION , make sure you aren't using session_register() too.
From the manual.
If you are using $_SESSION (or $HTTP_SESSION_VARS), do not use session_register(), session_is_registered() and session_unregister().


*/
error_reporting(E_ALL & ~E_NOTICE);

// Zone strings
define ("MODULE_ACCESS_DOMAIN",		"CallingCard System");
define ("MODULE_ACCESS_DENIED",		"./Access_denied.htm");

define ("ACX_CUSTOMER",		1);
define ("ACX_BILLING",		2);		// 1 << 1
define ("ACX_RATECARD",		4);		// 1 << 2
define ("ACX_TRUNK",   		8);		// 1 << 3
define ("ACX_CALL_REPORT",   	16);		// 1 << 4
define ("ACX_CRONT_SERVICE",   	32);		// 1 << 5
define ("ACX_ADMINISTRATOR",   	64);		// 1 << 6
define ("ACX_FILE_MANAGER",   	128);		// 1 << 7
define ("ACX_MISC",   		256);		// 1 << 8
define ("ACX_DID",   		512);		// 1 << 9
define ("ACX_CALLBACK",		1024);		// 1 << 10
define ("ACX_OUTBOUNDCID",	2048);		// 1 << 11
define ("ACX_PACKAGEOFFER",	4096);		// 1 << 12
define ("ACX_PRED_DIALER",	8192);		// 1 << 13
define ("ACX_INVOICING",	16384);		// 1 << 14
define ("ACX_AGENTS", 		0x8000);	// 1 << 15
define ("ACX_NUMPLAN",		0x10000);
define ("ACX_SERVERS",		0x20000);
define ("ACX_PRICING",		0x40000);
define ("ACX_QUEUES",		0x80000);
define ("ACX_NETMON",		0x100000);

define("ACX_ROOT",		0x1FFFFF);
define('DEFAULT_CONFIG', '/etc/a2billing.conf');

header("Expires: Sat, Jan 01 2000 01:01:01 GMT");
//echo "PHP_AUTH_USER : $PHP_AUTH_USER";

if (!isset($_SESSION)) {
	session_name("UIADMINSESSION");
	session_start();
}

$URI = $_SERVER['REQUEST_URI'];
$restircted_url = substr($URI,-16);
if(($restircted_url != "PP_intro.php") && ($restircted_url != "signup/index.php")) {
	require_once("./lib/Class.Logger.inc.php");
	if (!isset($log))
		$log= new Logger(); // TODO: instance
	$log -> insertLog($_SESSION['pr_userid'], 1, "Page Visit", "User Visited the Page", '', $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'],'');
	//$log = null;
}


if (isset($_GET["logout"]) && $_GET["logout"]=="true") {
	require_once("./lib/Class.Logger.inc.php");
	if (!isset($log))
		$log = new Logger(/*new Config()*/); //TODO..
	$log -> insertLog($_SESSION['pr_userid'], 1, "USER LOGGED OUT", "User Logged out from website", '', $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'],'');
	$log = null;
	session_destroy();
	$rights=0;
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: index.php");
	exit();
}
	

function access_sanitize_data($data)
{
	$lowerdata = strtolower ($data);
	$data = str_replace('--', '', $data);	
	$data = str_replace("'", '', $data);
	$data = str_replace('=', '', $data);
	$data = str_replace(';', '', $data);
	if (!(strpos($lowerdata, ' or ')===FALSE)){ return false;}
	if (!(strpos($lowerdata, 'table')===FALSE)){ return false;}
	
	return $data;
}

function has_rights ($condition) {	
	return ($_SESSION["rights"] & $condition);
}

function session_readonly(){
	return ($_SESSION['readonly'] == true);
}

	// Easter egg to let debug from url
if (isset($_GET['debug']) && is_numeric($_GET['debug']))
	$_SESSION['FG_DEBUG']= $FG_DEBUG = $_GET['debug'];
elseif (isset($_SESSION['FG_DEBUG']))
	$FG_DEBUG =$_SESSION['FG_DEBUG'];

require_once("lang-settings.inc.php");
?>
