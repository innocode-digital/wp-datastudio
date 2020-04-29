<?php $google_datastudio = innocode_google_datastudio() ?>
<?php $option = [ $google_datastudio, 'option' ]; ?>
<?php $url = $option( 'url' ) ?>
<?php if ( $url ) : ?>
    <iframe
        src="<?= esc_url( $url ) ?>"
        style="border: 0; width: 100%; height: calc(100vh - 100px);"
        allowfullscreen
    ></iframe>
<?php else : ?>
    <?php $fallback_message = $option( 'fallback_message' ) ?>
    <?php if ( $fallback_message ) : ?>
        <div class="wrap">
            <h1><?php _e( 'Google Data Studio', 'innocode-google-datastudio' ) ?></h1>
            <?= apply_filters( 'the_content', $fallback_message ) ?>
        </div>
    <?php endif ?>
<?php endif ?>
<?php if ( $google_datastudio->current_user_can_read_notice() ) : ?>
    <script>
        jQuery(function ($) {
            $.post('<?= admin_url( 'admin-ajax.php' ) ?>', {
                action: '<?= Innocode\GoogleDataStudio\Plugin::OPTION_GROUP ?>_dismiss_notice',
                _wpnonce: '<?= wp_create_nonce(
                    Innocode\GoogleDataStudio\Plugin::OPTION_GROUP . '_dismiss_notice'
                ) ?>'
            });
        });
    </script>
<?php endif;