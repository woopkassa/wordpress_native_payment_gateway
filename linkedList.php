<form id="wooppay_card_choice" class="pb-3">
    <div class="row row-1 empty" style="display: flex; align-items: center; height: 60px">
        <div class="col-7"><input type="radio" name="card_id" value="" style="display: none"> <label
                    for="new_card" style="margin: 0">Оплатить с новой карты</label></div>
    </div>
	<?php
	foreach ($_GET['cards'] as $item) {
		?>

        <div class="row row-1" style="display: flex; align-items: center">
            <div class="col-2"
                 style="margin-right: 20px;width: 48px;height: 48px;display: flex; justify-content: center;">
                <img class="img-fluid" src="<?php echo $item->bank->picture_url ?>"
                     style="max-width: 48px;max-height: 48px"/>
            </div>
            <div class="col-7"><input type="radio" name="card_id" value="<?php echo $item->id; ?>" style="display: none"
                                      checked="false"> <label
                        for="<?php echo $item->id; ?>" style="margin: 0"><?php echo str_replace(array('-', 'x'),
						array(' ', '*'),
						$item->mask); ?></label></div>
        </div>
		<?php
	}
	?>
    <button type="button"
            id="wooppay_choice_card_btn">
        Продолжить оплату
    </button>
</form>

<style>

    #wooppay_frame_modal input {
        border: none;
        outline: none;
        font-size: 1rem;
        font-weight: 600;
        color: #000;
        width: 100%;
        min-width: unset;
        background-color: transparent;
        border-color: transparent;
        margin: 0
    }

    #wooppay_choice_card_btn {
        border: none;
        outline: none;
        font-weight: 600;
        color: #000 !important;
        width: 100%;
        display: inline-block;
        font-size: 16px;
        background-color: transparent;
        margin: 0
    }

    #wooppay_choice_card_btn:hover {
        text-decoration: underline;
    }

    #wooppay_frame_modal .row {
        margin: 0;
        overflow: hidden
    }

    #wooppay_frame_modal .row-1 {
        border: 2px solid rgba(0, 0, 0, 0.137);
        padding: 0.5rem;
        outline: none;
        width: 100%;
        min-width: unset;
        border-radius: 5px;
        background-color: rgba(221, 228, 236, 0.301);
        border-color: rgba(221, 228, 236, 0.459);
        margin: 2vh 0;
        overflow: hidden
    }


    #wooppay_frame_modal .row .col-2,
    #wooppay_frame_modal .col-7,
    #wooppay_frame_modal .col-3 {
        display: flex;
        align-items: center;
        text-align: center;
        padding: 0 1vh
    }

    #wooppay_frame_modal .row .col-2 {
        padding-right: 0
    }

    #wooppay_frame_modal .row.row-1:hover {
        border-color: green;
        cursor: pointer;
    }

    #wooppay_frame_modal .row.row-1 label {
        cursor: pointer;
    }

    #wooppay_frame_modal .row.row-1.active {
        border-color: green;
    }

</style>

<script>
    (function ($) {
        $('#wooppay_frame_modal .row-1').on('click', function () {
            $('#wooppay_frame_modal .row-1').removeClass('active');
            $('#wooppay_frame_modal .row-1').find('input').prop('checked', false)
            $(this).find('input').prop('checked', true)
            $(this).toggleClass('active');
        })
        $('#wooppay_frame_modal .empty')[0].click();
    })(jQuery);
</script>
