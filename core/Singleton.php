<?php

namespace Buddy_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Singleton
 * @package Buddy_Builder
 */
class Singleton {

	/**
	 * @var array
	 */
	public static $instances = [];

	/**
	 * Singleton constructor.
	 */
	protected function __construct() {
	}

	/**
	 * Disables class cloning and throw an error on object clone.
	 */
	final private function __clone() {
	}

	/**
	 * Disables unserializing of the class.
	 */
	final private function __wakeup() {
	}

	/**
	 * Get instance
	 *
	 * @return mixed
	 */
	public static function get_instance() {
		$class = static::class;

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class;
		}

		return self::$instances[ $class ];
	}

}
