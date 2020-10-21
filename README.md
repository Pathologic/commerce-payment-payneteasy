# Платежный плагин PaynetEasy для Commerce

Реализует сценарий [Payment Form](https://doc.payneteasy.com/card_payment_API/preauth_capture_transactions.html#payment-form-integration).

Валюта по умолчанию должна совпадать с валютой счета в PaynetEasy.

Форма заказа должна содержать обязательные поля с [информацией о плательщике](https://doc.payneteasy.com/card_payment_API/preauth_capture_transactions.html#payment-form-request-parameters):
* payer_first_name - имя;
* payer_last_name - фамилия;
* payer_phone - телефон;
* payer_email - e-mail;
* payer_country - код страны;
* payer_state - код штата (для США, Канады и Австралии);
* payer_city - город;
* payer_zip - почтовый индекс;
* payer_address - адрес.

Для упрощения обработки формы заказа в параметре prepare можно указать сниппет preparePaynetEasyPayment. Это позволит заполнить пустые поля плательщика значениями из одноименных (без префикса payer_) полей, а также задать правила валидации этих полей в отдельном параметре payerRules - они будут действовать только если выбран соответствующий метод платежа.



