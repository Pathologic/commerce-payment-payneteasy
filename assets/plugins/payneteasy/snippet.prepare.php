<?php
$payment = $FormLister->getField('payment_method');
if ($FormLister->isSubmitted() && $payment == 'payneteasy') {
    $fields = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'country',
        'city',
        'zip',
        'state',
    ];
    foreach ($fields as $field) {
        $payer_field = 'payer_' . $field;
        $value = $FormLister->getField($payer_field);
        if (empty($value)) {
            $FormLister->setField($payer_field, $FormLister->getField($field));
        }
    }
    $rules = $FormLister->getValidationRules('rules');
    $payerRules = $FormLister->getValidationRules('payerRules');
    $FormLister->config->setConfig([
        'rules' => array_merge($rules, $payerRules)
    ]);
}