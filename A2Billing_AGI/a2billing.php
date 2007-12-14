#!/usr/bin/php -q
<?php   
/***************************************************************************
 *
 * a2billing.php : PHP A2Billing Core
 * Written for PHP 5.X versions.
 *
 * A2Billing -- Asterisk billing solution.
 * Copyright (C) 2007, P. Christeas <p_christeas A yahoo.com>
 * Copyright (C) 2004, 2007 Belaid Arezqui <areski _atl_ gmail com>
 *
 * See http://www.asterisk2billing.org for more information about
 * the A2Billing project. 
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 ****************************************************************************/

declare(ticks = 1);

function sig_handler($signo)
{

     switch ($signo) {
         case SIGTERM:
             // handle shutdown tasks
             throw new Exception("Term signal!",SIGTERM);
             break;
         case SIGHUP:
             throw new Exception("Hangup signal!",SIGHUP);
             break;
         case SIGINT:
             throw new Exception("Interrupt signal!",SIGINT);
             break;
         case SIGUSR1:
             echo "Caught SIGUSR1...\n";
             break;
         default:
             echo "Caught sighal $signo ..\n";
             // handle all other signals
     }

}

// Required!
pcntl_signal(SIGHUP, 'sig_handler');
pcntl_signal(SIGTERM, 'sig_handler');
pcntl_signal(SIGINT, 'sig_handler');

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

define(AGI_LIBDIR, dirname(__FILE__)."/libs_a2billing/");

require_once(AGI_LIBDIR.'Class.Config.inc.php');
require_once(AGI_LIBDIR."Class.A2Billing.inc.php");
require_once(AGI_LIBDIR."Misc.inc.php");
require_once(AGI_LIBDIR.'Class.DynConf.inc.php');
require_once(AGI_LIBDIR."/phpagi/phpagi.php");
// include (dirname(__FILE__)."/libs_a2billing/phpagi_2_14/phpagi-asmanager.php");
// include (dirname(__FILE__)."/libs_a2billing/Misc.php");

$charge_callback=0;
$G_startime = time();
$agi_date = "Release : you'd wish";
$agi_version = "Asterisk2Billing - Version v200/xrg - Alpha";
$conf_file = NULL;

if ($argc > 1 && ($argv[1] == '--version' || $argv[1] == '-V'))
{
	echo "A2Billing - Version $agi_version - $agi_date\n";
	exit;
}
$verbose_mode = false;

if ($argc > 1 && ($argv[1] == '--verbose' || $argv[1] == '-v')){
	AGI::verbose_s("Verbose mode!",0);
	error_reporting(E_ALL);
	$verbose_mode = true;
	array_shift($argv);
	$argc--;
}

if ($argc > 1 && ($argv[1] == '--test')){
	AGI::verbose_s("Testing mode!",0);
	define('DEFAULT_A2BILLING_CONFIG', "../a2billing.conf");
	array_shift($argv);
	$argc--;
}

// create the objects
$a2b = A2Billing::instance();
$dynconf = DynConf::instance();

if ($argc > 1 && is_numeric($argv[1]) && $argv[1] >= 0){
	$idconfig = $argv[1];
}else{
	$idconfig = 1;
}

try {
	$dynconf->init();
	$dynconf->PrefetchGroup('agiconf'.$idconfig);
} catch (Exception $ex){
	error_log($ex->getMessage());
	@syslog(LOG_ERR,"Cannot Fetch config!");
	@syslog(LOG_ERR,$ex->getMessage());
	exit();
}

if ($verbose_mode)
	$dynconf->SetDefVar('agiconf'.$idconfig,'debug',true);

$agi = new AGI($dynconf,'agiconf'.$idconfig);

if (!$agi->is_alive)
	exit();

$mode = 'standard';
if ($argc > 2 && strlen($argv[2]) > 0 )
	switch ($argv[2]) {
	case 'did':
	case 'callback':
	case 'cid-callback':
	case 'all-callback':
	case 'predictivedialer':
	case 'voucher':
		$mode = $argv[2];
		break;
	default:
		echo "Unknown mode: ". $argv[2] . "\n";
	}

// get the area code for the cid-callback & all-callback
if ($argc > 3 && strlen($argv[3]) > 0) $caller_areacode = $argv[3];

function getAGIconfig($var,$default){
	global $dynconf;
	global $idconfig;
	return $dynconf->GetCfgVar('agiconf'.$idconfig,$var,$default);
}

// Authorize and return an array with card data.
function getCard(){
	global $agi;
	// *-*
	return array('cardid' => 1, 'tgid' => 1);
}

// Lock the card and return remaining money

function CardGetMoney(&$card){
	return array('base' => 2.0, 'local' => 1.8);
}

//Unlock the card (decrease usage count)
function ReleaseCard(&$card){
	//TODO!
}

function getDialNumber(){
	return '329486253';
}

if ($mode == 'standard'){

	if (getAGIconfig('early_answer',false))
		$agi->answer();
	else
		$agi->exec('Progress');
		
		// Repeat until we hangup
	for($num_try = 0;$num_try<getAGIconfig('number_try',3);$num_try++){
		$card = getCard();
		
		if (! $card)
			continue;
		
		// Here, we're authorized..
		//TODO: set callerid
		
		//TODO: fix lang
		$card_money = CardGetMoney($card);
		//TODO: play balance, intros
		
		if ($card_money['base'] < getAGIconfig('min_credit_2call',0.01)){
			// not enough money!
			ReleaseCard($card);
			continue;
		}
		
		$dialnumber = getDialNumber();
		
		$QRY = str_dbparams($a2b->DBHandle(),'SELECT * FROM RateEngine2(%#1, %2, now(), %3);',
			array($card['tgid'],$dialnumber,$card_money['base']));
			
		$res = $a2b->DBHandle()->Execute($QRY);
		if (!$res){
			if(getAGIconfig('say_errors',true))
				$agi-> stream_file('allison2'/*-*/, '#');
			ReleaseCard($card);
			break;
		}elseif($res->EOF){
			$agi-> stream_file('prepaid-no-route'/*-*/, '#');
			ReleaseCard($card);
			continue;
		}
		$routes = $res->GetArray();
		
		// TODO: musiconhold
		//TODO: record_call
		//TODO: outbound cid
		
		try {
		foreach ($routes as $route){
			$dialstr = 'aaaa';
			$call_res= $agi->exec('Dial',$dialstr);
			//TODO: if record, stop
			
			$answeredtime = $agi->get_variable("ANSWEREDTIME");
			$this -> real_answeredtime = $this -> answeredtime = $answeredtime['data'];
			$dialstatus = $agi->get_variable("DIALSTATUS");
			$this -> dialstatus = $dialstatus['data'];
			
			//TODO: SIP, ISDN extended status

		}
		}catch (Exception $ex){
			// Here we handle signals received
			ReleaseCard($card);
			break;
		}
		
		ReleaseCard($card);
	}
	
	if(getAGIconfig('say_goodbye',true))
		$agi-> stream_file('prepaid-final', '#');
	$agi->hangup();
	exit();

	
}// mode standard

exit();
?>
