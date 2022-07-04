<div style="display: flex; flex-direction: column; justify-content: center ; align-items: center">
    <img src="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '',
			realpath(__DIR__)) ?>/assets/images/payment-success.svg" width="100" height="94">
    <p style="margin-top: 20px; font-size: 16px">Оплата прошла успешно!</p>
    <a id="finish_url" style="cursor: pointer"> Завершить заказ </a>
</div>