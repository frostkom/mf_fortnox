<?php
/*
Plugin Name: MF Fortnox
Plugin URI: https://github.com/frostkom/mf_fortnox
Description: Adds support for communicating with the Fortnox API
Version: 1.0.3
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_fortnox
Domain Path: /lang

Documentation: https://api.fortnox.se/apidocs
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_fortnox = new mf_fortnox();

	add_action('cron_base', array($obj_fortnox, 'cron_base'), mt_rand(1, 10));

	add_action('init', array($obj_fortnox, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_fortnox');

		add_action('admin_init', array($obj_fortnox, 'settings_fortnox'));
		add_filter('pre_update_option', array($obj_fortnox, 'pre_update_option'), 10, 3);
		add_action('admin_menu', array($obj_fortnox, 'admin_menu'));

		add_filter('manage_'.$obj_fortnox->post_type.'_posts_columns', array($obj_fortnox, 'column_header'), 5);
		add_action('manage_'.$obj_fortnox->post_type.'_posts_custom_column', array($obj_fortnox, 'column_cell'), 5, 2);
		add_filter('manage_'.$obj_fortnox->post_type_vouchers.'_posts_columns', array($obj_fortnox, 'column_header'), 5);
		add_action('manage_'.$obj_fortnox->post_type_vouchers.'_posts_custom_column', array($obj_fortnox, 'column_cell'), 5, 2);

		add_action('rwmb_meta_boxes', array($obj_fortnox, 'rwmb_meta_boxes'));
	}

	if(wp_doing_ajax())
	{
		add_action('wp_ajax_api_fortnox_run', array($obj_fortnox, 'api_fortnox_run'), 10, 1);
	}

	function uninstall_fortnox()
	{
		include_once("include/classes.php");

		$obj_fortnox = new mf_fortnox();

		mf_uninstall_plugin(array(
			'options' => array('setting_fortnox_client_id', 'setting_fortnox_client_secret', 'setting_fortnox_scope', 'option_fortnox_scope', 'option_fortnox_database_number', 'setting_fortnox_authorization_code', 'setting_fortnox_access_token', 'setting_fortnox_refresh_token', 'setting_fortnox_endpoint', 'setting_fortnox_debug'),
			'post_types' => array($obj_fortnox->post_type),
		));
	}
}