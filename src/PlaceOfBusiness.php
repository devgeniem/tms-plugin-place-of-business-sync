<?php
/**
 *  Copyright (c) 2023. Geniem Oy
 */

namespace Tms\Plugin\PlaceOfBusinessSync;

use Geniem\ACF\Exception;
use Geniem\ACF\Group;
use Geniem\ACF\Field;
use Geniem\ACF\RuleGroup;
use TMS\Theme\Base\Logger;

/**
 * Class PlaceOfBusiness
 */
class PlaceOfBusiness {

    /**
     * This defines the slug of this post type.
     */
    public const SLUG = 'placeofbusiness-cpt';

    /**
     * This defines what is shown in the url. This can
     * be different than the slug which is used to register the post type.
     *
     * @var string
     */
    private $url_slug = '';

    /**
     * Define the CPT description
     *
     * @var string
     */
    private $description = '';

    /**
     * This is used to position the post type menu in admin.
     *
     * @var int
     */
    private $menu_order = 40;

    /**
     * This defines the CPT icon.
     *
     * @var string
     */
    private $icon = 'dashicons-admin-site';

    /**
     * Constructor
     */
    public function __construct() {
        // Make url slug translatable
        $this->url_slug = _x( 'place-of-business', 'theme CPT slugs', 'tms-plugin-place-of-business-sync' );

        // Make possible description text translatable.
        $this->description = _x( 'CPT Description', 'theme CPT', 'tms-plugin-place-of-business-sync' );

        add_action( 'init', \Closure::fromCallable( [ $this, 'register' ] ), 1, 0 );
        add_action( 'acf/init', \Closure::fromCallable( [ $this, 'fields' ] ), 50, 0 );
    }

    /**
     * Add hooks and filters from this controller
     *
     * @return void
     */
    public function hooks() : void {
        add_filter(
            'use_block_editor_for_post_type',
            \Closure::fromCallable( [ $this, 'disable_gutenberg' ] ),
            10,
            2
        );
    }

    /**
     * This registers the post type.
     *
     * @return void
     */
    private function register() {
        $labels = [
            'name'                  => 'Toimipaikat',
            'singular_name'         => 'Toimipaikka',
            'menu_name'             => 'Toimipaikat',
            'name_admin_bar'        => 'Toimipaikka',
            'archives'              => 'Arkistot',
            'attributes'            => 'Ominaisuudet',
            'parent_item_colon'     => 'Vanhempi:',
            'all_items'             => 'Kaikki',
            'add_new_item'          => 'Lisää uusi',
            'add_new'               => 'Lisää uusi',
            'new_item'              => 'Uusi',
            'edit_item'             => 'Muokkaa',
            'update_item'           => 'Päivitä',
            'view_item'             => 'Näytä',
            'view_items'            => 'Näytä kaikki',
            'search_items'          => 'Etsi',
            'not_found'             => 'Ei löytynyt',
            'not_found_in_trash'    => 'Ei löytynyt roskakorista',
            'featured_image'        => 'Kuva',
            'set_featured_image'    => 'Aseta kuva',
            'remove_featured_image' => 'Poista kuva',
            'use_featured_image'    => 'Käytä kuvana',
            'insert_into_item'      => 'Aseta julkaisuun',
            'uploaded_to_this_item' => 'Lisätty tähän julkaisuun',
            'items_list'            => 'Listaus',
            'items_list_navigation' => 'Listauksen navigaatio',
            'filter_items_list'     => 'Suodata listaa',
        ];

        $args = [
            'label'               => $labels['name'],
            'description'         => '',
            'labels'              => $labels,
            'supports'            => [ 'title', 'revisions' ],
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => $this->menu_order,
            'menu_icon'           => $this->icon,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => false,
            'capability_type'     => 'material',
            'query_var'           => true,
            'map_meta_cap'        => true,
            'show_in_rest'        => true,
        ];

        $args = apply_filters(
            'tms/post_type/' . static::SLUG . '/args',
            $args
        );

         register_post_type( static::SLUG, $args );
    }

