<?php
/**
 * Copyright (c) 2023 Geniem Oy.
 */

namespace Tms\Plugin\PlaceOfBusinessSync;

/**
 * Class Plugin
 *
 * @package Tms\Plugin\PlaceOfBusinessSync
 */
final class Plugin {

    /**
     * Default language.
     *
     * @var string
     */
    const DEFAULT_LANGUAGE = 'fi';

    /**
     * Fallback language.
     *
     * @var string
     */
    const FALLBACK_LANGUAGE = 'en';

    /**
     * Holds the singleton.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Current plugin version.
     *
     * @var string
     */
    protected $version = '';

    /**
     * Get the instance.
     *
     * @return Plugin
     */
    public static function get_instance() : Plugin {
        return self::$instance;
    }

    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $plugin_path = '';

    /**
     * The plugin root uri without trailing slash.
     *
     * @var string
     */
    protected $plugin_uri = '';

    /**
     * Get the version.
     *
     * @return string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function get_plugin_path() : string {
        return $this->plugin_path;
    }

    /**
     * Get the plugin directory uri.
     *
     * @return string
     */
    public function get_plugin_uri() : string {
        return $this->plugin_uri;
    }

    /**
     * Initialize the plugin by creating the singleton.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    public static function init( $version = '', $plugin_path = '' ) {
        if ( empty( self::$instance ) ) {
            self::$instance = new self( $version, $plugin_path );
            self::$instance->hooks();
        }
    }

    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    public static function plugin() {
        return self::$instance;
    }

    /**
     * Initialize the plugin functionalities.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    protected function __construct( $version = '', $plugin_path = '' ) {
        $this->version     = $version;
        $this->plugin_path = $plugin_path;
        $this->plugin_uri  = \plugin_dir_url( $plugin_path ) . basename( $this->plugin_path );
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() : void {
        ( new PlaceOfBusiness() );

        \add_filter(
            'pll_get_post_types',
            \Closure::fromCallable( [ $this, 'add_cpts_to_polylang' ] ),
            10,
            2
        );

        $this->cli_hooks();
    }

    /**
     * This adds the CPTs that are not public to Polylang translation.
     *
     * @param array   $post_types  The post type array.
     * @param boolean $is_settings A not used boolean flag to see if we're in settings.
     *
     * @return array The modified post_types -array.
     */
    protected function add_cpts_to_polylang( $post_types, $is_settings ) { // phpcs:ignore
        $post_types[ PlaceOfBusiness::SLUG ] = PlaceOfBusiness::SLUG;

        return $post_types;
    }

    /**
     * Define CLI hooks.
     *
     * @return void
     */
    protected function cli_hooks() : void {
        if ( ! defined( 'WP_CLI' ) || ! \WP_CLI ) {
            return;
        }

        \WP_CLI::add_command(
            'sync-place-of-business',
            [ $this, 'cli_callback' ],
            [
                'shortdesc' => 'Import place of business from Tampere.fi Drupal API to WordPress.',
                'synopsis'  => [
                    [
                        'type'        => 'assoc',
                        'name'        => 'from',
                        'description' => 'The language to import from.',
                        'optional'    => true,
                        'default'     => 'fi',
                    ],
                    [
                        'type'        => 'assoc',
                        'name'        => 'to',
                        'description' => 'The language to import to.',
                        'optional'    => true,
                        'default'     => 'fi',
                    ],
                ],
            ]
        );
    }

    /**
     * WP CLI callback for importing contacts.
     *
     * @param array $args       Arguments.
     * @param array $assoc_args Associative arguments.
     *
     * @return void
     */
    public function cli_callback( $args, $assoc_args ): void {
        $this->do_import( $assoc_args['from'], $assoc_args['to'] );
    }

    /**
     * Import contacts for each language.
     *
     * @return void
     */
    public function import() : void {
        if ( ! function_exists( 'pll_languages_list' ) ) {
            $this->do_import( self::DEFAULT_LANGUAGE, self::DEFAULT_LANGUAGE );

            return;
        }

        $languages = \pll_languages_list();

        foreach ( $languages as $from_lang ) {
            // All languages default to en, except fi.
            $to_lang = $from_lang === self::DEFAULT_LANGUAGE
                ? self::DEFAULT_LANGUAGE
                : self::FALLBACK_LANGUAGE;

            $this->do_import( $from_lang, $to_lang );
        }
    }

    /**
     * Import contacts from Tampere.fi Drupal API to WordPress.
     *
     * @param string $from_lang The API language to sync from.
     * @param string $to_lang   The WordPress language to sync to.
     *
     * @return void
     */
    public function do_import( string $from_lang = 'fi', string $to_lang = 'fi' ) : void {
        ( new Sync() )->run( $from_lang, $to_lang );
    }
}
