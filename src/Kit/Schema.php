<?php
/**
 * Reader of element.json and attribute normalizer (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@33c7c19 (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * One element.json is the single attribute schema of an element. Both adapters
 * (block, Elementor) read it, and every value reaches render.php through normalize().
 */
final class Schema {

	/**
	 * Attribute types the kit understands.
	 */
	const TYPES = array( 'string', 'boolean', 'integer', 'number', 'array' );

	/**
	 * Controls the generated editors understand.
	 */
	const CONTROLS = array( 'text', 'textarea', 'url', 'select', 'multiselect', 'toggle', 'number', 'range' );

	/**
	 * Read and check an element.json file.
	 *
	 * @param string $file Path to element.json.
	 * @return array Schema, or an empty array when the file is missing or invalid.
	 */
	public static function load( $file ) {
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$schema = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.

		if ( ! is_array( $schema ) || array() !== self::errors( $schema ) ) {
			return array();
		}

		return $schema;
	}

	/**
	 * Problems in a schema (empty when valid). Used by load() and by the tests.
	 *
	 * @param array $schema Decoded element.json.
	 * @return string[]
	 */
	public static function errors( array $schema ) {
		$errors = array();

		if ( empty( $schema['name'] ) || ! preg_match( '/^[a-z][a-z0-9-]*$/', (string) $schema['name'] ) ) {
			$errors[] = 'name: lowercase letters, digits and dashes';
		}
		if ( empty( $schema['title'] ) ) {
			$errors[] = 'title: required';
		}

		foreach ( self::attributes( $schema ) as $key => $def ) {
			if ( ! isset( $def['type'] ) || ! in_array( $def['type'], self::TYPES, true ) ) {
				$errors[] = $key . '.type: one of ' . implode( ', ', self::TYPES );
			}
			if ( isset( $def['control'] ) && ! in_array( $def['control'], self::CONTROLS, true ) ) {
				$errors[] = $key . '.control: one of ' . implode( ', ', self::CONTROLS );
			}
			if ( isset( $def['panel'] ) && ! in_array( $def['panel'], array( 'basic', 'advanced' ), true ) ) {
				$errors[] = $key . '.panel: basic or advanced';
			}
		}

		return $errors;
	}

	/**
	 * Translatable strings of a schema (title, description, keywords, emptyHint, labels, help,
	 * static option labels, string defaults marked "translate": true). Used to generate a PHP file
	 * of __() calls, so the strings reach the POT file (see K Elements bin/element-strings.php).
	 *
	 * @param array $schema Schema as read from element.json.
	 * @return string[]
	 */
	public static function strings( array $schema ) {
		$out = array();

		foreach ( array( 'title', 'description', 'emptyHint' ) as $key ) {
			if ( ! empty( $schema[ $key ] ) ) {
				$out[] = (string) $schema[ $key ];
			}
		}
		foreach ( isset( $schema['keywords'] ) ? (array) $schema['keywords'] : array() as $keyword ) {
			$out[] = (string) $keyword;
		}
		foreach ( isset( $schema['variations'] ) ? (array) $schema['variations'] : array() as $variation ) {
			if ( ! empty( $variation['title'] ) ) {
				$out[] = (string) $variation['title'];
			}
		}

		foreach ( self::attributes( $schema ) as $def ) {
			foreach ( array( 'label', 'help' ) as $key ) {
				if ( ! empty( $def[ $key ] ) ) {
					$out[] = (string) $def[ $key ];
				}
			}
			if ( ! empty( $def['translate'] ) && is_string( isset( $def['default'] ) ? $def['default'] : null ) && '' !== $def['default'] ) {
				$out[] = $def['default'];
			}
			foreach ( isset( $def['options'] ) && is_array( $def['options'] ) ? $def['options'] : array() as $value => $option ) {
				$out[] = (string) ( is_array( $option ) ? $option['label'] : $option );
			}
		}

		return array_values( array_unique( array_filter( $out, 'strlen' ) ) );
	}

	/**
	 * Attribute definitions: key => definition.
	 *
	 * @param array $schema Schema.
	 * @return array
	 */
	public static function attributes( array $schema ) {
		return isset( $schema['attributes'] ) && is_array( $schema['attributes'] ) ? $schema['attributes'] : array();
	}

