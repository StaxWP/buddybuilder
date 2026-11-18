<?php
/**
 * Profile fields the elements show (Member header headline and location, Member about): the pickers in both editors
 * list the site's extended profile fields, and the setup offers to create the fields a style uses when they are
 * missing (only on an explicit click). Attribute values stay field names (strings), as before.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Elements\Elements;

defined( 'ABSPATH' ) || die();

/**
 * Extended profile fields for the element pickers and the setup.
 */
final class ProfileFields {

	/**
	 * Fields the styles use, with the field type the setup creates (BuddyPress field types).
	 */
	const STANDARD = [
		'Headline' => 'textbox',
		'Location' => 'textbox',
		'About'    => 'textarea',
		'Website'  => 'url',
	];

	/** Name of the profile field group the setup creates the fields in (not the base group: no new sign-up fields). */
	const GROUP = 'Profile details';

	/**
	 * Field names of this site, in profile order (cached per request).
	 *
	 * @var string[]|null
	 */
	private static $names = null;

	/**
	 * The option sources of the element pickers ("optionsFrom" in element.json), resolved only when an element's
	 * schema is read.
	 *
	 * @param \Buddy_Builder\Kit\Kit $kit Kit.
	 */
	public static function add_sources( $kit ) {
		$kit->add_option_source( 'profile_field', [ __CLASS__, 'single_options' ] )->add_option_source( 'profile_fields', [ __CLASS__, 'multi_options' ] );
	}

	/**
	 * Names of the extended profile fields of this site, in profile order.
	 *
	 * @return string[]
	 */
	public static function names() {
		if ( null === self::$names ) {
			self::$names = [];

			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) && function_exists( 'bp_xprofile_get_groups' ) ) {
				foreach ( (array) bp_xprofile_get_groups( [ 'fetch_fields' => true ] ) as $group ) {
					foreach ( isset( $group->fields ) ? (array) $group->fields : [] as $field ) {
						self::$names[] = (string) $field->name;
					}
				}
			}

			self::$names = array_values( array_unique( self::$names ) );
		}

		return self::$names;
	}

	/**
	 * Forget the cached names (after creating fields).
	 */
	public static function flush() {
		self::$names = null;
	}

	/**
	 * Picker of one field (Member header): None, the site's fields, then the standard fields the site does not have
	 * yet (the element defaults, so a default value is never shown as an empty choice).
	 *
	 * @return array value => label
	 */
	public static function single_options() {
		$options = [ '' => __( 'None', 'stax-buddy-builder' ) ] + self::multi_options();

		foreach ( [ 'Headline', 'Location' ] as $name ) {
			if ( ! isset( $options[ $name ] ) ) {
				/* translators: %s: profile field name */
				$options[ $name ] = sprintf( __( '%s (no such field yet)', 'stax-buddy-builder' ), $name );
			}
		}

		return $options;
	}

	/**
	 * Picker of several fields (Member about): the site's fields.
	 *
	 * @return array value => label
	 */
	public static function multi_options() {
		$names = self::names();

		return $names ? array_combine( $names, $names ) : [];
	}

	/**
	 * Profile fields a block layout shows: Member header headline and location (while shown), Member about fields.
	 *
	 * @param string $content Block markup.
	 * @return string[] Field names, in order of appearance.
	 */
	public static function used_by( $content ) {
		$out = [];

		foreach ( self::blocks( parse_blocks( (string) $content ) ) as $block ) {
			$name    = substr( (string) $block['blockName'], strlen( 'buddybuilder/' ) );
			$element = Elements::get( $name );

			if ( ! $element || ! in_array( $name, [ 'member-header', 'member-about' ], true ) ) {
				continue;
			}

			$attrs = $element->normalize( isset( $block['attrs'] ) ? (array) $block['attrs'] : [] );

			if ( 'member-header' === $name ) {
				$out[] = $attrs['show_headline'] ? $attrs['headline_field'] : '';
				$out[] = $attrs['show_meta'] ? $attrs['location_field'] : '';
			} else {
				$out = array_merge( $out, array_map( 'trim', explode( ',', (string) $attrs['fields'] ) ) );
			}
		}

		return array_values( array_unique( array_filter( $out, 'strlen' ) ) );
	}

	/**
	 * BuddyBuilder blocks of a parsed tree, flattened.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private static function blocks( array $blocks ) {
		$out = [];

		foreach ( $blocks as $block ) {
			if ( 0 === strpos( (string) $block['blockName'], 'buddybuilder/' ) ) {
				$out[] = $block;
			}

			$out = array_merge( $out, self::blocks( isset( $block['innerBlocks'] ) ? (array) $block['innerBlocks'] : [] ) );
		}

		return $out;
	}

	/**
	 * The standard fields among $names that this site does not have (only these can be created).
	 *
	 * @param string[] $names Field names.
	 * @return string[]
	 */
	public static function missing( array $names ) {
		if ( ! function_exists( 'xprofile_insert_field' ) ) {
			return [];
		}

		$have = array_map( 'strtolower', self::names() );

		return array_values(
			array_filter(
				$names,
				static function ( $name ) use ( $have ) {
					return isset( self::STANDARD[ $name ] ) && ! in_array( strtolower( $name ), $have, true );
				}
			)
		);
	}

	/**
	 * Create the missing standard fields among $names (optional, public, in the "Profile details" group).
	 *
	 * @param string[] $names Field names.
	 * @return string[] Names created.
	 */
	public static function create( array $names ) {
		$missing = self::missing( $names );

		if ( ! $missing ) {
			return [];
		}

		$group = 0;

		foreach ( (array) bp_xprofile_get_groups() as $existing ) {
			if ( self::GROUP === $existing->name ) {
				$group = (int) $existing->id;
			}
		}

		if ( ! $group ) {
			$group = (int) xprofile_insert_field_group(
				[
					'name'       => self::GROUP,
					'can_delete' => true,
				]
			);
		}

		$types   = function_exists( 'bp_xprofile_get_field_types' ) ? bp_xprofile_get_field_types() : [];
		$created = [];

		foreach ( $missing as $name ) {
			$type = isset( $types[ self::STANDARD[ $name ] ] ) ? self::STANDARD[ $name ] : 'textbox';
			$id   = $group ? xprofile_insert_field(
				[
					'field_group_id' => $group,
					'name'           => $name,
					'type'           => $type,
					'is_required'    => false,
					'can_delete'     => true,
				]
			) : 0;

			if ( $id ) {
				bp_xprofile_update_field_meta( $id, 'default_visibility', 'public' );
				$created[] = $name;
			}
		}

		self::flush();

		return $created;
	}
}
