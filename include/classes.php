<?php

class mf_fortnox
{
	var $post_type = __CLASS__;
	var $post_type_vouchers;
	var $meta_prefix;
	var $redirect_uri = "/wp-content/plugins/mf_fortnox/include/api/callback.php";

	function __construct()
	{
		$this->post_type_vouchers = $this->post_type.'_vouchers';
		$this->meta_prefix = $this->post_type.'_';
	}

	function fetch_from_api($data)
	{
		global $wpdb, $obj_base;

		$obj_encryption = new mf_encryption(__CLASS__);

		if(!isset($data['endpoint']))
		{
			$data['endpoint'] = get_option('setting_fortnox_endpoint', 'products');
		}

		$result = array(
			'success' => false,
			'html' => "",
		);

		$setting_fortnox_client_id = get_option('setting_fortnox_client_id');
		$setting_fortnox_client_secret = get_option('setting_fortnox_client_secret');

		if($setting_fortnox_client_secret != '')
		{
			$setting_fortnox_client_secret = $obj_encryption->decrypt($setting_fortnox_client_secret, md5(AUTH_KEY));
		}

		if($setting_fortnox_client_id != '' && $setting_fortnox_client_secret != '')
		{
			switch($data['endpoint'])
			{
				case 'generate_access_token':
				case 'generate_access_token_from_refresh':
					$url = 'https://api.fortnox.se/oauth-v1/token';

					$arr_headers = [
						'Content-Type: application/x-www-form-urlencoded',
						'Authorization: Basic '.base64_encode($setting_fortnox_client_id.":".$setting_fortnox_client_secret),
					];

					switch($data['endpoint'])
					{
						case 'generate_access_token':
							$setting_fortnox_authorization_code = get_option('setting_fortnox_authorization_code');

							$post_data = http_build_query([
								'grant_type' => 'authorization_code',
								'code' => $setting_fortnox_authorization_code,
								'redirect_uri' => get_site_url().$this->redirect_uri,
							]);
						break;

						case 'generate_access_token_from_refresh':
							$setting_fortnox_refresh_token = get_option('setting_fortnox_refresh_token');

							$post_data = http_build_query([
								'grant_type' => 'refresh_token',
								'refresh_token' => $setting_fortnox_refresh_token,
							]);
						break;
					}

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
						'post_data' => $post_data,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							/*{
								"access_token": "xyz...",
								"refresh_token": "a7302e6b-b1cb-4508-b884-cf9abd9a51de",
								"scope": "companyinformation",
								"expires_in": 3600,
								"token_type": "bearer"
							}*/

							if(isset($arr_json['access_token']) && $arr_json['access_token'] != '')
							{
								update_option('setting_fortnox_access_token', $arr_json['access_token'], false);
							}

							if(isset($arr_json['refresh_token']) && $arr_json['refresh_token'] != '')
							{
								update_option('setting_fortnox_refresh_token', $arr_json['refresh_token'], false);
							}

							if(isset($arr_json['scope']) && $arr_json['scope'] != '')
							{
								update_option('option_fortnox_scope', $arr_json['scope'], false);
							}

							delete_option('setting_fortnox_authorization_code');

							switch($data['action'])
							{
								case 'insert':
									// Do nothing. It has been saved above
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." => ".$headers['http_code']." => ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;

				case 'companyinformation':
					$url = "https://api.fortnox.se/3/companyinformation";

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							/*array ( 'CompanyInformation' => array ( 'Address' => '[text]', 'City' => '[city]', 'CountryCode' => 'SE', 'DatabaseNumber' => [number], 'CompanyName' => '[text]', 'OrganizationNumber' => 'XXXXXX-XXXX', 'VisitAddress' => NULL, 'VisitCity' => NULL, 'VisitCountryCode' => NULL, 'VisitZipCode' => NULL, 'ZipCode' => '[number]', ), )*/

							if(isset($arr_json['CompanyInformation']['DatabaseNumber']) && $arr_json['CompanyInformation']['DatabaseNumber'] != '')
							{
								update_option('option_fortnox_database_number', $arr_json['CompanyInformation']['DatabaseNumber'], false); // Then -> https://www.fortnox.se/developer/authorization/get-access-token-using-client-credentials
							}						

							switch($data['action'])
							{
								case 'insert':
									// Do what?
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;

				case 'invoices':
					$url = "https://api.fortnox.se/3/invoices";

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							/*array (
								'MetaInformation' => array (
									'@TotalResources' => 15,
									'@TotalPages' => 1,
									'@CurrentPage' => 1,
								),
								'Invoices' => array (
									0 => array (
										'@url' => 'https://api.fortnox.se/3/invoices/1',
										'Balance' => 0,
										'Booked' => true,
										'Cancelled' => false,
										'CostCenter' => '',
										'Currency' => 'SEK',
										'CurrencyRate' => '1',
										'CurrencyUnit' => 1,
										'CustomerName' => '[text]',
										'CustomerNumber' => '1',
										'DocumentNumber' => '1',
										'DueDate' => 'YYYY-MM-DD',
										'ExternalInvoiceReference1' => '',
										'ExternalInvoiceReference2' => '',
										'InvoiceDate' => 'YYYY-MM-DD',
										'InvoiceType' => 'INVOICE',
										'NoxFinans' => false,
										'OCR' => '133',
										'VoucherNumber' => 1,
										'VoucherSeries' => 'B',
										'VoucherYear' => 1,
										'WayOfDelivery' => '',
										'TermsOfPayment' => '15',
										'Project' => '',
										'Sent' => true,
										'Total' => 20000,
										'FinalPayDate' => 'YYYY-MM-DD',
									),
								),
							)*/

							switch($data['action'])
							{
								case 'insert':
									// Do what?
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;

				case 'payments':
					$url = "https://api.fortnox.se/3/invoicepayments"; // https://api.fortnox.se/apidocs#tag/fortnox_InvoicePayments

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							switch($data['action'])
							{
								case 'insert':
									do_log("Payments: ".var_export($arr_json, true));
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;

				case 'vouchers':
					$url = "https://api.fortnox.se/3/vouchers/?voucherseries=A"; //&accountnumber=1901

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							switch($data['action'])
							{
								case 'insert':
									/*array (
										'MetaInformation' => array (
											'@TotalResources' => 164,
											'@TotalPages' => 2,
											'@CurrentPage' => 1,
										),
										'Vouchers' => array (
											0 => array (
												'@url' => 'https://api.fortnox.se/3/vouchers/A/1?financialyear=1',
												'Comments' => NULL,
												'Description' => 'Inbetalning medlemsavgift Swish / [Name] [Name]',
												'ReferenceNumber' => '',
												'ReferenceType' => '',
												'TransactionDate' => 'YYYY-MM-DD',
												'VoucherNumber' => 1,
												'VoucherSeries' => 'A',
												'Year' => 1,
												'ApprovalState' => 0,
											)
										)
									)*/

									foreach($arr_json['Vouchers'] as $arr_voucher)
									{
										$voucher_id = $arr_voucher['Year'].":".$arr_voucher['VoucherSeries'].":".$arr_voucher['VoucherNumber'];
										$voucher_name = $arr_voucher['Description'];
										$voucher_excerpt = "";
										$voucher_content = $arr_voucher['Comments'];
										$voucher_reference_no = $arr_voucher['ReferenceNumber'];
										$voucher_reference_type = $arr_voucher['ReferenceType'];
										$voucher_number = $arr_voucher['VoucherNumber'];
										$voucher_series = $arr_voucher['VoucherSeries'];
										$voucher_year = $arr_voucher['Year'];
										$voucher_approval_state = $arr_voucher['ApprovalState'];
										$voucher_amount = "";
										$voucher_created = date("Y-m-d H:i:s", strtotime($arr_voucher['TransactionDate']." 00:00:00"));

										if(isset($arr_voucher['@url']) && $arr_voucher['@url'] != '')
										{
											$arr_voucher_info = $this->fetch_from_api(['endpoint' => 'voucher', 'url' => $arr_voucher['@url'], 'action' => 'insert']);

											//do_log(__FUNCTION__.": ".var_export($arr_voucher_info, true));

											/*'Voucher' => array (
												'@url' => 'https://api.fortnox.se/3/vouchers/A/12?financialyear=1',
												'Comments' => NULL,
												'CostCenter' => '',
												'Description' => 'Inbetalning medlemsavgift Swish / [name]',
												'Project' => '',
												'ReferenceNumber' => '',
												'ReferenceType' => '',
												'TransactionDate' => 'YYYY-MM-DD',
												'VoucherNumber' => 12,
												'VoucherRows' => array (
													0 => array (
														'Account' => 1901,
														'CostCenter' => '',
														'Credit' => 0,
														'Description' => 'Swish Nordea',
														'Debit' => 500,
														'Project' => '',
														'Removed' => false,
														'TransactionInformation' => '[info] 1802936198795036 / Swishnummer: 1232335578',
														'Quantity' => 0,
													),
													1 => array (
														'Account' => 3000,
														'CostCenter' => '',
														'Credit' => 500,
														'Description' => 'Försäljning inom Sverige',
														'Debit' => 0,
														'Project' => '',
														'Removed' => false,
														'TransactionInformation' => '[info] 1802936198795036 / Swishnummer: 1232335578',
														'Quantity' => 0,
													),
												),
												'VoucherSeries' => 'A',
												'Year' => 1,
												'ApprovalState' => 0,
											)*/

											$voucher_account = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Account'];
											$voucher_amount = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Debit'];
											$voucher_excerpt = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Description'];
											$voucher_content = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['TransactionInformation'];
										}

										//$arr_voucher_tags = $arr_voucher['tags'];

										$post_data = array(
											'post_type' => $this->post_type_vouchers,
											'post_status' => 'publish',
											'post_title' => $voucher_name,
											'post_excerpt' => $voucher_excerpt,
											'post_content' => $voucher_content,
											'meta_input' => array(
												$this->meta_prefix.'voucher_id' => $voucher_id,
												$this->meta_prefix.'voucher_account' => $voucher_account,
												$this->meta_prefix.'voucher_amount' => $voucher_amount,
												$this->meta_prefix.'voucher_reference_no' => $voucher_reference_no,
												$this->meta_prefix.'voucher_reference_type' => $voucher_reference_type,
												$this->meta_prefix.'voucher_number' => $voucher_number,
												$this->meta_prefix.'voucher_series' => $voucher_series,
												$this->meta_prefix.'voucher_year' => $voucher_year,
												$this->meta_prefix.'voucher_approval_state' => $voucher_approval_state,
											),
										);

										$result = $obj_base->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND meta_value = %s", $this->meta_prefix.'voucher_id', $this->post_type_vouchers, $voucher_id));
										$voucher_amount = count($result);

										if($voucher_amount > 0)
										{
											$i = 0;

											foreach($result as $r)
											{
												$post_voucher_id = $r->ID;

												if($i == 0)
												{
													$post_date = get_post_field('post_date', $post_voucher_id);

													if($voucher_created != $post_date)
													{
														//do_log(__FUNCTION__." - Created: ".$post_date." -> ".$voucher_created);

														$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $voucher_created, $post_voucher_id));
													}

													if(1 == 1)
													{
														$post_data['ID'] = $post_voucher_id;
														$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input'], $post_data['ID']);

														wp_update_post($post_data);
													}
												}

												else
												{
													wp_trash_post($post_voucher_id);
												}

												$i++;
											}
										}

										else
										{
											$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input']);

											$post_voucher_id = wp_insert_post($post_data);

											$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $voucher_created, $post_voucher_id));
										}

										/*if($post_voucher_id > 0)
										{
											wp_set_post_terms($post_voucher_id, $arr_voucher_tags, $this->taxonomy_tags, false);
										}*/
									}
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;

				case 'voucher':
					$url = $data['url'];

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
						'headers' => $arr_headers,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							switch($data['action'])
							{
								case 'insert':
									/*array (
										'Voucher' => array (
											'@url' => 'https://api.fortnox.se/3/vouchers/A/12?financialyear=1',
											'Comments' => NULL,
											'CostCenter' => '',
											'Description' => 'Inbetalning medlemsavgift Swish / [name]',
											'Project' => '',
											'ReferenceNumber' => '',
											'ReferenceType' => '',
											'TransactionDate' => 'YYYY-MM-DD',
											'VoucherNumber' => 12,
											'VoucherRows' => array (
												0 => array (
													'Account' => 1901,
													'CostCenter' => '',
													'Credit' => 0,
													'Description' => 'Swish Nordea',
													'Debit' => 500,
													'Project' => '',
													'Removed' => false,
													'TransactionInformation' => '[info] 1802936198795036 / Swishnummer: 1232335578',
													'Quantity' => 0,
												),
												1 => array (
													'Account' => 3000,
													'CostCenter' => '',
													'Credit' => 500,
													'Description' => 'Försäljning inom Sverige',
													'Debit' => 0,
													'Project' => '',
													'Removed' => false,
													'TransactionInformation' => '[info] 1802936198795036 / Swishnummer: 1232335578',
													'Quantity' => 0,
												),
											),
											'VoucherSeries' => 'A',
											'Year' => 1,
											'ApprovalState' => 0,
										),
									)*/

									$result['success'] = true;
									$result['array'] = $arr_json;
								break;

								case 'print':
									$result['success'] = true;
									$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
								break;
							}
						break;

						default:
							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message);
								break;

								case 'print':
									$result['html'] .= $log_message;

									if($content === false)
									{
										$result['html'] .= "cURL error: ".curl_error($ch);
									}
								break;
							}
						break;
					}
				break;
			}
		}

		else
		{
			$result['html'] .= __("You have to enter Client ID and Secret first", 'lang_fortnox');
		}

		return $result;
	}

	function cron_base()
	{
		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			if(get_option('setting_fortnox_refresh_token') != '')
			{
				$this->fetch_from_api(['endpoint' => 'generate_access_token_from_refresh', 'action' => 'insert']);
			}

			else if(get_option('setting_fortnox_authorization_code') != '')
			{
				$this->fetch_from_api(['endpoint' => 'generate_access_token', 'action' => 'insert']);
			}

			$this->fetch_from_api(['endpoint' => 'vouchers', 'action' => 'insert']);

			mf_uninstall_plugin(array(
				'options' => array('setting_fortnox_tenant_id'),
			));
		}

		$obj_cron->end();
	}

	function init()
	{
		load_plugin_textdomain('lang_fortnox', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("Customers", 'lang_fortnox'),
				'singular_name' => __("Customer", 'lang_fortnox'),
				'menu_name' => __("Customers", 'lang_fortnox'),
				'all_items' => __("List", 'lang_fortnox'),
				'edit_item' => __("Edit", 'lang_fortnox'),
				'view_item' => __("View", 'lang_fortnox'),
				'add_new_item' => __("Add New", 'lang_fortnox'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_vouchers, array(
			'labels' => array(
				'name' => __("Vouchers", 'lang_fortnox'),
				'singular_name' => __("Voucher", 'lang_fortnox'),
				'menu_name' => __("Vouchers", 'lang_fortnox'),
				'all_items' => __("List", 'lang_fortnox'),
				'edit_item' => __("Edit", 'lang_fortnox'),
				'view_item' => __("View", 'lang_fortnox'),
				'add_new_item' => __("Add New", 'lang_fortnox'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'supports' => array('title', 'editor'),
			'hierarchical' => true,
			'has_archive' => false,
		));
	}

	function settings_fortnox()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_fortnox_client_id'] = __("Client ID", 'lang_fortnox');
		$arr_settings['setting_fortnox_client_secret'] = __("Client Secret", 'lang_fortnox');
		$arr_settings['setting_fortnox_scope'] = __("Scope", 'lang_fortnox');

		//$arr_settings['setting_fortnox_authorization_code'] = __("Authorization Code", 'lang_fortnox');
		//$arr_settings['setting_fortnox_access_token'] = __("Access Token", 'lang_fortnox');
		//$arr_settings['setting_fortnox_refresh_token'] = __("Refresh Token", 'lang_fortnox');

		$arr_settings['setting_fortnox_endpoint'] = __("Endpoint", 'lang_fortnox');
		$arr_settings['setting_fortnox_debug'] = __("Debug", 'lang_fortnox');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function pre_update_option($new_value, $option_key, $old_value)
	{
		if($new_value != '')
		{
			switch($option_key)
			{
				case 'setting_fortnox_client_secret':
					$obj_encryption = new mf_encryption(__CLASS__);
					$new_value = $obj_encryption->encrypt($new_value, md5(AUTH_KEY));
				break;
			}
		}

		return $new_value;
	}

	function settings_fortnox_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Fortnox", 'lang_fortnox'));
	}

	function setting_fortnox_client_id_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_fortnox_client_secret_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		if($option != '')
		{
			$obj_encryption = new mf_encryption(__CLASS__);
			$option = $obj_encryption->decrypt($option, md5(AUTH_KEY));
		}

		echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='new-password'"));
	}

	function setting_fortnox_scope_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array(
			'archive' => __("Archive", 'lang_fortnox'),
			'article' => __("Article", 'lang_fortnox'),
			'bookkeeping' => __("Bookkeeping", 'lang_fortnox'),
			'companyinformation' => __("Company Information", 'lang_fortnox'),
			'costcenter' => __("Costcenter", 'lang_fortnox'),
			'connectfile' => __("Connect File", 'lang_fortnox'),
			'currency' => __("Currency", 'lang_fortnox'),
			'customer' => __("Customer", 'lang_fortnox'),
			'deletevoucher' => __("Delete Voucher", 'lang_fortnox'),
			'developerapi' => __("Developer API", 'lang_fortnox'),
			'invoice' => __("Invoice", 'lang_fortnox'),
			'noxfinansinvoice' => __("NOX Finance Invoice", 'lang_fortnox'),
			'order' => __("Order", 'lang_fortnox'),
			'inbox' => __("Inbox", 'lang_fortnox'),
			'payment' => __("Payment", 'lang_fortnox'),
			'price' => __("Price", 'lang_fortnox'),
			'print' => __("Print", 'lang_fortnox'),
			'profile' => __("Profile", 'lang_fortnox'),
			'project' => __("Project", 'lang_fortnox'),
			'settings' => __("Settings", 'lang_fortnox'),
			'supplier' => __("Supplier", 'lang_fortnox'),
			'supplierinvoice' => __("Supplier Invoice", 'lang_fortnox'),
			'warehouse' => __("Warehouse", 'lang_fortnox'),
		);

		echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));

		$setting_fortnox_authorization_code = get_option('setting_fortnox_authorization_code');

		echo "<p>"
			.__("Authorization Code", 'lang_fortnox').": ";

			if($setting_fortnox_authorization_code != '')
			{
				echo shorten_text(array('string' => $setting_fortnox_authorization_code, 'limit' => 10));
			}

			else
			{
				$setting_fortnox_scope = get_option('setting_fortnox_scope');

				if(is_array($setting_fortnox_scope) && count($setting_fortnox_scope) > 0)
				{
					$scope = implode("%20", $setting_fortnox_scope);
				}

				else
				{
					$scope = "companyinformation";
				}

				$state_check = "mf_fortnox";

				echo "<a href='https://api.fortnox.se/oauth-v1/auth?response_type=code&client_id=".get_option('setting_fortnox_client_id')."&redirect_uri=".get_site_url().$this->redirect_uri."&scope=".$scope."&state=".$state_check."&access_type=offline&response_type=code&account_type=service'>".__("Get your code here", 'lang_fortnox')."</a>";
			}

		echo "</p>";

		$option_fortnox_database_number = get_option('option_fortnox_database_number');

		if($option_fortnox_database_number != '')
		{
			echo "<p>".__("Database Number", 'lang_fortnox').": ".$option_fortnox_database_number."</p>";
		}

		$option_fortnox_scope = get_option('option_fortnox_scope');

		if($option_fortnox_scope != '')
		{
			echo "<p>".__("Scope", 'lang_fortnox').": ".$option_fortnox_scope."</p>";
		}

		$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

		if($setting_fortnox_access_token != '')
		{
			echo "<p>".__("Access Token", 'lang_fortnox').": ".shorten_text(array('string' => $setting_fortnox_access_token, 'limit' => 10))."</p>";
		}

		$setting_fortnox_refresh_token = get_option('setting_fortnox_refresh_token');

		if($setting_fortnox_refresh_token != '')
		{
			echo "<p>".__("Refresh Token", 'lang_fortnox').": ".shorten_text(array('string' => $setting_fortnox_refresh_token, 'limit' => 10))."</p>";
		}
	}
	
	function setting_fortnox_authorization_code_callback() // https://www.fortnox.se/developer/authorization/get-authorization-code
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_fortnox_access_token_callback() // https://www.fortnox.se/developer/authorization/get-access-token
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));	
	}

	function setting_fortnox_refresh_token_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));	
	}

	function setting_fortnox_endpoint_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = [];

		if(get_option('setting_fortnox_authorization_code') != '')
		{
			$arr_data['generate_access_token'] = __("Generate Access Token from Access Code", 'lang_membership_io');
		}

		if(get_option('setting_fortnox_refresh_token') != '')
		{
			$arr_data['generate_access_token_from_refresh'] = __("Generate Access Token from Refresh Token", 'lang_membership_io');
		}

		if(get_option('setting_fortnox_access_token') != '')
		{
			$arr_data['companyinformation'] = __("Company Information", 'lang_membership_io');
			$arr_data['invoices'] = __("Invoices", 'lang_membership_io');
			$arr_data['payments'] = __("Payments", 'lang_membership_io');
			$arr_data['vouchers'] = __("Vouchers", 'lang_membership_io');
		}

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'allow_hidden_field' => false));
	}

	function setting_fortnox_debug_callback()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_fortnox', $plugin_include_url."script_wp.js", array(
			'ajax_url' => admin_url('admin-ajax.php'),
		));

		echo "<div".get_form_button_classes().">"
			.show_button(array('type' => 'button', 'name' => 'btnFortnoxRun', 'text' => __("Run Now", 'lang_fortnox'), 'class' => 'button-secondary'))
		."</div>
		<p id='api_fortnox_run'></p>";
	}

	function admin_menu()
	{
		$menu_start = "edit.php?post_type=".$this->post_type;
		$menu_capability = 'edit_posts';

		$menu_title = __("Fortnox", 'lang_fortnox');
		add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, '', 'dashicons-cart', 21);

		$menu_title = __("Customers", 'lang_fortnox');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$menu_title = __("Vouchers", 'lang_fortnox');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_vouchers);

		if(IS_EDITOR)
		{
			$menu_title = __("Settings", 'lang_fortnox');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_fortnox"));
		}
	}

	function column_header($columns)
	{
		global $post_type;

		//do_action('load_font_awesome');

		unset($columns['date']);

		switch($post_type)
		{
			case $this->post_type:
				//$columns['customer_id'] = __("ID", 'lang_fortnox');
				//$columns['contact'] = __("Contact", 'lang_fortnox');
			break;

			case $this->post_type_vouchers:
				//$columns['voucher_id'] = __("ID", 'lang_fortnox');
				$columns['voucher_excerpt'] = __("Description", 'lang_fortnox');
				$columns['voucher_content'] = __("Information", 'lang_fortnox');
				$columns['voucher_account'] = __("Account", 'lang_fortnox');
				$columns['voucher_amount'] = __("Amount", 'lang_fortnox');
				$columns['voucher_reference_no'] = __("Reference No.", 'lang_fortnox');
				$columns['voucher_reference_type'] = __("Reference Type", 'lang_fortnox');
				$columns['voucher_number'] = __("Number", 'lang_fortnox');
				$columns['voucher_series'] = __("Series", 'lang_fortnox');
				$columns['voucher_year'] = __("Year", 'lang_fortnox');
				$columns['voucher_approval_state'] = __("State", 'lang_fortnox');
			break;
		}

		return $columns;
	}

	function column_cell($column, $post_id)
	{
		global $post, $wpdb;

		switch($post->post_type)
		{
			case $this->post_type:
				switch($column)
				{
					/*case 'customer_id':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;

					case 'contact':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.'customer_email', true);

						if($post_meta != '')
						{
							//$obj_encryption = new mf_encryption(__CLASS__);
							//$post_meta = $obj_encryption->decrypt($post_meta, md5(AUTH_KEY));

							echo "<a href='mailto:".$post_meta."' title='".$post_meta."'><i class='fa fa-paper-plane fa-lg'></i></a> ";
						}

						$post_meta = get_post_meta($post_id, $this->meta_prefix.'customer_phone', true);

						if($post_meta != '')
						{
							$obj_encryption = new mf_encryption(__CLASS__);
							$post_meta = $obj_encryption->decrypt($post_meta, md5(AUTH_KEY));

							echo "<a href='tel:".$post_meta."' title='".$post_meta."'><i class='fa fa-phone fa-lg'></i></a> ";
						}
					break;*/
				}
			break;

			case $this->post_type_vouchers:
				switch($column)
				{
					case 'voucher_excerpt':
						$post_meta = get_post_field('post_excerpt', $post_id);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;

					case 'voucher_content':
						$post_meta = get_post_field('post_content', $post_id);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;

					case 'voucher_amount':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta." ".__("USD", 'lang_fortnox');
						}
					break;

					case 'voucher_id':
					case 'voucher_account':
					case 'voucher_reference_no':
					case 'voucher_reference_type':
					case 'voucher_number':
					case 'voucher_series':
					case 'voucher_year':
					case 'voucher_approval_state':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;
				}
			break;
		}
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		/*$meta_boxes[] = array(
			'id' => $this->meta_prefix.'information',
			'title' => __("Information", 'lang_fortnox'),
			'post_types' => array($this->post_type),
			'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("ID", 'lang_fortnox'),
					'id' => $this->meta_prefix.'customer_id',
					'type' => 'text',
				),
			)
		);*/

		return $meta_boxes;
	}

	function api_fortnox_run()
	{
		$result = $this->fetch_from_api(array('action' => 'print'));

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}
}