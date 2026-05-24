<?php
/**
 * IXR_ClientMulticall
 *
 * @package IXR
 * @since 1.5.0
 */
class IXR_ClientMulticall extends IXR_Client {

	public $calls = array();

	public function __construct( $server, $path = false, $port = 80 ) {
		parent::__construct( $server, $path, $port );
		$this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
	}

	public function addCall( ...$args ) {
		$methodName    = array_shift( $args );
		$struct        = array(
			'methodName' => $methodName,
			'params'     => $args,
		);
		$this->calls[] = $struct;
	}

	/**
	 * @since 1.5.0
	 * @since 5.5.0 Formalized the existing `...$args` parameter by adding it
	 *              to the function signature.
	 *
	 * @return bool
	 */
	public function query( ...$args ) {
		// Prepare multicall, then call the parent::query() method
		return parent::query( 'system.multicall', $this->calls );
	}
}
