<?php
require_once ("./lib/defines.php");
require_once ("./lib/module.access.php");
require_once ("a2blib/Form.inc.php");
require_once ("a2blib/Class.HelpElem.inc.php");
require_once ("a2blib/Form/Class.SqlRefField.inc.php");
require_once ("a2blib/Form/Class.VolField.inc.php");
require_once ("a2blib/Form/Class.TimeField.inc.php");
require_once ("a2blib/Class.JQuery.inc.php");

$menu_section='menu_customers';


HelpElem::DoHelp(_("Asterisk users are the entries that provide SIP/IAX peers for asterisk."),'vcard.png');

$HD_Form= new FormHandler('cc_ast_users',_("Users"),_("User"));
$HD_Form->checkRights(ACX_CUSTOMER);
$HD_Form->init();
$HD_Form->views['tooltip'] = new DetailsMcView();

$PAGE_ELEMS[] = &$HD_Form;
$PAGE_ELEMS[] = new AddNewButton($HD_Form);

$HD_Form->model[] = new PKeyFieldEH(_("ID"),'id');

$HD_Form->model[] = new SqlBigRefField(_("Card"),'card_id','cc_card','id','username',_("Corresponding card"));
$HD_Form->model[] = new SqlBigRefField(_("Booth"),'booth_id','cc_booth','id','peername',_("Booth (if no card)"));

$HD_Form->model[] = new SqlRefField(_("Config"), "config","cc_ast_users_config", "id", "cfg_name");

$HD_Form->model[] = new BoolField(_("SIP"),'has_sip',_("If true, the peer will have a SIP entry"));
$HD_Form->model[] = new BoolField(_("IAX"),'has_iax',_("If true, the peer will have a IAX2 entry"));
$HD_Form->model[] = DontList(new TextFieldN(_("Default IP"),'defaultip',_("Default IP to ring user at.")));
$HD_Form->model[] = new TextField(_("Host"),'host',_("Statically bind user with some IP/DNS or 'dynamic' for users that will register."));
	end($HD_Form->model)->def_value='dynamic';

$HD_Form->model[] = new TextFieldN(_("Name B"),'peernameb',_("Override asterisk username, so that a second device can be registered"));
if (! session_readonly())
	$HD_Form->model[] = dontList(new TextFieldN(_("Secret B"),'secretb',_("Override asterisk secret from card/booth, so that a second device can be registered")));
$HD_Form->model[] = dontList(new TextFieldN(_("Callerid B"),'callerid',_("Override callerid.")));

$HD_Form->model[] = DontList( new TextFieldN(_("From user"),'fromuser',_("Override user string.")));

$HD_Form->model[] = DontList(new TextFieldN(_("Call group"),'callgroup',_("When this device is called, set the call group so that others can pick it up.")));
$HD_Form->model[] = DontList(new TextFieldN(_("Pickup group"),'pickupgroup',_("Allow this device to pick up calls made to those groups.")));

$HD_Form->model[] = DontList( new TextFieldN(_("Device Model"),'devmodel',_("Provision model of device.")));
$HD_Form->model[] = DontList( new TextFieldN(_("MAC"),'macaddr',_("MAC address of provisioned device.")));
$HD_Form->model[] = DontList( new TextField(_("D Secret"),'devsecret',_("Device secret, provision safety.")));
$HD_Form->model[] = DontList( new IntFieldN(_("Provision Name"),'provi_name',_("Provisioned name (display text)")));

$HD_Form->model[] = DontList( new IntFieldN(_("Provision Num"),'provi_num',_("Provision configuration number")));
$HD_Form->model[] = DontList( new DateTimeFieldN(_("Last provisioned"),'provi_date',_("Last provision timestamp")));

$HD_Form->model[] = new DelBtnField();


if($HD_Form->getAction()=='tooltip')
	require("PP_bare_page.inc.php");
else
	require("PP_page.inc.php");

?>