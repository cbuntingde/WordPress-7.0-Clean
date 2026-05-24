<?php

/**
 * IXR_Base64
 *
 * @package IXR
 * @since 1.5.0
 */
class IXR_Base64 {

	public $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function getXml() {
		return '<base64>' . base64_encode( $this->data ) . '</base64>';
	}
}
