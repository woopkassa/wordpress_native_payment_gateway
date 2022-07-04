<div style="display: flex; flex-direction: column; justify-content: center ; align-items: center">
	<img src="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '',
			realpath(__DIR__)) ?>/assets/images/payment-error.svg" width="100" height="94">
	<p style="margin-top: 20px; font-size: 16px">При формировании инвойса что-то пошло не так.</p>
</div>