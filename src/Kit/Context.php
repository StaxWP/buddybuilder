<?php
/**
 * Render context of an element (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@bedf241 (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable bag of values the renderer needs besides its attributes
 * (preview flag, post id, displayed member, loop item...). Keys are plugin specific.
 */
final class Context {

	/**
	 * Values.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param array $data Values.
	 */
	public function __construct( array $data = array() ) {
		$this->data = $data;
	}

	/**
	 * One value.
	 *
	 * @param string $key           Key.
	 * @param mixed  $default_value Returned when the key is missing.
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default_value;
	}

	/**
	 * Copy with more values.
	 *
	 * @param array $data Values to add or replace.
	 * @return Context
	 */
	public function with( array $data ) {
		return new self( array_merge( $this->data, $data ) );
	}

	/**
	 * Rendering for an editor preview (block editor or Elementor editor).
	 *
	 * @return bool
	 */
	public function is_preview() {
		return ! empty( $this->data['preview'] );
	}

	/**
	 * All values.
	 *
	 * @return array
	 */
	public function all() {
		return $this->data;
	}
}
