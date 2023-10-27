<?php
/**
 * Copyright (c) 2023 Geniem Oy.
 */

namespace Tms\Plugin\PlaceOfBusinessSync;

/**
 * Class Sync
 *
 * @package Tms\Plugin\PlaceOfBusinessSync
 */
class Sync {

    /**
     * Entity API ID.
     */
    public const ENTITY_API_ID = 'tamperefi_api_id';

    /**
     * Fetch entities from API
     *
     * @param string $lang API language.
     *
     * @return array|null
     */
    public function fetch_entities( string $lang = 'fi' ): ?array {
        $base_url = env( 'TAMPERE_API_URL' ) . 'sites/default/files/api_json';
        $endpoint = sprintf( '%s/place_of_business_%s.json', $base_url, $lang );

        \WP_CLI::log( sprintf( 'Fetching entities from %s', $endpoint ) );

        $basic_auth_key = env( 'TAMPERE_API_AUTH' );

        $response = wp_remote_get(
            $endpoint,
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $basic_auth_key ),
                ],
            ]
        );

        return 200 === wp_remote_retrieve_response_code( $response )
            ? json_decode( wp_remote_retrieve_body( $response ) )
            : null;
    }

    /**
     * Run the sync.
     *
     * @param string $from_lang The API language to sync from.
     * @param string $to_lang   The WordPress language to sync to.
     *
     * @return void
     */
    public function run( string $from_lang = 'fi', string $to_lang = 'fi' ): void {
        \WP_CLI::log( sprintf( 'Syncing from %s to %s', $from_lang, $to_lang ) );
        $api_entities = $this->fetch_entities( $from_lang );

        if ( empty( $api_entities ) ) {
            // Empty API response is to be treated as API error
            \WP_CLI::log( 'Nothing to sync. Exiting...' );

            return;
        }

        $wp_entities        = $this->fetch_wp_entities( $from_lang );
        $api_entity_id_list = [];
        $to_be_created      = [];
        $to_be_updated      = [];
        $to_be_deleted      = [];

        foreach ( $api_entities as $api_entity ) {
            $normalized_api_entity = $this->normalize_api_entity( $api_entity );

            if ( ! isset( $wp_entities[ $api_entity->id . '-' . $from_lang ] ) ) {
                $to_be_created[] = $normalized_api_entity;
            }
            else {
                $to_be_updated[] = [
                    'api_entity' => $normalized_api_entity,
                    'wp_id'      => $wp_entities[ $api_entity->id . '-' . $from_lang ],
                ];
            }

            $api_entity_id_list[] = $api_entity->id . '-' . $from_lang;
        }

        $this->create_entities( $to_lang, $to_be_created );
        $this->update_entities( $to_be_updated );

        if ( empty( $wp_entities ) ) {
            return;
        }

        foreach ( $wp_entities as $api_id => $wp_user ) {
            if ( ! in_array( $api_id, $api_entity_id_list, true ) ) {
                $to_be_deleted[] = $wp_user;
            }
        }

        $this->delete_entities( $to_be_deleted );
    }

    /**
     * Create entities
     *
     * @param string $to_lang       The WordPress language to sync to.
     * @param array  $to_be_created Entities to be created.
     *
     * @return void
     */
    protected function create_entities( string $to_lang, array $to_be_created = [] ): void {
        if ( empty( $to_be_created ) ) {
            return;
        }

        \WP_CLI::log( sprintf( 'Creating %s entities for lang %s', count( $to_be_created ), $to_lang ) );

        foreach ( $to_be_created as $item ) {
            if ( empty( $item['post_title'] ) ) {
                continue;
            }

            $id = wp_insert_post( [
                'post_title'   => $item['post_title'],
                'post_type'    => PlaceOfBusiness::SLUG,
                'post_content' => '',
                'post_status'  => 'publish',
                'lang'         => $to_lang,
            ] );

            if ( function_exists( 'pll_set_post_language' ) ) {
                pll_set_post_language( $id, $to_lang );
            }

            if ( is_wp_error( $id ) || $id === 0 ) {
                error_log( 'Insert failed for: ' . $item['meta'][ self::ENTITY_API_ID ] );

                continue;
            }

            $this->update_entity_meta( $id, $item['meta'] );
        }
    }

    /**
     * Update entities
     *
     * @param array $to_be_updated Array of entities to be updated.
     *
     * @return void
     */
    protected function update_entities( array $to_be_updated = [] ): void {
        if ( empty( $to_be_updated ) ) {
            return;
        }

        printf( "Updating %s entities...\n", count( $to_be_updated ) );

        foreach ( $to_be_updated as $item ) {
            $id = wp_update_post( [
                'ID'         => $item['wp_id'],
                'post_title' => $item['api_entity']['post_title'],
            ] );

            if ( is_wp_error( $id ) || $id === 0 ) {
                error_log( 'Update failed for: ' . $item['api_entity'][ self::ENTITY_API_ID ] );

                continue;
            }

            $this->update_entity_meta( $id, $item['api_entity']['meta'] );
        }
    }

    /**
     * Update entity meta
     *
     * @param int   $id   \WP_Post ID.
     * @param array $meta Entity meta.
     *
     * @return void
     */
    protected function update_entity_meta( int $id, array $meta = [] ): void {
        if ( empty( $meta ) ) {
            return;
        }

        foreach ( $meta as $meta_key => $meta_value ) {
            update_field( $meta_key, $meta_value, $id );
        }
    }

    /**
     * Delete entities
     *
     * @param array $to_be_deleted Array of entities.
     *
     * @return void
     */
    protected function delete_entities( array $to_be_deleted = [] ): void {
        if ( empty( $to_be_deleted ) ) {
            return;
        }

        foreach ( $to_be_deleted as $item ) {
            wp_delete_post( $item, true );
        }
    }

    /**
     * Normalize single API response object for WP
     *
     * @param object $entity Entity from API.
     *
     * @return array
     */
    protected function normalize_api_entity( object $entity ): array {
        $normalized = [
            'post_title' => $entity->title,
            'meta'       => [
                static::ENTITY_API_ID    => $entity->id . '-' . $entity->langcode,
                'title'                  => $entity->title ?? '',
                'summary'                => $entity->field_summary ?? '',
                'description'            => $entity->field_body_md ?? '',
                'additional_information' => $entity->field_additional_information ?? '',
                'phone_repeater'         => [],
                'mail_address_street'    => $entity->field_address_postal->address_line1 ?? '',
                'mail_address_zip_code'  => $entity->field_address_postal->postal_code ?? '',
                'mail_address_city'      => $entity->field_address_postal->locality ?? '',
            ],
        ];

        $normalized = $this->handle_phone_numbers( $normalized, $entity );

        foreach ( $normalized as $key => $value ) {
            if ( empty( $value ) ) {
                unset( $normalized[ $key ] );
            }
        }

        return $normalized;
    }

    /**
     * Handle contact phone numbers
     *
     * @param array  $data   Normalized contact data.
     * @param object $fields API contact fields.
     *
     * @return array
     */
    private function handle_phone_numbers( array $data, $fields ): array {
        if ( ! empty( $fields->field_phone ) ) {
            $data['phone_repeater'][] = [
                'phone_text'   => $fields->phone_supplementary ?? '',
                'phone_number' => $fields->field_phone,
            ];
        }

        if ( ! empty( $fields->field_additinal_phones ) ) {
            foreach ( $fields->field_additinal_phones as $phone ) {
                $data['phone_repeater'][] = [
                    'phone_text'   => $phone->telephone_supplementary ?? '',
                    'phone_number' => $phone->telephone_number,
                ];
            }
        }

        return $data;
    }

    /**
     * Fetch WP entities
     * Return array keys are API ID's
     *
     * @param string $from_lang The language used for syncing.
     *
     * @return array
     */
    protected function fetch_wp_entities( string $from_lang ): array {
        $the_query = new \WP_Query( [
            'post_type'      => PlaceOfBusiness::SLUG,
            'posts_per_page' => - 1,
            'lang'           => $from_lang,
        ] );

        if ( ! $the_query->have_posts() ) {
            return [];
        }

        $entities = [];

        foreach ( $the_query->posts as $entity ) {
            $api_id = get_field( static::ENTITY_API_ID, $entity->ID );

            if ( ! empty( $api_id ) ) {
                $entities[ $api_id ] = $entity->ID;
            }
        }

        return $entities;
    }
}
