<?php

class ControllerExtensionPaymentBlockBee extends Controller
{
    public function index()
    {
        require_once(DIR_SYSTEM . 'library/blockbee.php');

        $this->load->language('extension/payment/blockbee');

        $this->load->model('extension/payment/blockbee');

        $data['title'] = $this->config->get('payment_blockbee_title');

        $data['cryptocurrencies'] = array();

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $order_total = floatval($order['total']);

        $apiKey = $this->config->get('payment_blockbee_api_key');

        foreach ($this->config->get('payment_blockbee_cryptocurrencies') as $selected) {
            foreach (json_decode(str_replace("&quot;", '"', $this->config->get('payment_blockbee_cryptocurrencies_array_cache')), true) as $token => $coin) {
                if ($selected === $token) {
                    $data['cryptocurrencies'] += [
                        $token => $coin,
                    ];
                }
            }
        }

        // Fee
        $fee = $this->config->get('payment_blockbee_fees');
        $blockchain_fee = $this->config->get('payment_blockbee_blockchain_fees');
        $currency = $order['currency_code'];
        $currencySymbolLeft = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_left'];
        $currencySymbolRight = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_right'];
        $data['symbol_left'] = $currencySymbolLeft;
        $data['symbol_right'] = $currencySymbolRight;

        $blockbeeFee = 0;

        if ($_POST) {
            if ($fee != 0) {
                $blockbeeFee += floatval($fee) * $order_total;
            }

            if ($blockchain_fee) {
                $blockbeeFee += floatval(BlockBeeHelper::get_estimate($_POST["blockbee_coin"])->$currency);
            }
        }

        $data['fee'] = $fee;
        $data['blockchain_fee'] = $blockchain_fee;
        $data['blockbee_fee'] = $this->currency->format($blockbeeFee, $currency, 1.00000, false);
        $data['total'] = $this->currency->format($order_total + $blockbeeFee, $currency, 1.00000, false);

        $this->session->data['blockbee_fee'] = round($blockbeeFee, 2);

        $this->load->model('checkout/order');

        return $this->load->view('extension/payment/blockbee', $data);
    }

