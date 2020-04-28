<?php

namespace Innocode\GoogleDataStudio;

/**
 * Class Admin
 * @package Innocode\GoogleDataStudio
 */
final class Admin
{
    /**
     * @var array
     */
    private $settings = [
        'url'              => [
            'sanitize_callback' => 'esc_url_raw',
        ],
        'fallback_message' => [
            'sanitize_callback' => 'wp_kses_post',
        ],
        'roles' => [],
    ];
    /**
     * @var array
     */
    private $pages = [];
    /**
     * @var array
     */
    private $sections = [];
    /**
     * @var array
     */
    private $fields = [];

    /**
     * Admin constructor.
     * @param callable $option
     * @param callable $view
     */
    public function __construct( callable $option, callable $view )
    {
        $this->init_page( $view, 'options' );
        $this->init_page( $view, 'dashboard' );
        $this->init_general_section();
        $this->init_url_field( $option );
        $this->init_fallback_message_field( $option );
        $this->init_roles_field( $option );
    }

    /**
     * @param callable $view
     * @param string $name
     */
    private function init_page( callable $view, $name )
    {
        $this->pages[ $name ] = [
            'menu_slug' => Plugin::OPTION_GROUP . "_$name",
            'callback'  => function () use ( $view, $name ) {
                $view( "$name-page" );
            },
        ];
    }

    private function init_general_section()
    {
        $this->sections['general'] = [
            'title'    => __( 'General Settings', 'innocode-google-datastudio' ),
            'callback' => null,
            'page'     => Plugin::OPTION_GROUP . '_options',
        ];
    }

    /**
     * @param callable $option
     */
    private function init_url_field( callable $option )
    {
        $this->fields['url'] = [
            'title'    => __( 'Embed URL', 'innocode-google-datastudio' ),
            'callback' => function () use ( $option ) {
                printf(
                    '<input type="url" id="%s" name="%s" value="%s" class="large-text">
<p class="description">%s</p>',
                    esc_attr( Plugin::OPTION_GROUP . '-url' ),
                    esc_attr( Plugin::OPTION_GROUP . '_url' ),
                    esc_attr( $option( 'url' ) ),
                    __( 'Insert copied URL from <a href="https://datastudio.google.com/" target="_blank" rel="noopener noreferrer">Data Studio</a>.', 'innocode-google-datastudio' )
                );
            },
            'page'     => Plugin::OPTION_GROUP . '_options',
            'section'  => 'general',
        ];
    }

    /**
     * @param callable $option
     */
    private function init_fallback_message_field( callable $option )
    {
        $this->fields['fallback_message'] = [
            'title'    => __( 'Fallback Message', 'innocode-google-datastudio' ),
            'callback' => function () use ( $option ) {
                wp_editor(
                    $option( 'fallback_message' ),
                    Plugin::OPTION_GROUP . '-fallback_message',
                    [
                        'media_buttons' => false,
                        'textarea_name' => Plugin::OPTION_GROUP . '_fallback_message',
                        'textarea_rows' => 10,
                        'teeny'         => true,
                    ]
                );
                printf(
                    '<p class="description">%s</p>',
                    sprintf(
                        __(
                            'It\'s a default text which will be displayed in case when no <a href="%s">Embed URL</a> is set.',
                            'innocode-google-datastudio'
                        ),
                        esc_attr( '#' . Plugin::OPTION_GROUP . '-url' )
                    )
                );
            },
            'page'     => Plugin::OPTION_GROUP . '_options',
            'section'  => 'general',
        ];
    }

    /**
     * @param callable $option
     */
    private function init_roles_field( callable $option )
    {
        $this->fields['roles'] = [
            'title'    => __( 'Roles', 'innocode-google-datastudio' ),
            'callback' => function () use ( $option ) {
                $roles = $option( 'roles' );
                $role_names = wp_roles()->role_names;

                printf(
                    '<ul>%s</ul><p class="description">%s</p>',
                    array_reduce(
                        array_keys( $role_names ),
                        function ( $list, $role ) use ( $roles, $role_names ) {
                            return $list . sprintf(
                                '<li><label><input type="checkbox" id="%s" name="%s" value="%s" %s %s> %s%s</label></li>',
                                esc_attr( Plugin::OPTION_GROUP . '-roles-' . $role ),
                                esc_attr( Plugin::OPTION_GROUP . '_roles[]' ),
                                esc_attr( $role ),
                                in_array( $role, $roles ) ? 'checked' : '',
                                $role == 'administrator' ? 'disabled' : '',
                                esc_html( $role_names[ $role ] ),
                                $role == 'administrator'
                                    ? sprintf(
                                    '<em>(%s)</em>',
                                    __( 'always active', 'innocode-google-datastudio' )
                                )
                                    : ''
                            );
                        },
                        ''
                    ),
                    __(
                        'Only users with these roles are able to open <strong>Dashboard</strong>.',
                        'innocode-google-datastudio'
                    )
                );
            },
            'page'     => Plugin::OPTION_GROUP . '_options',
            'section'  => 'general',
        ];
    }

    /**
     * @return array
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function get_pages()
    {
        return $this->pages;
    }

    /**
     * @return array
     */
    public function get_sections()
    {
        return $this->sections;
    }

    /**
     * @return array
     */
    public function get_fields()
    {
        return $this->fields;
    }

    public function register_settings()
    {
        foreach ( $this->get_settings() as $setting => $args ) {
            register_setting(
                Plugin::OPTION_GROUP,
                Plugin::OPTION_GROUP . "_$setting",
                $args
            );
        }
    }

    public function add_pages()
    {
        foreach ( $this->get_pages() as $name => $page ) {
            $function = "add_{$name}_page";
            $function(
                __( 'Google Data Studio', 'innocode-google-datastudio' ),
                __( 'Google Data Studio', 'innocode-google-datastudio' ),
                'manage_options',
                $page['menu_slug'],
                $page['callback']
            );
        }
    }

    public function add_sections()
    {
        foreach ( $this->get_sections() as $id => $section ) {
            add_settings_section(
                $id,
                $section['title'],
                $section['callback'],
                $section['page']
            );
        }
    }

    public function add_fields()
    {
        foreach ( $this->get_fields() as $name => $field ) {
            $id = Plugin::OPTION_GROUP . "-$name";

            add_settings_field(
                $id,
                $field['title'],
                $field['callback'],
                $field['page'],
                $field['section'],
                [ 'label_for' => $id ]
            );
        }
    }

    public function add_notices()
    {
        if ( get_current_screen()->id != 'settings_page_' . Plugin::OPTION_GROUP . '_options' ) {
            return;
        }

        printf(
            '<div class="notice notice-success"><p>%s</p></div>',
            sprintf(
                __( '<strong>Dashboard</strong> is available <a href="%s">here</a>.' ),
                admin_url(
                    'index.php?page=' . Plugin::OPTION_GROUP . '_dashboard'
                )
            )
        );
    }

    public function remove_dashboard_notices()
    {
        if ( get_current_screen()->id != 'dashboard_page_' . Plugin::OPTION_GROUP . '_dashboard' ) {
            return;
        }

        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
    }
}
