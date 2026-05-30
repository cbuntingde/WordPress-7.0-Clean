<?php
/**
 * Dependency Injection Container.
 *
 * @package WordPress
 * @subpackage DI
 */

/**
 * PSR-11 compatible container for WordPress.
 */
class Container {
	/**
	 * Singleton instance.
	 *
	 * @var Container|null
	 */
	private static $instance = null;

	/**
	 * Bindings.
	 *
	 * @var array
	 */
	private $bindings = array();

	/**
	 * Resolved instances.
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Get singleton instance.
	 *
	 * @return Container
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Reset singleton instance.
	 *
	 * @return void
	 */
	public static function reset_instance() {
		self::$instance = null;
	}

	/**
	 * Register a binding.
	 *
	 * @param string   $abstract Abstract type or name.
	 * @param callable $concrete Concrete callable.
	 * @return void
	 */
	public function bind( $abstract, $concrete ) {
		$this->bindings[ $abstract ] = $concrete;
	}

	/**
	 * Register a singleton instance.
	 *
	 * @param string $abstract Abstract type.
	 * @param mixed  $instance Instance.
	 * @return void
	 */
	public function singleton( $abstract, $instance ) {
		$this->instances[ $abstract ] = $instance;
	}

	/**
	 * Resolve from container.
	 *
	 * @param string $abstract Abstract type.
	 * @return mixed
	 */
	public function make( $abstract ) {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		if ( isset( $this->bindings[ $abstract ] ) ) {
			$concrete = $this->bindings[ $abstract ];
			$instance = $concrete( $this );
			$this->instances[ $abstract ] = $instance;
			return $instance;
		}

		throw new InvalidArgumentException( "No binding found for: {$abstract}" );
	}

	/**
	 * Check if binding exists.
	 *
	 * @param string $abstract Abstract type.
	 * @return bool
	 */
	public function has( $abstract ) {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->instances[ $abstract ] );
	}

	/**
	 * Resolve or return default.
	 *
	 * @param string $abstract Abstract type.
	 * @param mixed  $default  Default value.
	 * @return mixed
	 */
	public function resolve( $abstract, $default = null ) {
		try {
			return $this->make( $abstract );
		} catch ( InvalidArgumentException $e ) {
			return $default;
		}
	}
}

/**
 * Get container instance.
 *
 * @return Container
 */
function container() {
	return Container::get_instance();
}