    public function confirm()
    {
        $this->load->language('extension/payment/blockbee');
        $json = array();
        $err_coin = '';

        if ($this->session->data['payment_method']['code'] == 'blockbee') {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/blockbee');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            $cryptoFee = empty($this->session->data['blockbee_fee']) ? 0 : $this->session->data['blockbee_fee'];
            $total = $this->currency->format($order_info['total'] + $cryptoFee, $order_info['currency_code'], 1.00000, false);
            $currency = $this->session->data['currency'];

            if (empty($this->request->post['blockbee_coin'])) {
                $err_coin = $this->language->get('error_coin');
            } else {
                $selected = $this->request->post['blockbee_coin'];
                $apiKey = $this->config->get('payment_blockbee_api_key');
                if (empty($apiKey)) {
                    $err_coin = $this->language->get('error_apikey');
                }
            }

            if (empty($err_coin) && !empty($apiKey)) {

                $nonce = $this->model_extension_payment_blockbee->generateNonce();

                require_once(DIR_SYSTEM . 'library/blockbee.php');

                $disable_conversion = $this->config->get('payment_blockbee_disable_conversion');
                $qr_code_size = $this->config->get('payment_blockbee_qrcode_size');

                $info = BlockBeeHelper::get_info($selected, false, $apiKey);
                $minTx = floatval($info->minimum_transaction_coin);

                $cryptoTotal = BlockBeeHelper::get_conversion($order_info['currency_code'], $selected, $total, $disable_conversion, $apiKey);
                $callbackUrl = $this->url->link('extension/payment/blockbee/callback', 'order_id=' . $this->session->data['order_id'] . '&nonce=' . $nonce, true);
                $callbackUrl = str_replace('&amp;', '&', $callbackUrl);

                $helper = new BlockBeeHelper($selected, $apiKey, $callbackUrl, [], true);
                $addressIn = $helper->get_address();

                if (!isset($addressIn)) {
                    $err_coin = $this->language->get('error_adress');
                } else {
                    if (($cryptoTotal < $minTx)) {
                        $err_coin = $this->language->get('value_minim') . ' ' . $minTx . ' ' . strtoupper($selected);
                    }
                }

                if (empty($err_coin)) {


                    $qrCodeDataValue = $helper->get_qrcode($cryptoTotal, $qr_code_size);
                    $qrCodeData = $helper->get_qrcode('', $qr_code_size);
                    $paymentURL = $this->url->link('extension/payment/blockbee/pay', 'order_id=' . $this->session->data['order_id'] . 'nonce=' . $nonce, true);
                    $paymentData = [
                        'blockbee_fee' => $cryptoFee,
                        'blockbee_nonce' => $nonce,
                        'blockbee_address' => $addressIn,
                        'blockbee_total' => $cryptoTotal,
                        'blockbee_total_fiat' => $total,
                        'blockbee_currency' => $selected,
                        'blockbee_qrcode_value' => $qrCodeDataValue['qr_code'],
                        'blockbee_qrcode' => $qrCodeData['qr_code'],
                        'blockbee_last_price_update' => time(),
                        'blockbee_order_timestamp' => time(),
                        'blockbee_cancelled' => '0',
                        'blockbee_min' => $minTx,
                        'blockbee_history' => json_encode([]),
                        'blockbee_payment_url' => $paymentURL
                    ];
                    $paymentData = json_encode($paymentData);
                    $this->model_extension_payment_blockbee->addPaymentData($this->session->data['order_id'], $paymentData);

                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_blockbee_order_status_id'));
                    $json['redirect'] = $this->url->link('checkout/success', 'order_id=' . $this->session->data['order_id'] . 'nonce=' . $nonce, true);
                } else {
                    $json['error']['warning'] = sprintf($this->language->get('error_payment'), $err_coin);
                }
            } else {
                $json['error']['warning'] = sprintf($this->language->get('error_payment'), $err_coin);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function isBlockBeeOrder($status = false)
    {
        $order = false;
        if (isset($this->request->get['order_id'])) {
            $order_id = (int)($this->request->get['order_id']);
        } else if (isset($this->request->get['amp;order_id'])) {
            $order_id = (int)($this->request->get['amp;order_id']);
        }

        if (isset($order_id)) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);

            $this->load->model('setting/setting');
            $setting = $this->model_setting_setting;

            if ($order && $order['payment_code'] != 'blockbee') {
                $order = false;
            }

            if (!$status && $order && $order['order_status_id'] != $setting->getSettingValue('payment_blockbee_order_status_id')) {
                $order = false;
            }
        }
        return $order;
    }

    public function pay()
    {
        $this->document->addScript('catalog/view/javascript/blockbee/js/blockbee_script.js');
        $this->document->addStyle('catalog/view/javascript/blockbee/css/blockbee_style.css');

        // In case the extension is disabled, do nothing
        if (!$this->config->get('payment_blockbee_status')) {
            $this->response->redirect($this->url->link('common/home', '', true));
        }

        // Library
        require_once(DIR_SYSTEM . 'library/blockbee.php');

        $this->load->language('extension/payment/blockbee');

        $order = $this->isBlockBeeOrder();

        if (!$order) {
            $this->response->redirect($this->url->link('common/home', '', true));
        }

        $this->load->model('extension/payment/blockbee');
        $this->load->model('localisation/currency');

        $metaData = $this->model_extension_payment_blockbee->getPaymentData($order['order_id']);

        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
        }

        $total = $metaData['blockbee_total_fiat'];
        $currencySymbolLeft = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_left'];
        $currencySymbolRight = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_right'];

        $ajaxUrl = $this->url->link('extension/payment/blockbee/status', 'order_id=' . $order['order_id'], true);
        $ajaxUrl = str_replace('&amp;', '&', $ajaxUrl);

        $allowed_to_value = array(
            'btc',
            'eth',
            'bch',
            'ltc',
            'miota',
            'xmr',
        );

        $cryptoCoin = $metaData['blockbee_currency'];

        $crypto_allowed_value = false;

        if (in_array($cryptoCoin, $allowed_to_value, true)) {
            $crypto_allowed_value = true;
        }

        $conversion_timer = ((int)$metaData['blockbee_last_price_update'] + (int)$this->config->get('payment_blockbee_refresh_values')) - time();
        $cancel_timer = (int)$metaData['blockbee_order_timestamp'] + (int)$this->config->get('payment_blockbee_order_cancelation_timeout') - time();

        $params = [
            'module_path' => HTTPS_SERVER . 'image/catalog/blockbee/',
            'header' => $this->load->controller('common/header'),
            'footer' => $this->load->controller('common/footer'),
            'currency_symbol_left' => $currencySymbolLeft,
            'currency_symbol_right' => $currencySymbolRight,
            'total' => floatval($total) < 0 ? 0 : floatval($total),
            'address_in' => $metaData['blockbee_address'],
            'crypto_coin' => $cryptoCoin,
            'crypto_value' => $metaData['blockbee_total'],
            'ajax_url' => $ajaxUrl,
            'qr_code_size' => $this->config->get('payment_blockbee_qrcode_size'),
            'qr_code' => $metaData['blockbee_qrcode'],
            'qr_code_value' => $metaData['blockbee_qrcode_value'],
            'show_branding' => $this->config->get('payment_blockbee_branding'),
            'branding_logo' => HTTPS_SERVER . 'image/catalog/blockbee/payment.png',
            'qr_code_setting' => $this->config->get('payment_blockbee_qrcode'),
            'order_timestamp' => $order['total'],
            'order_cancelation_timeout' => $this->config->get('payment_blockbee_order_cancelation_timeout'),
            'refresh_value_interval' => $this->config->get('payment_blockbee_refresh_values'),
            'last_price_update' => $metaData['blockbee_last_price_update'],
            'min_tx' => $metaData['blockbee_min'],
            'min_tx_notice' => (string)$metaData['blockbee_min'] . ' ' . strtoupper($cryptoCoin),
            'color_scheme' => $this->config->get('payment_blockbee_color_scheme'),
            'conversion_timer' => (int)$conversion_timer,
            'cancel_timer' => (int)$cancel_timer,
            'crypto_allowed_value' => $crypto_allowed_value,
        ];

        return $this->response->setOutput($this->load->view('extension/payment/blockbee_success', $params));
    }

