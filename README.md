# Wordpress native payment gateway

Установка модуля в Wordpress:

Перейти в раздел администратора

Установить плагин wooppay-3.0.0 : перейти на страницу Plugins, нажать Add New -> Upload Plugin, загрузить распакованный модуль в .zip формате

Активировать плагины в WooCommerce -> Settings -> Payments

В настройках  ввести ваши данные.Пример:

API Username: test_merch

API Password: A12345678a

Order prefix: card

Service name: test_merch_invoice

Поле "Место оплаты" дает выбор, где пользователь будет оплачивать инвойс, с редиректом на wooppay или оставаясь на сайте магазина.

Поле "Поле привязывать карты покупателей" нужно для сохранения карт при оплате и их последующего использования.

Поле "Terms" нужно для того чтобы при оплате прикрепить ссылку для принятия оферты. Если поле Terms содержит ссылку, то на странице оплаты появится чекбокс обязательный для принятия.

Перейти в магазин и произвести оплату.

