<?php

error_reporting(E_ALL & ~E_NOTICE);
header("Expires: Sat, Jan 01 2000 01:01:01 GMT");

if (!isset($_SESSION)) {
	session_name("UISIGNUP");
	session_start();
}

function has_rights ($condition) {	
	return ($condition == 1);
}

require_once(DIR_COMMON."languageSettings.inc.php");

    if (isset($_GET['language'])){
    	if ($FG_DEBUG >0) echo "<!-- lang explicitly set to ".$_GET['language'] ."-->\n";
      $_SESSION["language"] = $_GET['language'];
    }
    else if (!isset($_SESSION["language"]))
	$_SESSION["language"] = negot_language('english');


    define ("LANGUAGE",$_SESSION["language"]);
	//include (FSROOT."lib/languages/".LANGUAGE.".php");
	//define ("LANGUAGE_DIR",FSROOT."lib/languages/".LANGUAGE."/");

    $lang_abbr=SetLocalLanguage($_SESSION["language"]);
    if ($FG_DEBUG >5) trigger_error("lang abbr: $lang_abbr",E_USER_NOTICE);

?>
