<?php
//<?php
/**
 * Payment PaynetEasy
 *
 * PaynetEasy payments processing
 *
 * @category    plugin
 * @version     0.0.1
 * @author      pathologic
 * @internal    @events OnRegisterPayments,OnBeforeOrderProcessing,OnBeforeOrderSending,OnManagerBeforeOrderRender
 * @internal    @properties &title=Title;text; &token=Control;text; &login=Login;text; &endpoint=Endpoint;text; &debug=Debug;list;No==0||Yes==1;1 &production=Production mode;list;No==0||Yes==1;1
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */

if (empty($modx->commerce) && !defined('COMMERCE_INITIALIZED')) {
    return;
}

$isSelectedPayment = !empty($order['fields']['payment_method']) && $order['fields']['payment_method'] == 'payneteasy';

switch ($modx->event->name) {
    case 'OnRegisterPayments': {
        $class = new \Commerce\Payments\PaynetEasyPayment($modx, $params);
        if (empty($params['title'])) {
            $lang = $modx->commerce->getUserLanguage('payneteasy');
            $params['title'] = $lang['payneteasy.caption'];
        }
        $modx->commerce->registerPayment('payneteasy', $params['title'], $class);
        break;
    }

    case 'OnBeforeOrderSending': {
        if ($isSelectedPayment) {
            $FL->setPlaceholder('extra', $FL->getPlaceholder('extra', '') . $modx->commerce->loadProcessor()->populateOrderPaymentLink());
        }

        break;
    }

    case 'OnManagerBeforeOrderRender': {
        if (isset($params['groups']['payment_delivery']) && $isSelectedPayment) {
            $lang = $modx->commerce->getUserLanguage('payneteasy');
            $params['groups']['payment_delivery']['fields']['payment_link'] = [
                'title'   => $lang['payneteasy.link_caption'],
                'content' => function($data) use ($modx) {
                    return $modx->commerce->loadProcessor()->populateOrderPaymentLink('@CODE:<a href="[+link+]" target="_blank">[+link+]</a>');
                },
                'sort' => 50,
            ];
            $params['groups']['payer_info'] = [
                'title' => 'Информация о плательщике',
                'width' => '33.333%',
                'fields' => []
            ];
            $fields = [
                'payer_first_name',
                'payer_last_name',
                'payer_phone',
                'payer_email',
                'payer_country',
                'payer_state',
                'payer_city',
                'payer_zip',
                'payer_address',
            ];
            foreach ($fields as $field) {
                $params['groups']['payer_info']['fields'][] = [
                    'title' => $lang['payneteasy.' . $field],
                    'content' => function($data) use ($field) {
                        return $data['fields'][$field];
                    }
                ];
            }
        }

        break;
    }
}