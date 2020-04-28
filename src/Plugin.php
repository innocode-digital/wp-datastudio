<?php

namespace Innocode\GoogleDataStudio;

/**
 * Class Plugin
 * @package Innocode\GoogleDataStudio
 */
final class Plugin
{
    const OPTION_GROUP = 'innocode_google_data_studio';

    /**
     * @var string
     */
    private $path;
    /**
     * @var Admin
     */
    private $admin;

    /**
     * Plugin constructor.
     * @param string $path
     */
    public function __construct( string $path )
    {
        $this->path = $path;
        $this->admin = new Admin(
            [ $this, 'option' ],
            [ $this, 'view' ]
        );
    }

    public function run()
    {
        $admin = $this->get_admin();

        add_action( 'admin_init', [ $admin, 'register_settings' ] );
        add_action( 'admin_menu', [ $admin, 'add_pages' ] );
        add_action( 'admin_init', [ $admin, 'add_sections' ] );
        add_action( 'admin_init', [ $admin, 'add_fields' ] );
        add_action( 'admin_notices', [ $admin, 'add_notices' ] );
        add_action( 'in_admin_header', [ $admin, 'remove_dashboard_notices' ], 999 );

        add_action( 'admin_notices', [ $this, 'add_user_notices' ] );
        add_action(
            'wp_ajax_' . static::OPTION_GROUP . '_dismiss_notice',
            [ $this, 'dismiss_notice' ]
        );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function get_views_dir()
    {
        return "{$this->get_path()}/resources/views";
    }

    /**
     * @param string $name
     * @return string
     */
    public function get_view_file( string $name )
    {
        return "{$this->get_views_dir()}/$name";
    }

    /**
     * @param string $name
     */
    public function view( string $name )
    {
        $file = $this->get_view_file( "$name.php" );

        require_once $file;
    }

    /**
     * @return Admin
     */
    public function get_admin()
    {
        return $this->admin;
    }

    /**
     * @param string $name
     * @return false|mixed
     */
    public function option( string $name )
    {
        $settings = $this->get_admin()->get_settings();

        if ( ! array_key_exists( $name, $settings ) ) {
            return false;
        }

        $key = static::OPTION_GROUP . "_$name";

        if ( $name == 'roles' ) {
            return array_merge(
                isset( wp_roles()->roles['administrator'] )
                    ? [ 'administrator' ]
                    : [],
                get_option( $key, [] )
            );
        }

        return get_option( $key );
    }

    public function add_user_notices()
    {
        if (
            get_current_screen()->id == 'settings_page_' . Plugin::OPTION_GROUP . '_options' ||
            ! $this->current_user_can_see_dashboard() ||
            ! $this->current_user_can_see_notice()
        ) {
            return;
        }

        printf(
            '<div class="notice notice-success notice-%s is-dismissible"><p>%s</p></div>',
            static::OPTION_GROUP,
            sprintf(
                __( '<a href="https://datastudio.google.com/" target="_blank" rel="noopener noreferrer">Google Data Studio</a> dashboard is available <a href="%s">here</a>!' ),
                admin_url(
                    'index.php?page=' . Plugin::OPTION_GROUP . '_dashboard'
                )
            )
        );
    }

    public function dismiss_notice()
    {
        check_ajax_referer( static::OPTION_GROUP . '_dismiss_notice' );

        global $wpdb;

        update_user_meta(
            get_current_user_id(),
            "{$wpdb->get_blog_prefix()}dismissed_notice",
            INNOCODE_GOOGLE_DATASTUDIO_VERSION
        );
        wp_send_json_success();
    }

    public function enqueue_scripts()
    {
        if (
            get_current_screen()->id == 'settings_page_' . Plugin::OPTION_GROUP . '_options' ||
            ! $this->current_user_can_see_dashboard() ||
            ! $this->current_user_can_see_notice()
        ) {
            return;
        }

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'innocode-google-datastudio-notices',
            plugins_url(
                "public/js/notices$suffix.js",
                INNOCODE_GOOGLE_DATASTUDIO_FILE
            ),
            [ 'jquery' ],
            INNOCODE_GOOGLE_DATASTUDIO_VERSION,
            true
        );
        wp_localize_script(
            'innocode-google-datastudio-notices',
            'innocodeGoogleDataStudioNotices',
            [
                'ajaxURL'         => admin_url( 'admin-ajax.php' ),
                'dismissAction'   => static::OPTION_GROUP . '_dismiss_notice',
                'dismissNonce'    => wp_create_nonce(
                    static::OPTION_GROUP . '_dismiss_notice'
                ),
                'dismissCSSClass' => 'notice-' . static::OPTION_GROUP,
            ]
        );
    }

    /**
     * @return bool
     */
    public function current_user_can_see_dashboard()
    {
        return (bool) count(
            array_intersect(
                $this->option( 'roles' ),
                wp_get_current_user()->roles
            )
        );
    }

    /**
     * @return bool
     */
    public function current_user_can_see_notice()
    {
        return version_compare(
            INNOCODE_GOOGLE_DATASTUDIO_VERSION,
            $this->get_user_dismissed_notice_version( get_current_user_id() ),
            '>'
        );
    }

    /**
     * @param int $user_id
     * @return false|string
     */
    public function get_user_dismissed_notice_version( int $user_id )
    {
        global $wpdb;

        return get_user_meta(
            $user_id,
            "{$wpdb->get_blog_prefix()}dismissed_notice",
            true
        );
    }
}
