<?php
/**
 * Minimal IXR stub for pingback compatibility.
 * @ignore
 */

if ( ! class_exists( 'IXR_Error', false ) ) {
	class IXR_Error {
		public $code;
		public $message;

		public function __construct( $code, $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function getXml() {
			return '<methodResponse><fault><value><struct><member><name>faultCode</name><value><int>' . $this->code . '</int></value></member><member><name>faultString</name><value><string>' . $this->message . '</string></value></member></struct></value></fault></methodResponse>';
		}
	}
}

if ( ! class_exists( 'WP_HTTP_IXR_Client', false ) ) {
	class WP_HTTP_IXR_Client {
		public $client;
		public $useragent = 'WordPress IXR Client';
		public $timeout = 3;
		public $debug = false;
		public $path = '/';
		public $host = '';

		public function __construct( $server, $path = '/' ) {
			$this->host = $server;
			$this->path = $path;
		}

		public function query( $method ) {
			$args = func_get_args();
			array_shift( $args );
			return $this->$method( $args );
		}

		protected function Xml( $xml ) {
			return '<?xml version="1.0"?><methodCall><methodName>' . $xml[0] . '</methodName></methodCall>';
		}

		protected function extendedPing( $args ) {
			return true;
		}

		protected function ping( $args ) {
			return true;
		}
	}
}