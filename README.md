# WordPress REST API Registration Library

A comprehensive PHP library for registering and managing WordPress REST API endpoints programmatically. This library provides a robust solution for creating and managing REST API endpoints with support for schema validation and route management.

## Features

- 🚀 Simple REST API endpoint registration
- 🔄 Automatic schema validation
- 📝 Support for all HTTP methods
- 🛠️ Simple utility functions for quick implementation
- ✅ Comprehensive error handling
- 🔍 Debug logging support

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher

## Installation

You can install the package via composer:

```bash
composer require arraypress/wp-register-rest
```

## Basic Usage

Here's a simple example of registering REST API endpoints:

```php
// Define your endpoints
$endpoints = [
	'/items'             => [
		'methods'  => 'GET',
		'callback' => 'get_items_callback',
		'args'     => [
			'page' => [
				'type'    => 'integer',
				'default' => 1
			]
		]
	],
	'/items/(?P<id>\d+)' => [
		'methods'  => 'GET,POST,DELETE',
		'callback' => 'handle_single_item',
		'args'     => [
			'id' => [
				'required' => true,
				'type'     => 'integer'
			]
		]
	]
];

// Register endpoints with a namespace
register_rest_endpoints( 'my-plugin/v1', $endpoints );
```

## Configuration Options

Each endpoint can be configured with:

| Option | Type | Description |
|--------|------|-------------|
| methods | string/array | HTTP methods (GET, POST, etc.) |
| callback | callable | Function to handle the request |
| permission_callback | callable | Function for permission checks |
| args | array | Argument schema and validation |
| schema | callable/array | Response schema |

## Advanced Usage

### Schema Validation

Create endpoints with schema validation:

```php
$endpoints = [
	'/products' => [
		'methods'             => 'POST',
		'callback'            => 'create_product',
		'args'                => [
			'name'  => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return strlen( $value ) <= 100;
				}
			],
			'price' => [
				'required' => true,
				'type'     => 'number',
				'minimum'  => 0
			]
		],
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	]
];
```

### Multiple HTTP Methods

Handle different HTTP methods for the same endpoint:

```php
$endpoints = [
	'/order/(?P<id>\d+)' => [
		'methods'  => 'GET,PUT,DELETE',
		'callback' => function ( $request ) {
			switch ( $request->get_method() ) {
				case 'GET':
					return get_order( $request['id'] );
				case 'PUT':
					return update_order( $request['id'], $request->get_params() );
				case 'DELETE':
					return delete_order( $request['id'] );
			}
		},
		'args'     => [
			'id' => [
				'required' => true,
				'type'     => 'integer'
			]
		]
	]
];
```

### Using Response Schema

Define response schema for your endpoints:

```php
$endpoints = [
	'/products' => [
		'methods'  => 'GET',
		'callback' => 'get_products',
		'schema'   => function () {
			return [
				'$schema' => 'http://json-schema.org/draft-04/schema#',
				'title'   => 'products',
				'type'    => 'array',
				'items'   => [
					'type'       => 'object',
					'properties' => [
						'id'    => [
							'type'        => 'integer',
							'description' => 'Product ID'
						],
						'name'  => [
							'type'        => 'string',
							'description' => 'Product name'
						],
						'price' => [
							'type'        => 'number',
							'description' => 'Product price'
						]
					]
				]
			];
		}
	]
];
```

## Utility Functions

Global helper functions for easy access:

```php
// Register REST endpoints
register_rest_endpoints( $namespace, $endpoints );

// Get endpoint URL
$url = get_rest_endpoint_url( $namespace, '/endpoint' );

// Validate data against schema
$validation = validate_rest_schema( $schema, $data );
```

## Working with Requests

Example request handling:

```php
function handle_product_request( \WP_REST_Request $request ) {
	// Get URL parameters
	$product_id = $request['id'];

	// Get query parameters
	$fields = $request->get_param( 'fields' );

	// Get body parameters
	$data = $request->get_json_params();

	// Return response
	return rest_ensure_response( [
		'id'     => $product_id,
		'fields' => $fields,
		'data'   => $data
	] );
}
```

## Debug Mode

Debug logging is enabled when WP_DEBUG is true:

```php
// Logs will include:
// - Endpoint registration
// - Schema validation
// - Request handling
// - Error messages
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the GPL2+ License. See the LICENSE file for details.

## Support

For support, please use the [issue tracker](https://github.com/arraypress/wp-register-rest/issues).