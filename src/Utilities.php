<?php
/**
 * REST API Registration Helper Functions
 *
 * @package     ArrayPress\WP\Register
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\WP\Register\Rest;

if ( ! function_exists( 'register_rest_endpoints' ) ):
	/**
	 * Helper function to register WordPress REST API endpoints.
	 *
	 * Example usage:
	 * ```php
	 * $endpoints = [
	 *     '/items' => [
	 *         'methods'  => 'GET',
	 *         'callback' => 'get_items_callback',
	 *         'permission_callback' => function() {
	 *             return current_user_can('read');
	 *         },
	 *         'args' => [
	 *             'page' => [
	 *                 'type'    => 'integer',
	 *                 'default' => 1
	 *             ]
	 *         ]
	 *     ],
	 *     '/items/(?P<id>\d+)' => [
	 *         'methods'  => 'GET,POST,DELETE',
	 *         'callback' => 'handle_single_item',
	 *         'args'     => [
	 *             'id' => [
	 *                 'required' => true,
	 *                 'type'    => 'integer'
	 *             ]
	 *         ]
	 *     ]
	 * ];
	 *
	 * register_rest_endpoints('my-plugin/v1', $endpoints, 'my-plugin');
	 * ```
	 *
	 * @param string $namespace API namespace (e.g., 'my-plugin/v1')
	 * @param array  $endpoints Array of endpoints to register
	 * @param string $prefix    Optional prefix for internal use
	 *
	 * @return bool True on success, false on failure
	 */
	function register_rest_endpoints( string $namespace, array $endpoints, string $prefix = '' ): bool {
		try {
			$rest = new Rest( $namespace, $prefix );

			return ! empty( $endpoints ) && $rest->add_endpoints( $endpoints ) instanceof Rest;
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'REST registration failed: %s', $e->getMessage() ) );
			}

			return false;
		}
	}
endif;

if ( ! function_exists( 'get_rest_endpoint_url' ) ):
	/**
	 * Helper function to get a REST API endpoint URL.
	 *
	 * Example usage:
	 * ```php
	 * $url = get_rest_endpoint_url('my-plugin/v1', '/items');
	 * ```
	 *
	 * @param string $namespace API namespace
	 * @param string $endpoint  Endpoint route
	 *
	 * @return string Full REST API URL
	 */
	function get_rest_endpoint_url( string $namespace, string $endpoint ): string {
		return get_rest_url( null, trailingslashit( $namespace ) . ltrim( $endpoint, '/' ) );
	}
endif;

if ( ! function_exists( 'validate_rest_schema' ) ):
	/**
	 * Helper function to validate data against a REST schema.
	 *
	 * Example usage:
	 * ```php
	 * $schema = [
	 *     'name' => [
	 *         'type' => 'string',
	 *         'required' => true
	 *     ]
	 * ];
	 * $data = ['name' => 'John'];
	 * $result = validate_rest_schema($schema, $data);
	 * ```
	 *
	 * @param array $schema Schema to validate against
	 * @param array $data   Data to validate
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	function validate_rest_schema( array $schema, array $data ) {
		// Get REST Server instance
		$server = rest_get_server();

		// Create params from schema
		$params = [];
		foreach ( $schema as $key => $args ) {
			if ( isset( $data[ $key ] ) ) {
				$params[ $key ] = $data[ $key ];
			}
		}

		// Validate each parameter
		$errors = new WP_Error();

		foreach ( $params as $param => $value ) {
			if ( ! isset( $schema[ $param ] ) ) {
				continue;
			}

			$valid = rest_validate_value_from_schema( $value, $schema[ $param ], $param );
			if ( is_wp_error( $valid ) ) {
				$errors->add(
					$valid->get_error_code(),
					sprintf( '%s: %s', $param, $valid->get_error_message() ),
					[ 'param' => $param ]
				);
			}
		}

		if ( count( $errors->get_error_codes() ) > 0 ) {
			return $errors;
		}

		return true;
	}
endif;