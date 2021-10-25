<?php
?>
<div style="display: flex; flex-direction: column; justify-content: center ; align-items: center">
	<?php if ($_GET['wooppay_frame_status'] == 1): ?>
        <img src="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '',
				realpath(__DIR__)) ?>/assets/images/payment-success.svg" width="100" height="94">
	<?php else: ?>
        <img src="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '',
		        realpath(__DIR__)) ?>/assets/images/payment-error.svg" width="100" height="94">
	<?php endif; ?>
    <p style="margin-top: 20px; font-size: 16px"><?= $_GET['wooppay_frame_message'] ?></p>
	<?php if ($_GET['wooppay_frame_status'] == 1): ?>
        <a id="finish_url" href="<?php echo $_SESSION['wooppay']['finish_url'] ?>"> Завершить заказ </a>
	<?php endif; ?>
</div>

