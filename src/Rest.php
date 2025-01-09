<?php
/**
 * REST API Endpoints Registration Manager
 *
 * @package     ArrayPress\WP\Register
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WP\Register;

// Exit if accessed directly
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest
 *
 * Manages WordPress REST API endpoint registration and management.
 *
 * @since 1.0.0
 */
class Rest {

	/**
	 * Collection of endpoints to be registered
	 *
	 * @var array
	 */
	private array $endpoints = [];

	/**
	 * Option prefix for storing endpoint data
	 *
	 * @var string
	 */
	private string $prefix = '';

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private string $namespace = '';

	/**
	 * Debug mode status
	 *
	 * @var bool
	 */
	private bool $debug = false;

	/**
	 * Constructor
	 *
	 * @param string $namespace Optional API namespace
	 * @param string $prefix    Optional prefix for debugging
	 */
	public function __construct( string $namespace = '', string $prefix = '' ) {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		if ( ! empty( $namespace ) ) {
			$this->set_namespace( $namespace );
		}

		if ( ! empty( $prefix ) ) {
			$this->set_prefix( $prefix );
		}

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Set the prefix
	 *
	 * @param string $prefix The prefix to use
	 *
	 * @return self
	 */
	public function set_prefix( string $prefix ): self {
		$this->prefix = $prefix;

		return $this;
	}

	/**
	 * Set the API namespace
	 *
	 * @param string $namespace The API namespace
	 *
	 * @return self
	 */
	public function set_namespace( string $namespace ): self {
		if ( ! $this->is_valid_namespace( $namespace ) ) {
			$this->log( sprintf( 'Invalid API namespace: %s', $namespace ) );

			return $this;
		}

		$this->namespace = $namespace;

		return $this;
	}

	/**
	 * Add endpoints to be registered
	 *
	 * @param array $endpoints Array of endpoints
	 *
	 * @return self
	 */
	public function add_endpoints( array $endpoints ): self {
		foreach ( $endpoints as $route => $endpoint ) {
			$this->add_endpoint( $route, $endpoint );
		}

		return $this;
	}

	/**
	 * Add a single endpoint
	 *
	 * @param string $route    Endpoint route
	 * @param array  $endpoint Endpoint configuration
	 *
	 * @return self
	 */
	public function add_endpoint( string $route, array $endpoint ): self {
		if ( ! $this->is_valid_route( $route ) ) {
			$this->log( sprintf( 'Invalid endpoint route: %s', $route ) );

			return $this;
		}

		// Ensure required fields
		foreach ( [ 'methods', 'callback' ] as $required ) {
			if ( ! isset( $endpoint[ $required ] ) ) {
				$this->log( sprintf( 'Missing required field "%s" for endpoint: %s', $required, $route ) );

				return $this;
			}
		}

		// Parse methods if string
		if ( is_string( $endpoint['methods'] ) ) {
			$endpoint['methods'] = $this->parse_methods( $endpoint['methods'] );
		}

		// Validate callback
		if ( ! is_callable( $endpoint['callback'] ) ) {
			$this->log( sprintf( 'Invalid callback for endpoint: %s', $route ) );

			return $this;
		}

		// Parse and validate schema if provided
		if ( isset( $endpoint['schema'] ) && is_callable( $endpoint['schema'] ) ) {
			$endpoint['schema'] = call_user_func( $endpoint['schema'] );
		}

		$this->endpoints[ $route ] = wp_parse_args( $endpoint, [
			'methods'             => [],
			'callback'            => null,
			'permission_callback' => '__return_true',
			'args'                => [],
			'schema'              => null
		] );

		return $this;
	}

	/**
	 * Register endpoints with WordPress
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		if ( empty( $this->namespace ) || empty( $this->endpoints ) ) {
			return;
		}

		foreach ( $this->endpoints as $route => $endpoint ) {
			register_rest_route(
				$this->namespace,
				$route,
				$endpoint
			);
			$this->log( sprintf( 'Registered endpoint: %s/%s', $this->namespace, $route ) );
		}
	}

	/**
	 * Parse HTTP methods string into array
	 *
	 * @param string $methods Comma-separated HTTP methods
	 *
	 * @return array Array of WP_REST methods
	 */
	protected function parse_methods( string $methods ): array {
		$method_map = [
			'GET'     => WP_REST_Server::READABLE,
			'POST'    => WP_REST_Server::CREATABLE,
			'PUT'     => WP_REST_Server::EDITABLE,
			'PATCH'   => WP_REST_Server::EDITABLE,
			'DELETE'  => WP_REST_Server::DELETABLE,
			'OPTIONS' => WP_REST_Server::READABLE,
		];

		$parsed  = [];
		$methods = array_map( 'trim', explode( ',', strtoupper( $methods ) ) );

		foreach ( $methods as $method ) {
			if ( isset( $method_map[ $method ] ) ) {
				$parsed[] = $method_map[ $method ];
			}
		}

		return $parsed;
	}

	/**
	 * Validate API namespace
	 *
	 * @param string $namespace Namespace to validate
	 *
	 * @return bool Whether the namespace is valid
	 */
	protected function is_valid_namespace( string $namespace ): bool {
		return (bool) preg_match( '/^[a-zA-Z0-9_-]+\/v\d+$/', $namespace );
	}

	/**
	 * Validate endpoint route
	 *
	 * @param string $route Route to validate
	 *
	 * @return bool Whether the route is valid
	 */
	protected function is_valid_route( string $route ): bool {
		return (bool) preg_match( '/^\/[a-zA-Z0-9\/_-]+$/', $route );
	}

	/**
	 * Log debug message
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional context
	 *
	 * @return void
	 */
	protected function log( string $message, array $context = [] ): void {
		if ( $this->debug ) {
			$prefix = $this->prefix ? "[{$this->prefix}] " : '';
			error_log( sprintf(
				'%sREST: %s %s',
				$prefix,
				$message,
				$context ? json_encode( $context ) : ''
			) );
		}
	}
}