    public function after_purchase(&$route, &$data, &$output)
    {
        // In case the extension is disabled, do nothing
        if (!$this->config->get('payment_blockbee_status')) {
            return;
        }

        $order = $this->isBlockBeeOrder();

        if (!$order) {
            return;
        }

        $this->load->model('extension/payment/blockbee');
        $metaData = $this->model_extension_payment_blockbee->getPaymentData($order['order_id']);

        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
        }

        $this->load->language('extension/payment/blockbee');

        $nonce = $metaData['blockbee_nonce'];

        /**
         * Tries sending an e-mail. Will fail if configuration is not set but won't throw an error.
         */
        try {
            // Send the E-mail with the order URL
            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $subject = sprintf($this->language->get('order_subject'), $order['order_id'], strtoupper($metaData['blockbee_currency']));

            $data['order_greeting'] = sprintf($this->language->get('order_greeting'), $order['order_id'], strtoupper($metaData['blockbee_currency']));
            $data['order_url'] = $metaData['blockbee_payment_url'];
            $data['store'] = html_entity_decode($order['store_name'], ENT_QUOTES, 'UTF-8');
            $data['store_url'] = $order['store_url'];

            $html = $this->load->view('extension/payment/blockbee_email', $data);

            $mail->setTo($order['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($order['store_name'], ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($html);
            $mail->send();
        } catch (\Exception $exception) {
            # don't do anything
        }

        return $this->response->redirect($this->url->link('extension/payment/blockbee/pay', 'order_id=' . $order['order_id'] . 'nonce=' . $nonce, true));
    }

    public function isOrderPaid($order)
    {
        $paid = 0;
        $successOrderStatuses = [2, 3, 15];
        if (in_array($order['order_status_id'], $successOrderStatuses)) {
            $paid = 1;
        }
        return $paid;
    }

    public function status()
    {
        $order = $this->isBlockBeeOrder(true);

        if (!$order) {
            return;
        }

        $this->load->model('extension/payment/blockbee');
        $metaData = $this->model_extension_payment_blockbee->getPaymentData($order['order_id']);
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
        }

        require_once(DIR_SYSTEM . 'library/blockbee.php');
        $this->load->model('localisation/currency');
        $this->load->model('localisation/currency');

        $currencySymbolLeft = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_left'];
        $currencySymbolRight = $this->model_localisation_currency->getCurrencies()[$order['currency_code']]['symbol_right'];

        $showMinFee = 0;

        $history = json_decode($metaData['blockbee_history'], true);

        $calc = BlockBeeHelper::calc_order($history, $metaData['blockbee_total'], $metaData['blockbee_total_fiat']);

        $already_paid = $calc['already_paid'];
        $already_paid_fiat = $calc['already_paid_fiat'] <= 0 ? 0 : $calc['already_paid_fiat'];

        $min_tx = floatval($metaData['blockbee_min']);

        $remaining_pending = $calc['remaining_pending'];
        $remaining_fiat = $calc['remaining_fiat'];

        $blockbee_pending = 0;
        if ($remaining_pending <= 0 && !$this->isOrderPaid($order)) {
            $blockbee_pending = 1;
        }

        $counter_calc = (int)$metaData['blockbee_last_price_update'] + (int)$this->config->get('payment_blockbee_refresh_values') - time();
        if (!$this->isOrderPaid($order) && $counter_calc <= 0) {
            $this->cron();
        }

        if ($remaining_pending <= $min_tx && $remaining_pending > 0) {
            $remaining_pending = $min_tx;
            $showMinFee = 1;
        }

        $data = [
            'is_paid' => $this->isOrderPaid($order),
            'is_pending' => $blockbee_pending,
            'crypto_total' => floatval($metaData['blockbee_total']),
            'qr_code_value' => $metaData['blockbee_qrcode_value'],
            'cancelled' => (int)$metaData['blockbee_cancelled'],
            'remaining' => $remaining_pending < 0 ? 0 : $remaining_pending,
            'fiat_remaining' => $currencySymbolLeft . ($remaining_fiat < 0 ? 0 : $remaining_fiat) . $currencySymbolRight,
            'coin' => strtoupper($metaData['blockbee_currency']),
            'show_min_fee' => $showMinFee,
            'order_history' => $history,
            'already_paid' => $currencySymbolLeft . $already_paid . $currencySymbolRight,
            'already_paid_fiat' => floatval($already_paid_fiat) <= 0 ? 0 : floatval($already_paid_fiat), true, false,
            'counter' => (string)$counter_calc,
            'fiat_symbol_left' => $currencySymbolLeft,
            'fiat_symbol_right' => $currencySymbolRight,
        ];

        echo json_encode($data);
        die();
    }

