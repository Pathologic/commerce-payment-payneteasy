<?php


namespace Commerce\Payments;

class PaynetEasyPayment extends Payment
{
    public $debug = false;

    public function __construct($modx, array $params = [])
    {
        parent::__construct($modx, $params);
        $this->lang = $modx->commerce->getUserLanguage('payneteasy');
        $this->debug = !empty($this->getSetting('debug'));
    }

    public function getMarkup()
    {
        $login = $this->getSetting('login');
        $token = $this->getSetting('token');
        $endpoint = $this->getSetting('endpoint');
        $out = '';
        if (empty($login) || empty($token) || empty($endpoint)) {
            $out =  '<span class="error" style="color: red;">' . $this->lang['payneteasy.error_empty_params'] . '</span>';
        }

        return $out;
    }

    public function getPaymentLink()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->getOrder();
        $currency = ci()->currency->getCurrency($order['currency']);
        $amount = (float) $order['amount'];
        $payment = $this->createPayment($order['id'], ci()->currency->convertToDefault($amount, $currency['code']));
        $description = ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
            'order_id'  => $order['id'],
            'site_name' => $this->modx->getConfig('site_name'),
        ]);
        $data = [
            'client_orderid'      => $order['id'],
            'order_desc'          => $description,
            'first_name'          => $order['fields']['payer_first_name'],
            'last_name'           => $order['fields']['payer_last_name'],
            'address1'            => $order['fields']['payer_address'],
            'city'                => $order['fields']['payer_city'],
            'state'               => $order['fields']['payer_state'],
            'zip_code'            => $order['fields']['payer_zip'],
            'country'             => $order['fields']['payer_country'],
            'phone'               => preg_replace('/[^\d]/', '', $order['fields']['payer_phone']),
            'email'               => $order['fields']['payer_email'],
            'amount'              => number_format($payment['amount'], 2, '.', ''),
            'currency'            => ci()->currency->getDefaultCurrencyCode(),
            'ipaddress'           => $_SERVER['REMOTE_ADDR'],
            'redirect_url'        => $this->modx->getConfig('site_url') . 'commerce/payneteasy/payment-success/',
            'server_callback_url' => $this->modx->getConfig('site_url') . 'commerce/payneteasy/payment-process/?' . http_build_query([
                'stage'       => 'processPaynetEasyCallback',
                'paymentId'   => $payment['id'],
                'paymentHash' => $payment['hash'],
            ])
        ];

        $response = $this->sendRequest($data);
        if ($response !== false && !empty($response['redirect-url'])) {
            return $response['redirect-url'];
        } else {
            if ($this->debug) {
                $this->modx->logEvent(0, 3, 'Link is not received', 'Commerce PaynetEasy Payment Debug: getPaymentLink failed');
            }
            $docid = $this->modx->commerce->getSetting('payment_failed_page_id', $this->modx->getConfig('site_start'));
            $url   = $this->modx->makeUrl($docid);

            return $url;
        }
    }

    public function handleSuccess()
    {
        if ($this->debug) {
            $this->modx->logEvent(0, 1, 'Data: <pre>' . htmlentities(print_r($_REQUEST, true)) . '</pre>', 'Commerce PaynetEasy Payment Debug: payment finished');
        }
        if (!empty($_POST['status']) && $_POST['status'] == 'approved') {
            return true;
        } else {
            $this->modx->sendRedirect($this->modx->getConfig('site_url') . 'commerce/payneteasy/payment-failed/');
        }
    }

    public function handleCallback()
    {
        if ($this->debug) {
            $this->modx->logEvent(0, 1, 'Callback data: <pre>' . htmlentities(print_r($_REQUEST, true)) . '</pre>', 'Commerce PaynetEasy Payment Debug: callback start');
        }
        if (!empty($_REQUEST['status']) && $_GET['status'] == 'approved' && !empty($_REQUEST['orderid']) && !empty($_REQUEST['client_orderid']) && !empty($_REQUEST['control'])) {
            $control = sha1($_REQUEST['status'] . $_REQUEST['orderid'] . $_REQUEST['client_orderid'] . $this->getSetting('token'));
            if ($control == $_REQUEST['control']) {
                try {
                    $this->modx->commerce->loadProcessor()->processPayment($_REQUEST['paymentId'], (float)$_REQUEST['amount']);
                } catch (\Exception $e) {
                    if ($this->debug) {
                        $this->modx->logEvent(0, 3, 'Payment processing failed: ' . $e->getMessage(), 'Commerce PaynetEasy Payment Debug: callback failed');
                    }
                    
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getRequestPaymentHash()
    {
        if (isset($_REQUEST['paymentHash']) && is_scalar($_REQUEST['paymentHash'])) {
            return $_REQUEST['paymentHash'];
        }

        return null;
    }

    protected function sendRequest($data = [])
    {
        $gateway = $this->getSetting('production')
            ? 'https://gate.payneteasy.eu/paynet/api/v2/'
            : 'https://sandbox.payneteasy.eu/paynet/api/v2/';
        $endpoint = $this->getSetting('endpoint');
        $gateway .= 'sale-form/' . $endpoint;
        $data['control'] = sha1($endpoint . $data['client_orderid'] . (int)($data['amount'] * 100)   . $data['email'] . $this->getSetting('token'));
        $curl = curl_init($gateway);
        curl_setopt_array($curl, [
            CURLOPT_HEADER         => 0,
            CURLOPT_USERAGENT      => 'EvolutionCMS',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => 1
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($curl);
        if ($response !== false) {
            parse_str($response, $response);
        }
        if ($this->debug) {
            $this->modx->logEvent(0, 3, 'Payment request payload: <pre>' . htmlentities(print_r($data, true)) . '</pre>',
                'Commerce PaynetEasy Payment Debug: request sent');
            $this->modx->logEvent(0, 3, 'Payment request response: <pre>' . htmlentities(print_r($response, true)) . '</pre>',
                'Commerce PaynetEasy Payment Debug: response received');
        }

        return $response;
    }
}
