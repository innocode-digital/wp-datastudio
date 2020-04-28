<div class="wrap">
    <h1><?php _e( 'Google Data Studio', 'innocode-google-datastudio' ) ?></h1>
    <form method="post" action="<?= admin_url( 'options.php' ) ?>">
        <?php settings_fields( Innocode\GoogleDataStudio\Plugin::OPTION_GROUP ) ?>
        <?php do_settings_sections(
            Innocode\GoogleDataStudio\Plugin::OPTION_GROUP . '_options'
        ) ?>
        <?php submit_button() ?>
    </form>
</div>