    /**
     * Register fields
     */
    protected function fields() {
        try {
            $field_group = ( new Group( 'Toimipaikan tiedot' ) )
                ->set_key( 'fg_place_of_business' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'post_type', '==', static::SLUG );

            $field_group
                ->add_rule_group( $rule_group )
                ->set_position( 'normal' )
                ->set_hidden_elements(
                    [
                        'discussion',
                        'comments',
                        'format',
                        'send-trackbacks',
                    ]
                );

            $strings = [
                'title'       => [
                    'label'        => 'Otsikko',
                    'instructions' => '',
                ],
                'summary' => [
                    'label'        => 'Kuvauksen tiivistelmä',
                    'instructions' => '',
                ],
                'description' => [
                    'label'        => 'Kuvaus',
                    'instructions' => '',
                ],
                'phone_repeater'        => [
                    'label'        => 'Puhelinnumerot',
                    'instructions' => '',
                ],
                'phone_number'        => [
                    'label'        => 'Puhelinnumero',
                    'instructions' => '',
                ],
                'phone_text'        => [
                    'label'        => 'Puhelinnumeron selite',
                    'instructions' => '',
                ],
                'email'        => [
                    'label'        => 'Sähköposti',
                    'instructions' => '',
                ],
                'additional_info'        => [
                    'label'        => 'Lisätiedot',
                    'instructions' => '',
                ],
                'mail_address_street'        => [
                    'label'        => 'Katuosoite',
                    'instructions' => '',
                ],
                'mail_address_zip_code'        => [
                    'label'        => 'Postinumero',
                    'instructions' => '',
                ],
                'mail_address_city'        => [
                    'label'        => 'Postitoimipaikka',
                    'instructions' => '',
                ],
            ];

            $key = $field_group->get_key();

            $title_field = ( new Field\Text( $strings['title']['label'] ) )
                ->set_key( "${key}_title" )
                ->set_name( 'title' )
                ->set_instructions( $strings['title']['instructions'] );

            $summary_field = ( new Field\Textarea( $strings['summary']['label'] ) )
                ->set_key( "${key}_summary" )
                ->set_name( 'summary' )
                ->set_instructions( $strings['summary']['instructions'] );

            $description_field = ( new Field\ExtendedWysiwyg( $strings['description']['label'] ) )
                ->set_key( "${key}_description" )
                ->set_name( 'description' )
                ->set_tabs( 'visual' )
                ->set_toolbar(
                    [
                        'bold',
                        'italic',
                        'link',
                        'pastetext',
                        'removeformat',
                    ]
                )
                ->disable_media_upload()
                ->set_instructions( $strings['description']['instructions'] );

            $phone_repeater_field = ( new Field\Repeater( $strings['phone_repeater']['label'] ) )
                ->set_key( "${key}_phone_repeater" )
                ->set_name( 'phone_repeater' )
                ->set_instructions( $strings['phone_repeater']['instructions'] );

            $phone_number_field = ( new Field\Text( $strings['phone_number']['label'] ) )
                ->set_key( "${key}_phone_number" )
                ->set_name( 'phone_number' )
                ->set_instructions( $strings['phone_number']['instructions'] );

            $phone_text_field = ( new Field\Text( $strings['phone_text']['label'] ) )
                ->set_key( "${key}_phone_text" )
                ->set_name( 'phone_text' )
                ->set_instructions( $strings['phone_text']['instructions'] );

            $phone_repeater_field->add_fields([
                $phone_number_field,
                $phone_text_field,
            ]);

            $additional_info_field = ( new Field\ExtendedWysiwyg( $strings['additional_info']['label'] ) )
                ->set_key( "${key}_additional_info" )
                ->set_name( 'additional_info' )
                ->set_tabs( 'visual' )
                ->set_toolbar(
                    [
                        'bold',
                        'italic',
                        'link',
                        'pastetext',
                        'removeformat',
                    ]
                )
                ->disable_media_upload()
                ->set_instructions( $strings['additional_info']['instructions'] );

            $email_field = ( new Field\Text( $strings['email']['label'] ) )
                ->set_key( "${key}_email" )
                ->set_name( 'email' )
                ->set_instructions( $strings['email']['instructions'] );

            $street_field = ( new Field\Text( $strings['mail_address_street']['label'] ) )
                ->set_key( "${key}_mail_address_street" )
                ->set_name( 'mail_address_street' )
                ->set_wrapper_width( 50 )
                ->set_instructions( $strings['mail_address_street']['instructions'] );

            $zip_code_field = ( new Field\Text( $strings['mail_address_zip_code']['label'] ) )
                ->set_key( "${key}_mail_address_zip_code" )
                ->set_name( 'mail_address_zip_code' )
                ->set_wrapper_width( 50 )
                ->set_instructions( $strings['mail_address_zip_code']['instructions'] );

            $city_field = ( new Field\Text( $strings['mail_address_city']['label'] ) )
                ->set_key( "${key}_mail_address_city" )
                ->set_name( 'mail_address_city' )
                ->set_wrapper_width( 50 )
                ->set_instructions( $strings['mail_address_city']['instructions'] );

            $field_group->add_fields(
                apply_filters(
                    'tms/acf/group/' . $field_group->get_key() . '/fields',
                    [
                        $title_field,
                        $summary_field,
                        $description_field,
                        $email_field,
                        $phone_repeater_field,
                        $additional_info_field,
                        $street_field,
                        $zip_code_field,
                        $city_field,
                    ]
                )
            );

            $field_group = apply_filters(
                'tms/acf/group/' . $field_group->get_key(),
                $field_group
            );

            $field_group->register();
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTraceAsString() );
        }
    }

    /**
     * Disable Gutenberg for this post type
     *
     * @param boolean $current_status The current Gutenberg status.
     * @param string  $post_type      The post type.
     *
     * @return boolean
     */
    protected function disable_gutenberg( bool $current_status, string $post_type ) : bool {
        return $post_type === static::SLUG ? false : $current_status; // phpcs:ignore
    }
}
