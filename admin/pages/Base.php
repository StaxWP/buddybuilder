<?php

namespace Buddy_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Base
 * @package Buddy_Builder
 */
class Base {

	/**
	 * @var null
	 */
	protected static $instances;

	/**
	 * @var string
	 */
	protected $current_slug;

	/**
	 * @return Base|null
	 */
	public static function instance() {
		$class = static::class;

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class;
		}

		return self::$instances[ $class ];
	}

	/**
	 * @return string
	 */
	public function set_page_slug() {
		return $this->current_slug;
	}

	/**
	 * Get page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return BPB_ADMIN_PREFIX . $this->current_slug;
	}

	/**
	 * @return array
	 */
	public function set_wrapper_classes() {
		return [ $this->current_slug ];
	}

}