    public function cron()
    {
        require_once(DIR_SYSTEM . 'library/blockbee.php');
        $this->load->model('extension/payment/blockbee');
        $this->load->model('checkout/order');
        $this->response->addHeader('Content-Type: application/json');

        $order_timeout = intval($this->config->get('payment_blockbee_order_cancelation_timeout'));
        $value_refresh = intval($this->config->get('payment_blockbee_refresh_values'));
        $qrcode_size = intval($this->config->get('payment_blockbee_qrcode_size'));

        $apiKey = $this->config->get('payment_blockbee_api_key');

        $response = $this->response->setOutput(json_encode(['status' => 'ok']));

        if ($order_timeout === 0 && $value_refresh === 0) {
            return $response;
        }

        $orders = $this->model_extension_payment_blockbee->getOrders();

        if (empty($orders)) {
            return $response;
        }

        foreach ($orders as $order) {

            $order_id = $order['order_id'];

            $currency = $order['currency_code'];

            $metaData = json_decode($this->model_extension_payment_blockbee->getPaymentData($order['order_id']), true);

            if (!empty($metaData['blockbee_last_price_update'])) {
                $last_price_update = $metaData['blockbee_last_price_update'];

                $history = json_decode($metaData['blockbee_history'], true);

                $min_tx = floatval($metaData['blockbee_min']);

                $calc = BlockBeeHelper::calc_order($history, $metaData['blockbee_total'], floatval($metaData['blockbee_total_fiat']));

                $remaining = $calc['remaining'];
                $remaining_pending = $calc['remaining_pending'];
                $already_paid = $calc['already_paid'];

                if ($value_refresh !== 0 && $last_price_update + $value_refresh <= time()) {

                    if ($remaining === $remaining_pending) {
                        $blockbee_coin = $metaData['blockbee_currency'];

                        $crypto_total = BlockBeeHelper::get_conversion($currency, $blockbee_coin, $metaData['blockbee_total_fiat'], $this->disable_conversion, $this->config->get('payment_blockbee_api_key'));

                        $this->model_extension_payment_blockbee->updatePaymentData($order_id, 'blockbee_total', $crypto_total);

                        $calc_cron = BlockBeeHelper::calc_order($history, $crypto_total, $metaData['blockbee_total_fiat']);

                        $crypto_remaining_total = $calc_cron['remaining_pending'];

                        if ($remaining_pending <= $min_tx && $remaining_pending > 0) {
                            $qr_code_data_value = BlockBeeHelper::get_static_qrcode($metaData['blockbee_address'], $blockbee_coin, $min_tx, $apiKey, $qrcode_size);
                        } else {
                            $qr_code_data_value = BlockBeeHelper::get_static_qrcode($metaData['blockbee_address'], $blockbee_coin, $crypto_remaining_total, $apiKey, $qrcode_size);
                        }

                        $this->model_extension_payment_blockbee->updatePaymentData($order_id, 'blockbee_qrcode_value', $qr_code_data_value['qr_code']);
                    }

                    $this->model_extension_payment_blockbee->updatePaymentData($order_id, 'blockbee_last_price_update', time());
                }

                if ($order_timeout !== 0 && (strtotime($order['date_added']) + $order_timeout) <= time() && $already_paid <= 0 && (int)$metaData['blockbee_cancelled'] === 0) {
                    $this->model_checkout_order->addOrderHistory($order['order_id'], 7);
                    $this->model_extension_payment_blockbee->updatePaymentData($order_id, 'blockbee_cancelled', '1');
                }
            }
        }

        return $response;
    }

