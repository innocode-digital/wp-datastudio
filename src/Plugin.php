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
        register_activation_hook(
            INNOCODE_GOOGLE_DATASTUDIO_FILE,
            [ $this, 'install' ]
        );
        register_deactivation_hook(
            INNOCODE_GOOGLE_DATASTUDIO_FILE,
            [ $this, 'uninstall' ]
        );

        $admin = $this->get_admin();

        add_action( 'admin_init', [ $admin, 'register_settings' ] );
        add_action( 'admin_menu', [ $admin, 'add_pages' ] );
        add_action( 'admin_init', [ $admin, 'add_sections' ] );
        add_action( 'admin_init', [ $admin, 'add_fields' ] );
        add_action( 'admin_notices', [ $admin, 'add_notices' ] );
        add_action( 'in_admin_header', [ $admin, 'remove_dashboard_notices' ], 999 );

        add_action(
            'add_option_' . static::OPTION_GROUP . '_roles',
            [ $this, 'update_roles' ],
            10, 2
        );
        add_action(
            'update_option_' . static::OPTION_GROUP . '_roles',
            [ $this, 'update_roles' ],
            10, 2
        );
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
        	$roles = get_option( $key, [] );

        	if ( ! $roles ) {
		        $roles = [];
	        }

            return array_merge(
                isset( wp_roles()->roles['administrator'] )
                    ? [ 'administrator' ]
                    : [],
	            $roles
            );
        }

        return get_option( $key );
    }

    public function install()
    {
        $capability = 'read_' . static::OPTION_GROUP;

        foreach ( $this->option( 'roles' ) as $name ) {
			$role = get_role( $name );

			if ( $role && ! $role->has_cap( $capability ) ) {
				$role->add_cap( $capability );
			}
        }
    }

    public function uninstall()
    {
        $capability = 'read_' . static::OPTION_GROUP;

        foreach ( wp_roles()->role_objects as $role ) {
            if ( $role->has_cap( $capability ) ) {
                $role->remove_cap( $capability );
            }
        }
    }

    /**
     * @param mixed      $old_value
     * @param array|null $value
     */
    public function update_roles( $old_value, $value )
    {
    	if ( ! $value ) {
    		return;
	    }

        $capability = 'read_' . static::OPTION_GROUP;

        foreach ( wp_roles()->role_objects as $role ) {
            if ( $role->name == 'administrator' ) {
                continue;
            }

            if ( in_array( $role->name, $value ) && ! $role->has_cap( $capability ) ) {
                $role->add_cap( $capability );
            } elseif ( $role->has_cap( $capability ) ) {
                $role->remove_cap( $capability );
            }
        }
    }

    public function add_user_notices()
    {
        if (
            get_current_screen()->id == 'settings_page_' . static::OPTION_GROUP . '_options' ||
            ! current_user_can( 'read_' . static::OPTION_GROUP ) ||
            ! $this->current_user_can_read_notice()
        ) {
            return;
        }

        printf(
            '<div class="notice notice-success notice-%s is-dismissible"><p>%s</p></div>',
            static::OPTION_GROUP,
            sprintf(
                __( '<a href="https://datastudio.google.com/" target="_blank" rel="noopener noreferrer">Google Data Studio</a> dashboard is available <a href="%s">here</a>!' ),
                admin_url(
                    'index.php?page=' . static::OPTION_GROUP . '_dashboard'
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
        	in_array(
		        get_current_screen()->id,
		        [
			        'settings_page_' . static::OPTION_GROUP . '_options',
			        'dashboard_page_' . static::OPTION_GROUP . '_dashboard'
		        ]
	        ) ||
	        ! current_user_can( 'read_' . static::OPTION_GROUP )
        ) {
            return;
        }

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $settings = [
	        'ajaxURL'         => admin_url( 'admin-ajax.php' ),
	        'dismissAction'   => static::OPTION_GROUP . '_dismiss_notice',
	        'dismissNonce'    => wp_create_nonce(
		        static::OPTION_GROUP . '_dismiss_notice'
	        ),
	        'dismissCSSClass' => 'notice-' . static::OPTION_GROUP,
        ];

        if ( $this->current_user_can_read_notice() ) {
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
		        $settings
	        );
        }

        if ( $this->current_user_can_read_pointer() ) {
            wp_enqueue_style( 'wp-pointer' );
	        wp_enqueue_script( 'wp-pointer' );
	        wp_enqueue_script(
		        'innocode-google-datastudio-pointers',
		        plugins_url(
			        "public/js/pointers$suffix.js",
			        INNOCODE_GOOGLE_DATASTUDIO_FILE
		        ),
		        [ 'jquery', 'wp-pointer' ],
		        INNOCODE_GOOGLE_DATASTUDIO_VERSION,
		        true
	        );
	        wp_localize_script(
		        'innocode-google-datastudio-pointers',
		        'innocodeGoogleDataStudioPointers',
		        array_merge( $settings, [
			        'pointerSelector' => sprintf(
				        '.wp-menu-open [href="%s"]',
				        esc_attr( 'index.php?page=' . static::OPTION_GROUP . '_dashboard'    )
			        ),
			        'pointerOptions'  => [
				        'content' => sprintf( '<h3>%s</h3><p>%s</p>',
					        esc_html__(
						        'Google Data Studio Dashboard',
						        'innocode-google-datastudio'
					        ),
					        esc_html__(
						        'Your digital marketing reporting service. This is where your subscribed reporting service with Google Data Studio could be shown.',
						        'innocode-google-datastudio'
					        )
				        ),
			        ],
		        ] )
	        );
        }
    }

    /**
     * Checks if current user should see notice of plugin current version.
     * Patch versions are skipped.
     *
     * @return bool
     */
    public function current_user_can_read_notice()
    {
        return version_compare(
	        preg_replace(
	        	'/\d+$/', '0', INNOCODE_GOOGLE_DATASTUDIO_VERSION
	        ),
            $this->get_user_dismissed_notice_version( get_current_user_id() ),
            '>'
        );
    }

	/**
	 * Checks if current user should see pointer with plugin info.
	 * Unlike notice, pointer should be shown only one time.
	 *
	 * @return bool
	 */
    public function current_user_can_read_pointer()
    {
    	return ! $this->get_user_dismissed_notice_version( get_current_user_id() );
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
