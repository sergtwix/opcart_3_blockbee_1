<?php

class ControllerExtensionPaymentBlockBee extends Controller
{

    private $error = array();

    public function index()
    {
        require_once(DIR_SYSTEM . 'library/blockbee.php');

        $this->load->language('extension/payment/blockbee');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {


            $a = [];
            if (isset($_POST['payment_blockbee_cryptocurrencies'])) {
                foreach ($_POST['payment_blockbee_cryptocurrencies'] as $value) {
                    $a[$value] = $value;
                }
            }

            $this->request->post['payment_blockbee_cryptocurrencies'] = $a;

            $this->model_setting_setting->editSetting('payment_blockbee', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->load->model('localisation/order_status');

        $orderStatuses = $this->model_localisation_order_status->getOrderStatuses();
        $orderStatusesFiltered = [];
        $orderStatusesIgnore = [
            'Canceled',
            'Canceled Reversal',
            'Chargeback',
            'Complete',
            'Denied',
            'Expired',
            'Failed',
            'Processed',
            'Processing',
            'Refunded',
            'Reversed',
            'Shipped',
            'Voided'
        ];
        foreach ($orderStatuses as $orderStatus) {
            if (!in_array($orderStatus['name'], $orderStatusesIgnore)) {
                $orderStatusesFiltered[] = $orderStatus;
            }
        }
        $data['order_statuses'] = $orderStatusesFiltered;

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/blockbee', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/blockbee', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        /**
         * Defining Cryptocurrencies
         */

        $supported_coins = BlockBeeHelper::get_supported_coins();

        $data['payment_blockbee_cryptocurrencies_array'] = $supported_coins;

        if (isset($this->request->post['payment_blockbee_cryptocurrencies'])) {
            $data['payment_blockbee_cryptocurrencies'] = $this->request->post['payment_blockbee_cryptocurrencies'];
        } else {
            $data['payment_blockbee_cryptocurrencies'] = $this->config->get('payment_blockbee_cryptocurrencies');
        }

        
        if (isset($this->request->post['payment_blockbee_disable_conversion'])) {
            $data['payment_blockbee_disable_conversion'] = $this->request->post['payment_blockbee_disable_conversion'];
        } else {
            $data['payment_blockbee_disable_conversion'] = $this->config->get('payment_blockbee_disable_conversion');
        }

        if (isset($this->request->post['payment_blockbee_title'])) {
            $data['payment_blockbee_title'] = $this->request->post['payment_blockbee_title'];
        } else {
            $data['payment_blockbee_title'] = $this->config->get('payment_blockbee_title');
        }

        if (isset($this->request->post['payment_blockbee_api_key'])) {
            $data['payment_blockbee_api_key'] = $this->request->post['payment_blockbee_api_key'];
        } else {
            $data['payment_blockbee_api_key'] = $this->config->get('payment_blockbee_api_key');
        }

        if (isset($this->request->post['payment_blockbee_standard_geo_zone_id'])) {
            $data['payment_blockbee_standard_geo_zone_id'] = $this->request->post['payment_blockbee_standard_geo_zone_id'];
        } else {
            $data['payment_blockbee_standard_geo_zone_id'] = $this->config->get('payment_blockbee_standard_geo_zone_id');
        }

        if (isset($this->request->post['payment_blockbee_order_status_id'])) {
            $data['payment_blockbee_order_status_id'] = $this->request->post['payment_blockbee_order_status_id'];
        } else {
            $data['payment_blockbee_order_status_id'] = $this->config->get('payment_blockbee_order_status_id');
            if (!$data['payment_blockbee_order_status_id']) {
                $data['payment_blockbee_order_status_id'] = 1;
            }
        }

        if (isset($this->request->post['payment_blockbee_status'])) {
            $data['payment_blockbee_status'] = $this->request->post['payment_blockbee_status'];
        } else {
            $data['payment_blockbee_status'] = $this->config->get('payment_blockbee_status');
        }

        if (isset($this->request->post['payment_blockbee_blockchain_fees'])) {
            $data['payment_blockbee_blockchain_fees'] = $this->request->post['payment_blockbee_blockchain_fees'];
        } else {
            $data['payment_blockbee_blockchain_fees'] = $this->config->get('payment_blockbee_blockchain_fees');
        }

        if (isset($this->request->post['payment_blockbee_fees'])) {
            $data['payment_blockbee_fees'] = $this->request->post['payment_blockbee_fees'];
        } else {
            $data['payment_blockbee_fees'] = $this->config->get('payment_blockbee_fees');
        }

        if (isset($this->request->post['payment_blockbee_color_scheme'])) {
            $data['payment_blockbee_color_scheme'] = $this->request->post['payment_blockbee_color_scheme'];
        } else {
            $data['payment_blockbee_color_scheme'] = $this->config->get('payment_blockbee_color_scheme');
        }

        if (isset($this->request->post['payment_blockbee_refresh_values'])) {
            $data['payment_blockbee_refresh_values'] = $this->request->post['payment_blockbee_refresh_values'];
        } else {
            $data['payment_blockbee_refresh_values'] = $this->config->get('payment_blockbee_refresh_values');
        }

        if (isset($this->request->post['payment_blockbee_order_cancelation_timeout'])) {
            $data['payment_blockbee_order_cancelation_timeout'] = $this->request->post['payment_blockbee_order_cancelation_timeout'];
        } else {
            $data['payment_blockbee_order_cancelation_timeout'] = $this->config->get('payment_blockbee_order_cancelation_timeout');
        }

        if (isset($this->request->post['payment_blockbee_branding'])) {
            $data['payment_blockbee_branding'] = $this->request->post['payment_blockbee_branding'];
        } else {
            $data['payment_blockbee_branding'] = $this->config->get('payment_blockbee_branding');
        }

        if (isset($this->request->post['payment_blockbee_qrcode'])) {
            $data['payment_blockbee_qrcode'] = $this->request->post['payment_blockbee_qrcode'];
        } else {
            $data['payment_blockbee_qrcode'] = $this->config->get('payment_blockbee_qrcode');
        }

        if (isset($this->request->post['payment_blockbee_qrcode_default'])) {
            $data['payment_blockbee_qrcode_default'] = $this->request->post['payment_blockbee_qrcode_default'];
        } else {
            $data['payment_blockbee_qrcode_default'] = $this->config->get('payment_blockbee_qrcode_default');
        }

        if (isset($this->request->post['payment_blockbee_qrcode_size'])) {
            $data['payment_blockbee_qrcode_size'] = $this->request->post['payment_blockbee_qrcode_size'];
        } else {
            $data['payment_blockbee_qrcode_size'] = $this->config->get('payment_blockbee_qrcode_size');
        }

        if (isset($this->request->post['payment_blockbee_sort_order'])) {
            $data['payment_blockbee_sort_order'] = $this->request->post['payment_blockbee_sort_order'];
        } else {
            $data['payment_blockbee_sort_order'] = $this->config->get('payment_blockbee_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/blockbee', $data));
    }

    public function install()
    {
        $this->load->model('extension/payment/blockbee');
        // Create order database if doesn't exist
        $this->model_extension_payment_blockbee->install();

        // Set Events
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('blockbee_after_purchase', 'catalog/view/common/success/after', 'extension/payment/blockbee/after_purchase');
        $this->model_setting_event->addEvent('blockbee_order_info', 'admin/view/sale/order_info/before', 'extension/payment/blockbee/order_info');
        $this->model_setting_event->addEvent('blockbee_order_button', 'catalog/view/account/order_info/before', 'extension/payment/blockbee/order_pay_button');
    }

    public function order_info(&$route, &$data, &$output)
    {
        $order_id = $this->request->get['order_id'];
        $this->load->model('extension/payment/blockbee');
        $order = $this->model_extension_payment_blockbee->getOrder($order_id);
        if ($order) {
            $metaData = $order['response'];
            if (!empty($metaData)) {
                $metaData = json_decode($metaData, true);
                $fields = [];
                foreach ($metaData as $key => $val) {
                    $field = ['name' => $key, 'value' => $val];
                    $fields[] = $field;
                }
                if (isset($data['payment_custom_fields']) && is_array($data['payment_custom_fields'])) {
                    $data['payment_custom_fields'] = array_merge($data['payment_custom_fields'], $fields);
                } else {
                    $data['payment_custom_fields'] = $fields;
                }
            }
        }
    }
}
