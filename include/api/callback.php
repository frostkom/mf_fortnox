<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_fortnox/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

require_once("../classes.php");

$obj_fortnox = new mf_fortnox();

$json_output = array(
	'success' => false,
);

$code = check_var('code', 'char');
$state = check_var('state', 'char');

$json_output['response'] = var_export($_REQUEST, true);

$state_check = "mf_fortnox";

if($code != '')
{
	update_option('setting_fortnox_authorization_code', $code, false);

	do_log("Authorization Code Saved: ".$code.", ".$state." (".var_export($_REQUEST, true).")", 'notification', false);
}

else
{
	do_log("Authorization Code NOT Saved: ".$code.", ".$state." (".var_export($_REQUEST, true).")", 'publish', false);
}

$json_output['success'] = true;

echo json_encode($json_output);