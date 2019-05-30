<?php namespace ProcessWire;

abstract class GoogleClientClass extends Wire {

	/**
	 * @var GoogleClientAPI
	 * 
	 */
	protected $module;

	/**
	 * @var \Google_Service
	 * 
	 */
	protected $service = null;

	/**
	 * Construct
	 *
	 * @param GoogleClientAPI $module
	 * 
	 */
	public function __construct(GoogleClientAPI $module) {
		$this->module = $module;
	}

	/**
	 * Get the Google_Client
	 *
	 * @param array $options
	 * @return \Google_Client
	 * @throws \Google_Exception|WireException
	 *
	 */
	protected function getClient($options = array()) {
		$client = $this->module->getClient($options);
		if(!$client) throw new WireException("The GoogleClientAPI module is not yet configured");
		return $client;
	}

	/**
	 * Get the Google_Service
	 * 
	 * @param array $options
	 * @return \Google_Service
	 * @throws \Google_Exception
	 * 
	 */
	abstract public function getService(array $options = array());

	/**
	 * Get the Google_Service with default options
	 *
	 * @return \Google_Service
	 * @throws \Google_Exception
	 *
	 */
	public function service() {
		if(!$this->service) $this->service = $this->getService();
		return $this->service;
	}
	
}