    public function callback()
    {
        require_once(DIR_SYSTEM . 'library/blockbee.php');
        $this->load->model('extension/payment/blockbee');

        $data = BlockBeeHelper::process_callback($_GET);

        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder((int)$data['order_id']);

        $metaData = json_decode($this->model_extension_payment_blockbee->getPaymentData($order['order_id']), true);

        if ($this->isOrderPaid($order) || $data['nonce'] !== $metaData['blockbee_nonce']) {
            die("*ok*");
        }

        $disable_conversion = $this->config->get('payment_blockbee_disable_conversion');

        $qrcode_size = $this->config->get('payment_blockbee_qrcode_size');

        $paid = $data['value_coin'];

        $min_tx = floatval($metaData['blockbee_min']);

        $history = json_decode($metaData['blockbee_history'], true);

        if (empty($history[$data['uuid']])) {
            $fiat_conversion = BlockBeeHelper::get_conversion($metaData['blockbee_currency'], $order['currency_code'], $paid, $disable_conversion, $apiKey);

            $history[$data['uuid']] = [
                'timestamp' => time(),
                'value_paid' => BlockBeeHelper::sig_fig($paid, 6),
                'value_paid_fiat' => $fiat_conversion,
                'pending' => $data['pending']
            ];
        } else {
            $history[$data['uuid']]['pending'] = $data['pending'];
        }

        $this->model_extension_payment_blockbee->updatePaymentData($order['order_id'], 'blockbee_history', json_encode($history));

        $metaData = json_decode($this->model_extension_payment_blockbee->getPaymentData($order['order_id']), true);

        $history = json_decode($metaData['blockbee_history'], true); // <<-something's wrong

        $calc = BlockBeeHelper::calc_order($history, $metaData['blockbee_total'], $metaData['blockbee_total_fiat']);

        $remaining = $calc['remaining'];
        $remaining_pending = $calc['remaining_pending'];

        if ($remaining_pending <= 0) {
            if ($remaining <= 0) {
                $processing_state = 2;
                $this->model_checkout_order->addOrderHistory($order['order_id'], $processing_state);
                $this->model_extension_payment_blockbee->updatePaymentData($order['order_id'], 'blockbee_txid', $data['txid_in']);
            }
            die('*ok*');
        }

        if ($remaining_pending <= $min_tx) {
            $qrcode_conv = BlockBeeHelper::get_static_qrcode($metaData['blockbee_address'], $metaData['blockbee_currency'], $min_tx, $apiKey, $qrcode_size)['qr_code'];
        } else {
            $qrcode_conv = BlockBeeHelper::get_static_qrcode($metaData['blockbee_address'], $metaData['blockbee_currency'], $remaining_pending, $apiKey, $qrcode_size)['qr_code'];
        }

        $this->model_extension_payment_blockbee->updatePaymentData($order['order_id'], 'blockbee_qrcode_value', $qrcode_conv);

        die("*ok*");
    }

    function order_pay_button(&$route, &$data, &$output)
    {
        $order_id = $this->request->get['order_id'];

        $this->load->model('extension/payment/blockbee');
        $this->load->model('checkout/order');

        $orderFetch = $this->model_checkout_order->getOrder($order_id);
        $order = $this->model_extension_payment_blockbee->getOrder($order_id);

        $orderObj = isset($order['response']) ? json_decode($order['response']) : '';

        if (!$orderObj) {
            return;
        }

        if ((int)$orderObj->blockbee_cancelled === 0 && isset($orderObj->blockbee_payment_url) && (int)$orderFetch['order_status_id'] === 1) {
            $data['button_continue'] = 'Pay Order';
            $data['continue'] = $orderObj->blockbee_payment_url;
        }
    }
}