	/**
	 * Default values: key => default.
	 *
	 * @param array $schema Schema.
	 * @return array
	 */
	public static function defaults( array $schema ) {
		$out = array();

		foreach ( self::attributes( $schema ) as $key => $def ) {
			$out[ $key ] = self::cast( $def, isset( $def['default'] ) ? $def['default'] : null, true );
		}

		return $out;
	}

	/**
	 * Allowed values of a select or multiselect (empty = free value).
	 *
	 * @param array $def     Attribute definition (options already resolved, see Kit::resolve_options()).
	 * @return string[]
	 */
	public static function allowed( array $def ) {
		if ( empty( $def['options'] ) || ! is_array( $def['options'] ) ) {
			return array();
		}

		return array_map(
			static function ( $option ) {
				return (string) ( is_array( $option ) ? $option['value'] : $option );
			},
			$def['options']
		);
	}

	/**
	 * Values from a block or an Elementor widget, typed and checked against the schema.
	 * Unknown keys are dropped, missing or invalid ones get the default.
	 * Elementor values are accepted as they are saved: "yes"/"" switchers, "3" numbers, { size } sliders.
	 *
	 * @param array $schema Schema (options resolved).
	 * @param array $values Raw values.
	 * @return array
	 */
	public static function normalize( array $schema, array $values ) {
		$out = array();

		foreach ( self::attributes( $schema ) as $key => $def ) {
			$default = self::cast( $def, isset( $def['default'] ) ? $def['default'] : null, true );
			$value   = array_key_exists( $key, $values ) ? self::cast( $def, $values[ $key ], false ) : null;

			$out[ $key ] = null === $value ? $default : $value;
		}

		return $out;
	}

	/**
	 * Cast one value to its attribute type; null when it cannot be used.
	 *
	 * @param array $def        Attribute definition.
	 * @param mixed $value      Raw value.
	 * @param bool  $is_default The value is the schema default (not checked against options).
	 * @return mixed
	 */
	private static function cast( array $def, $value, $is_default ) {
		$type = isset( $def['type'] ) ? $def['type'] : 'string';

		if ( is_array( $value ) && array_key_exists( 'size', $value ) && 'array' !== $type ) {
			$value = $value['size']; // Elementor slider.
		}

		if ( null === $value ) {
			return 'boolean' === $type ? false : ( 'array' === $type ? array() : null );
		}

		switch ( $type ) {
			case 'boolean':
				return self::to_bool( $value );

			case 'integer':
			case 'number':
				if ( '' === $value || ! is_numeric( $value ) ) {
					return null;
				}
				$value = 'integer' === $type ? (int) round( (float) $value ) : (float) $value;
				if ( isset( $def['min'] ) && $value < $def['min'] ) {
					$value = 'integer' === $type ? (int) $def['min'] : (float) $def['min'];
				}
				if ( isset( $def['max'] ) && $value > $def['max'] ) {
					$value = 'integer' === $type ? (int) $def['max'] : (float) $def['max'];
				}
				return $value;

			case 'array':
				if ( is_string( $value ) ) {
					$value = '' === $value ? array() : array_map( 'trim', explode( ',', $value ) );
				}
				if ( ! is_array( $value ) ) {
					return null;
				}
				$value   = array_values( array_map( 'strval', array_filter( $value, 'is_scalar' ) ) );
				$allowed = self::allowed( $def );
				if ( $allowed && ! $is_default ) {
					$value = array_values( array_intersect( $value, $allowed ) );
				}
				return $value;

			default:
				if ( ! is_scalar( $value ) ) {
					return null;
				}
				$value   = (string) $value;
				$allowed = self::allowed( $def );
				if ( $allowed && ! $is_default && ! in_array( $value, $allowed, true ) ) {
					return null;
				}
				return $value;
		}
	}

	/**
	 * Boolean from block (true/false) or Elementor ("yes"/""/"no") values.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( 'yes', '1', 'true', 'on' ), true );
		}

		return (bool) $value;
	}
}
