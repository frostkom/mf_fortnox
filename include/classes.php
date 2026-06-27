<?php

class mf_fortnox
{
	var $post_type = __CLASS__;
	var $post_type_customer;
	var $post_type_invoices;
	var $post_type_payments;
	var $post_type_vouchers;
	var $meta_prefix;
	var $redirect_uri = "/wp-content/plugins/mf_fortnox/include/api/callback.php";
	var $default_endpoint_to_fetch = array('invoices', 'payments', 'vouchers');
	var $api_rate_limit_seconds = 5;
	var $api_rate_limit_requests = 25;
	var $api_rate_count_requests = 0;

	function __construct()
	{
		$this->post_type_customer = $this->post_type.'_customer';
		$this->post_type_invoices = $this->post_type.'_invoices';
		$this->post_type_payments = $this->post_type.'_payments';
		$this->post_type_vouchers = $this->post_type.'_vouchers';
		$this->meta_prefix = $this->post_type.'_';
	}

	function save_customer($data)
	{
		global $wpdb, $obj_base;

		$customer_id = $data['id'];
		$customer_name = $data['name'];

		$post_data = array(
			'post_type' => $this->post_type_customer,
			'post_status' => 'publish',
			'post_title' => $customer_name,
			'meta_input' => array(
				$this->meta_prefix.'customer_id' => $customer_id,
			),
		);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_date, post_modified FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND post_status = %s AND meta_value = %s", $this->meta_prefix.'customer_id', $this->post_type_customer, 'publish', $customer_id));
		$customer_amount = count($result);

		if($customer_amount > 0)
		{
			$i = 0;

			foreach($result as $r)
			{
				$post_customer_id = $r->ID;
				$post_customer_date = $r->post_date;
				$post_customer_modified = $r->post_modified;

				if($i == 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $post_customer_date, $post_customer_id));

					if($post_customer_modified < date("Y-m-d H:i:s", strtotime(current_time('mysql')." -4 hour")))
					{
						$post_data['ID'] = $post_customer_id;
						$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input'], $post_data['ID']);

						wp_update_post($post_data);
					}
				}

				else
				{
					wp_trash_post($post_customer_id);
				}

				$i++;
			}
		}

		else
		{
			$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input']);

			$post_customer_id = wp_insert_post($post_data);
		}

		return $post_customer_id;
	}

	function get_invoice_info($arr_invoice, $post_data)
	{
		//$invoice_ = "";

		if(isset($arr_invoice['@url']) && $arr_invoice['@url'] != '')
		{
			$arr_invoice_info = $this->fetch_from_api(['endpoint' => 'invoice', 'url' => $arr_invoice['@url'], 'action' => 'insert']);

			//do_log(__FUNCTION__.": ".var_export($arr_invoice_info, true), 'publish', false);

			/*array (
				'success' => true,
				'html' => '',
				'array' => array (
					'Invoice' => array (
						'@url' => 'https://api.fortnox.se/3/invoices/98',
						'@urlTaxReductionList' => 'https://api.fortnox.se/3/taxreductions?filter=invoices&referencenumber=98',
						'AccountingMethod' => 'ACCRUAL',
						'Address1' => 'c/o ...',
						'Address2' => '',
						'AdministrationFee' => 0,
						'AdministrationFeeVAT' => 0,
							'Balance' => 0,
						'BasisTaxReduction' => 0,
							'Booked' => true,
							'Cancelled' => false,
						'City' => '[name]',
						'Comments' => '',
						'ContractReference' => '0',
						'ContributionPercent' => 100,
						'ContributionValue' => [number],
							'CostCenter' => '',
						'Country' => '[text]',
						'Credit' => 'false',
						'CreditInvoiceReference' => '0',
							'Currency' => 'SEK',
							'CurrencyRate' => 1,
							'CurrencyUnit' => 1,
							'CustomerName' => '[name]',
							'CustomerNumber' => '1',
						'DeliveryAddress1' => '',
						'DeliveryAddress2' => '',
						'DeliveryCity' => '',
						'DeliveryCountry' => '',
						'DeliveryDate' => NULL,
						'DeliveryName' => '',
						'DeliveryZipCode' => '',
							'DocumentNumber' => '98',
							'DueDate' => 'YY-MM-DD',
						'EDIInformation' => array (
							'EDIGlobalLocationNumber' => '',
							'EDIGlobalLocationNumberDelivery' => '',
							'EDIInvoiceExtra1' => '',
							'EDIInvoiceExtra2' => '',
							'EDIOurElectronicReference' => '',
							'EDIYourElectronicReference' => '123-45678901',
							'EDIStatus' => '8',
						),
						'EUQuarterlyReport' => false,
						'EmailInformation' => array (
							'EmailAddressFrom' => NULL,
							'EmailAddressTo' => '',
							'EmailAddressCC' => '',
							'EmailAddressBCC' => '',
							'EmailSubject' => 'Faktura {no} bifogas',
							'EmailBody' => ' ',
						),
							'ExternalInvoiceReference1' => '',
							'ExternalInvoiceReference2' => '',
							'FinalPayDate' => 'YY-MM-DD',
						'Freight' => 0,
						'FreightVAT' => 0,
						'Gross' => [number],
						'HouseWork' => false,
							'InvoiceDate' => 'YY-MM-DD',
						'InvoicePeriodEnd' => '',
						'InvoicePeriodStart' => '',
						'InvoiceReference' => '0',
						'InvoiceRows' => array (
							0 => array (
								'AccountNumber' => 3001,
								'ArticleNumber' => '5',
								'ContributionPercent' => '100',
								'ContributionValue' => '[number]',
								'Cost' => NULL,
								'CostCenter' => NULL,
								'DeliveredQuantity' => '1',
								'Description' => '[text]',
								'Discount' => 0,
								'DiscountType' => 'PERCENT',
								'HouseWork' => false,
								'HouseWorkHoursToReport' => NULL,
								'HouseWorkType' => NULL,
								'Price' => [number],
								'PriceExcludingVAT' => [number],
								'Project' => '',
								'RowId' => 335,
								'StockPointCode' => NULL,
								'Total' => [number],
								'TotalExcludingVAT' => [number],
								'Unit' => '',
								'VAT' => 25,
								'VATCode' => NULL,
							),
						),
							'InvoiceType' => 'INVOICE',
						'Labels' => array ( ),
						'Language' => 'SV',
						'LastRemindDate' => NULL,
						'Net' => [number],
						'NotCompleted' => false,
							'NoxFinans' => false,
							'OCR' => '9845',
						'OfferReference' => '0',
						'OrderReference' => '0',
						'OrganisationNumber' => '[number]',
						'OurReference' => '[full name]',
						'OutboundDate' => 'YY-MM-DD',
						'PaymentWay' => '',
						'Phone1' => '040-0123456',
						'Phone2' => '',
						'PriceList' => 'A',
						'PrintTemplate' => 'st',
							'Project' => '',
						'Remarks' => '',
						'Reminders' => 0,
						'RoundOff' => 0,
							'Sent' => true,
						'TaxReduction' => NULL,
						'TaxReductionType' => 'none',
						'TermsOfDelivery' => '',
							'TermsOfPayment' => '30',
						'TimeBasisReference' => 0,
							'Total' => [number],
						'TotalToPay' => [number],
						'TotalVAT' => [number],
						'VATIncluded' => false,
							'VoucherNumber' => 56,
							'VoucherSeries' => 'B',
							'VoucherYear' => 2,
						'WarehouseReady' => true,
							'WayOfDelivery' => '',
						'YourOrderNumber' => '',
						'YourReference' => '123-45678901',
						'ZipCode' => '[number]',
					),
				),
			)*/

			/*if(isset($arr_invoice_info['array']))
			{
				$invoice_account = $arr_invoice_info['array']['invoice']['invoiceRows'][0]['Account'];
				$invoice_amount = $arr_invoice_info['array']['invoice']['invoiceRows'][0]['Debit'];
				$invoice_excerpt = $arr_invoice_info['array']['invoice']['invoiceRows'][0]['Description'];
				$invoice_content = $arr_invoice_info['array']['invoice']['invoiceRows'][0]['TransactionInformation'];
			}*/
		}

		/*$post_data['post_excerpt'] = $invoice_excerpt;
		$post_data['post_content'] = $invoice_content;
		$post_data['meta_input'][$this->meta_prefix.'invoice_account'] = $invoice_account;
		$post_data['meta_input'][$this->meta_prefix.'invoice_amount'] = $invoice_amount;*/

		return $post_data;
	}

	function get_payment_info($arr_payment, $post_data)
	{
		$post_customer_id = $payment_due_date = $payment_mode_of_payment = $payment_mode_of_payment_account = $payment_voucher_number = $payment_voucher_series = $payment_voucher_year = "";

		if(isset($arr_payment['@url']) && $arr_payment['@url'] != '')
		{
			$arr_payment_info = $this->fetch_from_api(['endpoint' => 'payment', 'url' => $arr_payment['@url'], 'action' => 'insert']);

			//do_log(__FUNCTION__.": ".var_export($arr_payment_info, true), 'publish', false);

			/*array (
				'success' => true,
				'html' => '',
				'array' => array (
					'InvoicePayment' => array (
						'@url' => 'https://api.fortnox.se/3/invoicepayments/101',
							'Amount' => [number],
							'AmountCurrency' => [number],
							'Booked' => true,
							'Currency' => 'SEK',
							'CurrencyRate' => 1,
							'CurrencyUnit' => 1,
						'ExternalInvoiceReference1' => '',
						'ExternalInvoiceReference2' => '',
						'InvoiceCustomerName' => '[name]',
						'InvoiceCustomerNumber' => '1',
							'InvoiceNumber' => 103,
						'InvoiceDueDate' => 'YY-MM-DD',
						'InvoiceOCR' => '10355',
							'InvoiceTotal' => '[number]',
						'ModeOfPayment' => 'BG',
						'ModeOfPaymentAccount' => 1930,
							'Number' => '101',
							'PaymentDate' => 'YY-MM-DD',
						'VoucherNumber' => 73,
						'VoucherSeries' => 'C',
						'VoucherYear' => 2,
							'Source' => 'file',
							'WriteOffs' => array ( ),
					),
				),
			)*/

			if(isset($arr_payment_info['array']))
			{
				$post_customer_id = $this->save_customer(array('id' => $arr_payment_info['array']['InvoicePayment']['InvoiceCustomerNumber'], 'name' => $arr_payment_info['array']['InvoicePayment']['InvoiceCustomerName']));

				$post_data['post_title'] = "#".$arr_payment_info['array']['InvoicePayment']['Number'];

				$payment_due_date = $arr_payment_info['array']['InvoicePayment']['InvoiceDueDate'];
				$payment_mode_of_payment = $arr_payment_info['array']['InvoicePayment']['ModeOfPayment'];
				$payment_mode_of_payment_account = $arr_payment_info['array']['InvoicePayment']['ModeOfPaymentAccount'];
				$payment_voucher_number = $arr_payment_info['array']['InvoicePayment']['VoucherNumber'];
				$payment_voucher_series = $arr_payment_info['array']['InvoicePayment']['VoucherSeries'];
				$payment_voucher_year = $arr_payment_info['array']['InvoicePayment']['VoucherYear'];
			}
		}

		$post_data['meta_input'][$this->meta_prefix.'post_customer_id'] = $post_customer_id;
		$post_data['meta_input'][$this->meta_prefix.'payment_due_date'] = $payment_due_date;
		$post_data['meta_input'][$this->meta_prefix.'payment_mode_of_payment'] = $payment_mode_of_payment;
		$post_data['meta_input'][$this->meta_prefix.'payment_mode_of_payment_account'] = $payment_mode_of_payment_account;
		$post_data['meta_input'][$this->meta_prefix.'payment_voucher_number'] = $payment_voucher_number;
		$post_data['meta_input'][$this->meta_prefix.'payment_voucher_series'] = $payment_voucher_series;
		$post_data['meta_input'][$this->meta_prefix.'payment_voucher_year'] = $payment_voucher_year;

		return $post_data;
	}

	function get_voucher_info($arr_voucher, $post_data)
	{
		$voucher_account = $voucher_amount = $voucher_excerpt = $voucher_content = "";

		if(isset($arr_voucher['@url']) && $arr_voucher['@url'] != '')
		{
			$arr_voucher_info = $this->fetch_from_api(['endpoint' => 'voucher', 'url' => $arr_voucher['@url'], 'action' => 'insert']);

			//do_log(__FUNCTION__.": ".var_export($arr_voucher_info, true), 'publish', false);

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
				),
				'VoucherSeries' => 'A',
				'Year' => 1,
				'ApprovalState' => 0,
			)*/

			if(isset($arr_voucher_info['array']))
			{
				$voucher_account = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Account'];
				$voucher_amount = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Debit'];
				$voucher_excerpt = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['Description'];
				$voucher_content = $arr_voucher_info['array']['Voucher']['VoucherRows'][0]['TransactionInformation'];
			}
		}

		$post_data['post_excerpt'] = $voucher_excerpt;
		$post_data['post_content'] = $voucher_content;
		$post_data['meta_input'][$this->meta_prefix.'voucher_account'] = $voucher_account;
		$post_data['meta_input'][$this->meta_prefix.'voucher_amount'] = $voucher_amount;

		return $post_data;
	}

	function execute_or_delay_request()
	{
		if($this->api_rate_count_requests >= $this->api_rate_limit_requests)
		{
			sleep($this->api_rate_limit_seconds);
			$this->api_rate_count_requests = 0;
		}

		$this->api_rate_count_requests++;
	}

	function fetch_from_api($data)
	{
		global $wpdb, $obj_base;

		if(!isset($data['page'])){		$data['page'] = 1;}
		if(!isset($data['endpoint'])){	$data['endpoint'] = get_option('setting_fortnox_endpoint', 'products');}

		$result = array(
			'success' => false,
			'html' => "",
		);

		$obj_encryption = new mf_encryption(__CLASS__);

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
				case 'generate_access_token_from_tenant_id':
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

						case 'generate_access_token_from_tenant_id':
							$option_fortnox_database_number = get_option('option_fortnox_database_number');

							$arr_headers[] = 'TenantId: '.$option_fortnox_database_number;

							$post_data = http_build_query([
								'grant_type' => 'client_credentials',
								//'scope' => 'companyinformation',
							]);
						break;
					}

					$this->execute_or_delay_request();

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
								"refresh_token": "a7302e6b-b1cb-4508-b884-cf...",
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
							$arr_json = json_decode($content, true);

							// {"error":"invalid_client","error_description":"The client credentials are invalid"}

							if(isset($arr_json['error']) && $arr_json['error'] == "invalid_client")
							{
								switch($data['endpoint'])
								{
									case 'generate_access_token':
										delete_option('setting_fortnox_authorization_code');
									break;

									case 'generate_access_token_from_refresh':
										delete_option('setting_fortnox_refresh_token');
									break;
								}
							}

							$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." + ".var_export($post_data, true)." => ".$headers['http_code']." => ".$content.")";

							switch($data['action'])
							{
								case 'insert':
									do_log($log_message, 'publish', false);
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

					$this->execute_or_delay_request();

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
								'CompanyInformation' => array (
									'Address' => '[text]',
									'City' => '[city]',
									'CountryCode' => 'SE',
									'DatabaseNumber' => [number],
									'CompanyName' => '[text]',
									'OrganizationNumber' => 'XXXXXX-XXXX',
									'VisitAddress' => NULL,
									'VisitCity' => NULL,
									'VisitCountryCode' => NULL,
									'VisitZipCode' => NULL,
									'ZipCode' => '[number]',
								),
							)*/

							if(isset($arr_json['CompanyInformation']['DatabaseNumber']) && $arr_json['CompanyInformation']['DatabaseNumber'] != '')
							{
								update_option('option_fortnox_database_number', $arr_json['CompanyInformation']['DatabaseNumber'], false);
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
									do_log($log_message, 'publish', false);
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
					$url = "https://api.fortnox.se/3/invoices/".$data['page'];

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					$this->execute_or_delay_request();

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

									if(isset($arr_json['Invoices']))
									{
										foreach($arr_json['Invoices'] as $arr_invoice)
										{
											$invoice_id = $arr_invoice['DocumentNumber'];
											$invoice_name = "#".$arr_invoice['DocumentNumber'];
											$invoice_balance = $arr_invoice['Balance'];
											$invoice_booked = $arr_invoice['Booked'];
											$invoice_cancelled = $arr_invoice['Cancelled'];
											$invoice_currency = $arr_invoice['Currency'];
											$invoice_customer_name = $arr_invoice['CustomerName'];
											$invoice_customer_number = $arr_invoice['CustomerNumber'];
											$invoice_due_date = date("Y-m-d H:i:s", strtotime($arr_invoice['DueDate']." 00:00:00"));
											$invoice_created = date("Y-m-d H:i:s", strtotime($arr_invoice['InvoiceDate']." 00:00:00"));
											$invoice_invoice_type = $arr_invoice['InvoiceType'];
											$invoice_voucher_number = $arr_invoice['VoucherNumber'];
											$invoice_voucher_series = $arr_invoice['VoucherSeries'];
											$invoice_voucher_year = $arr_invoice['VoucherYear'];
											$invoice_way_of_delivery = $arr_invoice['WayOfDelivery'];
											$invoice_terms_of_payment = $arr_invoice['TermsOfPayment'];
											$invoice_project = $arr_invoice['Project'];
											$invoice_sent = $arr_invoice['Sent'];
											$invoice_total = $arr_invoice['Total'];
											$invoice_final_pay_date = date("Y-m-d H:i:s", strtotime($arr_invoice['FinalPayDate']." 00:00:00"));

											$post_customer_id = $this->save_customer(array('id' => $invoice_customer_number, 'name' => $invoice_customer_name));

											$post_data = array(
												'post_type' => $this->post_type_invoices,
												'post_status' => 'publish',
												'post_title' => $invoice_name,
												'meta_input' => array(
													$this->meta_prefix.'invoice_id' => $invoice_id,
													$this->meta_prefix.'post_customer_id' => $post_customer_id,
													$this->meta_prefix.'invoice_balance' => $invoice_balance,
													$this->meta_prefix.'invoice_booked' => $invoice_booked,
													$this->meta_prefix.'invoice_cancelled' => $invoice_cancelled,
													$this->meta_prefix.'invoice_currency' => $invoice_currency,
													$this->meta_prefix.'invoice_due_date' => $invoice_due_date,
													$this->meta_prefix.'invoice_invoice_type' => $invoice_invoice_type,
													$this->meta_prefix.'invoice_voucher_number' => $invoice_voucher_number,
													$this->meta_prefix.'invoice_voucher_series' => $invoice_voucher_series,
													$this->meta_prefix.'invoice_voucher_year' => $invoice_voucher_year,
													$this->meta_prefix.'invoice_way_of_delivery' => $invoice_way_of_delivery,
													$this->meta_prefix.'invoice_terms_of_payment' => $invoice_terms_of_payment,
													$this->meta_prefix.'invoice_project' => $invoice_project,
													$this->meta_prefix.'invoice_sent' => $invoice_sent,
													$this->meta_prefix.'invoice_total' => $invoice_total,
													$this->meta_prefix.'invoice_final_pay_date' => $invoice_final_pay_date,
												),
											);

											$result = $obj_base->get_results($wpdb->prepare("SELECT ID, post_date, post_modified FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND meta_value = %s", $this->meta_prefix.'invoice_id', $this->post_type_invoices, $invoice_id));
											$invoice_amount = count($result);

											if($invoice_amount > 0)
											{
												$i = 0;

												foreach($result as $r)
												{
													$post_invoice_id = $r->ID;
													$post_invoice_date = $r->post_date;
													$post_invoice_modified = $r->post_modified;

													if($i == 0)
													{
														if($invoice_created != $post_invoice_date)
														{
															//do_log(__FUNCTION__." - Created: ".$post_invoice_date." -> ".$invoice_created, 'publish', false);

															$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $invoice_created, $post_invoice_id));
														}

														if($post_invoice_modified < date("Y-m-d H:i:s", strtotime(current_time('mysql')." -4 hour")))
														{
															//$post_data = $this->get_invoice_info($arr_invoice, $post_data);

															$post_data['ID'] = $post_invoice_id;
															$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input'], $post_data['ID']);

															wp_update_post($post_data);
														}
													}

													else
													{
														wp_trash_post($post_invoice_id);
													}

													$i++;
												}
											}

											else
											{
												//$post_data = $this->get_invoice_info($arr_invoice, $post_data);

												$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input']);

												$post_invoice_id = wp_insert_post($post_data);

												$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $invoice_created, $post_invoice_id));
											}
										}

										$total_pages = $arr_json['MetaInformation']['@TotalPages'];
										$current_page = $arr_json['MetaInformation']['@CurrentPage'];

										if($total_pages > $current_page)
										{
											$data['page'] = ($current_page + 1);

											$this->fetch_from_api($data);
										}
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
									do_log($log_message, 'publish', false);
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
					$url = "https://api.fortnox.se/3/invoicepayments/".$data['page'];

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					$this->execute_or_delay_request();

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
									//do_log("Payments: ".var_export($arr_json, true), 'publish', false);

									/*array (
										'MetaInformation' => array (
											'@TotalResources' => 715,
											'@TotalPages' => 8,
											'@CurrentPage' => 1,
										),
										'InvoicePayments' => array (
											0 => array (
												'@url' => 'https://api.fortnox.se/3/invoicepayments/1',
												'Amount' => [number],
												'Booked' => true,
												'Currency' => 'SEK',
												'CurrencyRate' => 1,
												'CurrencyUnit' => 1,
												'InvoiceNumber' => 1,
												'Number' => '1',
												'PaymentDate' => 'YYYY-MM-DD',
												'Source' => 'manual',
												'WriteOffExist' => false,
											),
										),
									)*/

									if(isset($arr_json['InvoicePayments']))
									{
										foreach($arr_json['InvoicePayments'] as $arr_payment)
										{
											$payment_id = $arr_payment['Number'];
											$payment_name = $arr_payment['Number'];
											$payment_amount = $arr_payment['Amount'];
											$payment_booked = $arr_payment['Booked'];
											$payment_currency = $arr_payment['Currency'];
											$payment_source = $arr_payment['Source'];
											$payment_write_off_exists = $arr_payment['WriteOffExist'];
											$payment_created = date("Y-m-d H:i:s", strtotime($arr_payment['PaymentDate']." 00:00:00"));

											$post_data = array(
												'post_type' => $this->post_type_payments,
												'post_status' => 'publish',
												'post_title' => $payment_name,
												'meta_input' => array(
													$this->meta_prefix.'payment_id' => $payment_id,
													$this->meta_prefix.'payment_amount' => $payment_amount,
													$this->meta_prefix.'payment_booked' => $payment_booked,
													$this->meta_prefix.'payment_currency' => $payment_currency,
													$this->meta_prefix.'payment_source' => $payment_source,
													$this->meta_prefix.'payment_write_off_exists' => $payment_write_off_exists,
												),
											);

											$result = $obj_base->get_results($wpdb->prepare("SELECT ID, post_date, post_modified FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND meta_value = %s", $this->meta_prefix.'payment_id', $this->post_type_payments, $payment_id));
											$payment_amount = count($result);

											if($payment_amount > 0)
											{
												$i = 0;

												foreach($result as $r)
												{
													$post_payment_id = $r->ID;
													$post_payment_date = $r->post_date;
													$post_payment_modified = $r->post_modified;

													if($i == 0)
													{
														if($payment_created != $post_payment_date)
														{
															$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $payment_created, $post_payment_id));
														}

														if($post_payment_modified < date("Y-m-d H:i:s", strtotime(current_time('mysql')." -4 hour")))
														{
															$post_data = $this->get_payment_info($arr_payment, $post_data);

															$post_data['ID'] = $post_payment_id;
															$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input'], $post_data['ID']);

															wp_update_post($post_data);
														}
													}

													else
													{
														wp_trash_post($post_payment_id);
													}

													$i++;
												}
											}

											else
											{
												$post_data = $this->get_payment_info($arr_payment, $post_data);

												$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input']);

												$post_payment_id = wp_insert_post($post_data);

												$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $payment_created, $post_payment_id));
											}
										}

										$total_pages = $arr_json['MetaInformation']['@TotalPages'];
										$current_page = $arr_json['MetaInformation']['@CurrentPage'];

										if($total_pages > $current_page)
										{
											$data['page'] = ($current_page + 1);

											$this->fetch_from_api($data);
										}
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
									do_log($log_message, 'publish', false);
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
					$setting_fortnox_vouchers_series = get_option('setting_fortnox_vouchers_series');

					if($setting_fortnox_vouchers_series != '')
					{
						$url = "https://api.fortnox.se/3/vouchers/?voucherseries=".$setting_fortnox_vouchers_series."&page=".$data['page']; //&accountnumber=1901&financialyear=1

						$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

						if($setting_fortnox_access_token != '')
						{
							$arr_headers = [
								'Content-Type: application/json',
								'Authorization: Bearer '.$setting_fortnox_access_token,
							];

							$this->execute_or_delay_request();

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

												//$arr_voucher_tags = $arr_voucher['tags'];

												$post_data = array(
													'post_type' => $this->post_type_vouchers,
													'post_status' => 'publish',
													'post_title' => $voucher_name,
													//'post_excerpt' => $voucher_excerpt,
													//'post_content' => $voucher_content,
													'meta_input' => array(
														$this->meta_prefix.'voucher_id' => $voucher_id,
														//$this->meta_prefix.'voucher_account' => $voucher_account,
														//$this->meta_prefix.'voucher_amount' => $voucher_amount,
														$this->meta_prefix.'voucher_reference_no' => $voucher_reference_no,
														$this->meta_prefix.'voucher_reference_type' => $voucher_reference_type,
														$this->meta_prefix.'voucher_number' => $voucher_number,
														$this->meta_prefix.'voucher_series' => $voucher_series,
														$this->meta_prefix.'voucher_year' => $voucher_year,
														$this->meta_prefix.'voucher_approval_state' => $voucher_approval_state,
													),
												);

												$result = $obj_base->get_results($wpdb->prepare("SELECT ID, post_date, post_modified FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND meta_value = %s", $this->meta_prefix.'voucher_id', $this->post_type_vouchers, $voucher_id));
												$voucher_amount = count($result);

												if($voucher_amount > 0)
												{
													$i = 0;

													foreach($result as $r)
													{
														$post_voucher_id = $r->ID;
														$post_voucher_date = $r->post_date;
														$post_voucher_modified = $r->post_modified;

														if($i == 0)
														{
															if($voucher_created != $post_voucher_date)
															{
																//do_log(__FUNCTION__." - Created: ".$post_voucher_date." -> ".$voucher_created, 'publish', false);

																$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $voucher_created, $post_voucher_id));
															}

															if($post_voucher_modified < date("Y-m-d H:i:s", strtotime(current_time('mysql')." -4 hour")))
															{
																$post_data = $this->get_voucher_info($arr_voucher, $post_data);

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
													$post_data = $this->get_voucher_info($arr_voucher, $post_data);

													$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input']);

													$post_voucher_id = wp_insert_post($post_data);

													$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_date = %s WHERE ID = '%d'", $voucher_created, $post_voucher_id));
												}

												/*if($post_voucher_id > 0)
												{
													wp_set_post_terms($post_voucher_id, $arr_voucher_tags, $this->taxonomy_tags, false);
												}*/
											}

											$total_pages = $arr_json['MetaInformation']['@TotalPages'];
											$current_page = $arr_json['MetaInformation']['@CurrentPage'];

											if($total_pages > $current_page)
											{
												$data['page'] = ($current_page + 1);

												$this->fetch_from_api($data);
											}
										break;

										case 'print':
											$result['success'] = true;
											$result['html'] .= "HTTP Status: ".$headers['http_code']." -> ".var_export($arr_json, true);
										break;
									}
								break;

								default:
									$arr_json = json_decode($content, true);

									// {"message":"unauthorized"}

									if(isset($arr_json['message']) && $arr_json['message'] == "unauthorized")
									{
										delete_option('setting_fortnox_access_token');
									}

									$log_message = __FUNCTION__.": Did you use the right endpoint and Auth Token? (".$url." + ".var_export($arr_headers, true)." => ".$headers['http_code']." + ".$content.")";

									switch($data['action'])
									{
										case 'insert':
											switch($http_status)
											{
												case 429:
													
												break;

												default:
													do_log($log_message, 'publish', false);
												break;
											}
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
						}
					}
				break;

				case 'invoice':
				case 'payment':
				case 'voucher':
					$url = $data['url'];

					$setting_fortnox_access_token = get_option('setting_fortnox_access_token');

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: Bearer '.$setting_fortnox_access_token,
					];

					$this->execute_or_delay_request();

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
									do_log($log_message, 'publish', false);
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
			replace_option(array('old' => 'setting_fortnox_endpoint_to_fetch', 'new' => 'setting_fortnox_endpoints_to_fetch'));

			if(get_option('setting_fortnox_refresh_token') != '')
			{
				$this->fetch_from_api(['endpoint' => 'generate_access_token_from_refresh', 'action' => 'insert']);
			}

			else if(get_option('setting_fortnox_authorization_code') != '')
			{
				$this->fetch_from_api(['endpoint' => 'generate_access_token', 'action' => 'insert']);
			}

			else if(get_option('option_fortnox_database_number') != '')
			{
				$this->fetch_from_api(['endpoint' => 'generate_access_token_from_tenant_id', 'action' => 'insert']);
			}

			$setting_fortnox_endpoints_to_fetch = get_option('setting_fortnox_endpoints_to_fetch', $this->default_endpoint_to_fetch);

			if(is_array($setting_fortnox_endpoints_to_fetch))
			{
				foreach($setting_fortnox_endpoints_to_fetch as $endpoint)
				{
					$this->fetch_from_api(['endpoint' => $endpoint, 'action' => 'insert']);
				}

				if(!in_array('invoices', $setting_fortnox_endpoints_to_fetch))
				{
					mf_uninstall_plugin(array(
						'post_types' => array($this->post_type_invoices),
					));
				}

				if(!in_array('payments', $setting_fortnox_endpoints_to_fetch))
				{
					mf_uninstall_plugin(array(
						'post_types' => array($this->post_type_payments),
					));
				}

				if(!in_array('vouchers', $setting_fortnox_endpoints_to_fetch))
				{
					mf_uninstall_plugin(array(
						'post_types' => array($this->post_type_vouchers),
					));
				}
			}
		}

		$obj_cron->end();
	}

	function init()
	{
		load_plugin_textdomain('lang_fortnox', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		register_post_type($this->post_type_customer, array(
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
			'show_ui' => current_user_can('manage_options'),
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		$setting_fortnox_endpoints_to_fetch = get_option('setting_fortnox_endpoints_to_fetch', $this->default_endpoint_to_fetch);

		if(is_array($setting_fortnox_endpoints_to_fetch))
		{
			if(in_array('invoices', $setting_fortnox_endpoints_to_fetch))
			{
				register_post_type($this->post_type_invoices, array(
					'labels' => array(
						'name' => __("Invoices", 'lang_fortnox'),
						'singular_name' => __("Invoice", 'lang_fortnox'),
						'menu_name' => __("Invoices", 'lang_fortnox'),
						'all_items' => __("List", 'lang_fortnox'),
						'edit_item' => __("Edit", 'lang_fortnox'),
						'view_item' => __("View", 'lang_fortnox'),
						'add_new_item' => __("Add New", 'lang_fortnox'),
					),
					'public' => false,
					'show_ui' => current_user_can('manage_options'),
					'show_in_menu' => false,
					'show_in_nav_menus' => false,
					'show_in_rest' => true,
					'supports' => array('title', 'editor'),
					'hierarchical' => true,
					'has_archive' => false,
				));
			}

			if(in_array('payments', $setting_fortnox_endpoints_to_fetch))
			{
				register_post_type($this->post_type_payments, array(
					'labels' => array(
						'name' => __("Payments", 'lang_fortnox'),
						'singular_name' => __("Payment", 'lang_fortnox'),
						'menu_name' => __("Payments", 'lang_fortnox'),
						'all_items' => __("List", 'lang_fortnox'),
						'edit_item' => __("Edit", 'lang_fortnox'),
						'view_item' => __("View", 'lang_fortnox'),
						'add_new_item' => __("Add New", 'lang_fortnox'),
					),
					'public' => false,
					'show_ui' => current_user_can('manage_options'),
					'show_in_menu' => false,
					'show_in_nav_menus' => false,
					'show_in_rest' => true,
					'supports' => array('title', 'editor'),
					'hierarchical' => true,
					'has_archive' => false,
				));
			}

			if(in_array('vouchers', $setting_fortnox_endpoints_to_fetch))
			{
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
					'show_ui' => current_user_can('manage_options'),
					'show_in_menu' => false,
					'show_in_nav_menus' => false,
					'show_in_rest' => true,
					'supports' => array('title', 'editor'),
					'hierarchical' => true,
					'has_archive' => false,
				));
			}
		}
	}

	function settings_fortnox()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_fortnox_client_id'] = __("Client ID", 'lang_fortnox');
		$arr_settings['setting_fortnox_client_secret'] = __("Client Secret", 'lang_fortnox');

		if(get_option('setting_fortnox_client_id') != '' && get_option('setting_fortnox_client_secret') != '')
		{
			$arr_settings['setting_fortnox_scope'] = __("Scope", 'lang_fortnox');

			//$arr_settings['setting_fortnox_authorization_code'] = __("Authorization Code", 'lang_fortnox');
			//$arr_settings['setting_fortnox_access_token'] = __("Access Token", 'lang_fortnox');
			//$arr_settings['setting_fortnox_refresh_token'] = __("Refresh Token", 'lang_fortnox');

			if(get_option('setting_fortnox_authorization_code') != '' || get_option('setting_fortnox_refresh_token') != '' || get_option('setting_fortnox_access_token') != '' || get_option('option_fortnox_database_number') != '')
			{
				if(get_option('setting_fortnox_access_token') != '')
				{
					$arr_settings['setting_fortnox_endpoints_to_fetch'] = __("Endpoint to fetch", 'lang_fortnox');

					$setting_fortnox_endpoints_to_fetch = get_option('setting_fortnox_endpoints_to_fetch');

					if(is_array($setting_fortnox_endpoints_to_fetch) && in_array('vouchers', $setting_fortnox_endpoints_to_fetch))
					{
						$arr_settings['setting_fortnox_vouchers_series'] = __("Voucher Series", 'lang_fortnox');
					}
				}

				$arr_settings['setting_fortnox_endpoint'] = __("Endpoint to test", 'lang_fortnox');
				$arr_settings['setting_fortnox_debug'] = __("Debug", 'lang_fortnox');
			}
		}

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

		if($option == '')
		{
			echo "<h3>".__("Step 1: Get access to the account", 'lang_fortnox')."</h3>
			<ol>
				<li>".sprintf(__("Send your social security number and e-mail to the client for them to notice %s, so that you can log in with %s.", 'lang_fortnox'), "Fortnox", "BankID")."</li>
				<!--<li>".__("Fill in your name, email address, and create a password.", 'lang_fortnox')."</li>
				<li>".__("Confirm your email address by clicking on the link sent to your inbox.", 'lang_fortnox')."</li>-->
			</ol>
			<h3>".__("Step 2: Log in to your Account", 'lang_fortnox')."</h3>
			<ol>
				<li>".sprintf(__("Go to %sFortnox's website%s.", 'lang_fortnox'), "<a href='//apps2.fortnox.se/app/'>", "</a>")."</li>
				<li>".__("Navigate to Developer Portal in the sidebar.", 'lang_fortnox')."</li>
				<li>".__("Press on Create Integration and give it a name.", 'lang_fortnox')."</li>
				<li>".__("Copy Client ID and Secret and enter into theses fields.", 'lang_fortnox')."</li>
			</ol>";
		}
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

		if($option == '' || count($option) == 0)
		{
			echo "<h3>".__("Step 3: Set additional parameters", 'lang_fortnox')."</h3>
			<ol>
				<li>".sprintf(__("Enter %s into the Redirect URI field.", 'lang_fortnox'), "<code>".get_site_url()."/wp-content/plugins/mf_fortnox/include/api/callback.php</code>")."</li>
				<li>".__("Check all the rights that you need access to.", 'lang_fortnox')."</li>
				<li>".__("Press Save.", 'lang_fortnox')."</li>
				<li>".__("Choose the Scope you want above and save the settings on this page.", 'lang_fortnox')."</li>
			</ol>";
		}

		else
		{
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
	}

	function setting_fortnox_authorization_code_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_fortnox_access_token_callback()
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

	function setting_fortnox_endpoints_to_fetch_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, $this->default_endpoint_to_fetch);

		$arr_data = [];
		$arr_data['invoices'] = __("Invoices", 'lang_fortnox');
		$arr_data['payments'] = __("Payments", 'lang_fortnox');
		$arr_data['vouchers'] = __("Vouchers", 'lang_fortnox');

		echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option, 'allow_hidden_field' => false));
	}

	function setting_fortnox_vouchers_series_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = [];
		$arr_data[''] = "-- ".__("Choose Here", 'lang_fortnox')." --";
		$arr_data['a'] = __("A", 'lang_fortnox');
		$arr_data['s'] = __("S", 'lang_fortnox');

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'allow_hidden_field' => false));
	}

	function setting_fortnox_endpoint_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = [];

		if(get_option('setting_fortnox_authorization_code') != '')
		{
			$arr_data['generate_access_token'] = __("Generate Access Token from Access Code", 'lang_fortnox');
		}

		if(get_option('setting_fortnox_refresh_token') != '')
		{
			$arr_data['generate_access_token_from_refresh'] = __("Generate Access Token from Refresh Token", 'lang_fortnox');
		}

		if(get_option('option_fortnox_database_number') != '')
		{
			$arr_data['generate_access_token_from_tenant_id'] = __("Generate Access Token from Tenant ID", 'lang_fortnox');
		}

		if(get_option('setting_fortnox_access_token') != '')
		{
			$arr_data['companyinformation'] = __("Company Information", 'lang_fortnox');
			$arr_data['invoices'] = __("Invoices", 'lang_fortnox');
			$arr_data['payments'] = __("Payments", 'lang_fortnox');
			$arr_data['vouchers'] = __("Vouchers", 'lang_fortnox');
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
		$menu_start = "edit.php?post_type=".$this->post_type_customer;
		$menu_capability = 'manage_options';

		$menu_title = __("Fortnox", 'lang_fortnox');
		add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, '', 'dashicons-money-alt', 21);

		$menu_title = __("Customers", 'lang_fortnox');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$setting_fortnox_endpoints_to_fetch = get_option('setting_fortnox_endpoints_to_fetch', $this->default_endpoint_to_fetch);

		if(is_array($setting_fortnox_endpoints_to_fetch))
		{
			if(in_array('invoices', $setting_fortnox_endpoints_to_fetch))
			{
				$menu_title = __("Invoices", 'lang_fortnox');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_invoices);
			}

			if(in_array('payments', $setting_fortnox_endpoints_to_fetch))
			{
				$menu_title = __("Payments", 'lang_fortnox');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_payments);
			}

			if(in_array('vouchers', $setting_fortnox_endpoints_to_fetch))
			{
				$menu_title = __("Vouchers", 'lang_fortnox');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_vouchers);
			}
		}

		if(IS_ADMINISTRATOR)
		{
			$menu_title = __("Settings", 'lang_fortnox');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_fortnox"));
		}
	}

	function posts_orderby($orderby_statement, $wp_query)
	{
		global $wpdb;

		if($wp_query->is_main_query() && check_var('orderby') == '')
		{
			switch($wp_query->get('post_type'))
			{
				case $this->post_type_invoices:
				case $this->post_type_payments:
				case $this->post_type_vouchers:
					$orderby_statement = $wpdb->posts.".post_status ASC, ".$wpdb->posts.".post_date DESC";
				break;
			}
		}

		return $orderby_statement;
	}

	function column_header($columns)
	{
		global $post_type;

		//do_action('load_font_awesome');

		unset($columns['date']);

		switch($post_type)
		{
			case $this->post_type_customer:
				$columns['customer_id'] = __("ID", 'lang_fortnox');
				$columns['payments'] = __("Payments", 'lang_fortnox');
				$columns['invoices'] = __("Invoices", 'lang_fortnox');
				//$columns['vouchers'] = __("Vouchers", 'lang_fortnox');
			break;

			case $this->post_type_invoices:
				//$columns['invoice_id'] = __("ID", 'lang_fortnox');
				$columns['post_customer_id'] = __("Customer", 'lang_fortnox');
				$columns['post_date'] = __("Date", 'lang_fortnox');
			break;

			case $this->post_type_payments:
				//$columns['payment_id'] = __("ID", 'lang_fortnox');
				$columns['post_customer_id'] = __("Customer", 'lang_fortnox');
				$columns['payment_amount'] = __("Amount", 'lang_fortnox');
				$columns['post_date'] = __("Date", 'lang_fortnox');
				//$columns['payment_due_date'] = __("Due Date", 'lang_fortnox');
				//$columns['payment_voucher_number'] = __("Number", 'lang_fortnox');
				$columns['payment_voucher_series'] = __("Series", 'lang_fortnox');
				//$columns['payment_voucher_year'] = __("Year", 'lang_fortnox');
			break;

			case $this->post_type_vouchers:
				//$columns['voucher_id'] = __("ID", 'lang_fortnox');
				$columns['voucher_excerpt'] = __("Description", 'lang_fortnox');
				$columns['voucher_content'] = __("Information", 'lang_fortnox');
				//$columns['voucher_account'] = __("Account", 'lang_fortnox');
				$columns['voucher_amount'] = __("Amount", 'lang_fortnox');
				//$columns['voucher_reference_no'] = __("Reference No.", 'lang_fortnox');
				//$columns['voucher_reference_type'] = __("Reference Type", 'lang_fortnox');
				//$columns['voucher_number'] = __("Number", 'lang_fortnox');
				$columns['voucher_series'] = __("Series", 'lang_fortnox');
				//$columns['voucher_year'] = __("Year", 'lang_fortnox');
				//$columns['voucher_approval_state'] = __("State", 'lang_fortnox');
				$columns['post_date'] = __("Date", 'lang_fortnox');
			break;
		}

		return $columns;
	}

	function column_cell($column, $post_id)
	{
		global $post, $wpdb;

		switch($post->post_type)
		{
			case $this->post_type_customer:
				switch($column)
				{
					case 'payments':
					case 'invoices':
					//case 'vouchers':
						$post_type_temp = $this->post_type.'_'.$column;

						$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND meta_value = %s", $this->meta_prefix.'post_customer_id', $post_type_temp, $post_id));

						if($wpdb->num_rows > 0)
						{
							echo $wpdb->num_rows;
						}

						/*if(IS_SUPER_ADMIN && $wpdb->num_rows == 0)
						{
							echo " (".$wpdb->last_query.")";
						}*/
					break;

					default:
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;
				}
			break;

			case $this->post_type_invoices:
				switch($column)
				{
					case 'post_customer_id':
						$customer_id = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($customer_id > 0)
						{
							echo get_the_title($customer_id);

							/*if(IS_SUPER_ADMIN)
							{
								echo " (#".$customer_id.")";
							}*/
						}
					break;

					case 'post_date':
						$post_date = get_post_field('post_date', $post_id);

						if($post_date > DEFAULT_DATE)
						{
							echo format_date($post_date);
						}
					break;

					default:
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;
				}
			break;

			case $this->post_type_payments:
				switch($column)
				{
					case 'post_customer_id':
						$customer_id = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($customer_id > 0)
						{
							echo get_the_title($customer_id);

							/*if(IS_SUPER_ADMIN)
							{
								echo " (#".$customer_id.")";
							}*/
						}
					break;

					case 'payment_amount':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							$payment_currency = get_post_meta($post_id, $this->meta_prefix.'payment_currency', true);

							echo $post_meta." ".$payment_currency;
						}
					break;

					case 'post_date':
						$post_date = get_post_field('post_date', $post_id);

						if($post_date > DEFAULT_DATE)
						{
							echo format_date($post_date);
						}
					break;

					case 'payment_due_date':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo format_date($post_meta);
						}
					break;

					default:
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo $post_meta;
						}
					break;
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

					case 'post_date':
						$post_date = get_post_field('post_date', $post_id);

						if($post_date > DEFAULT_DATE)
						{
							echo format_date($post_date);
						}
					break;

					default:
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

	function get_payment_status($payment_status, $data)
	{
		global $wpdb;

		if($data['payment_hash'] != '' && $data['payment_amount'] > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND post_status = %s AND (post_title LIKE %s OR post_content LIKE %s) AND meta_value = %d LIMIT 0, 1", $this->meta_prefix.'voucher_amount', $this->post_type_vouchers, 'publish', "%".$data['payment_hash']."%", $data['payment_hash']."%", $data['payment_amount']));
			$num_rows = $wpdb->num_rows;

			if($num_rows > 1)
			{
				do_log(__FUNCTION__.": ".$num_rows." rows (".$wpdb->last_query.")");
			}

			else if($num_rows > 0)
			{
				$payment_status = 'paid';
			}

			else
			{
				//do_log(__FUNCTION__.": No rows (".$wpdb->last_query.")");
			}
		}

		return $payment_status;
	}

	function api_fortnox_run()
	{
		$result = $this->fetch_from_api(array('action' => 'print'));

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}
}