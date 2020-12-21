<?php namespace ProcessWire;

/**
 * Google mail (Gmail) API helper class for GoogleClientAPI module
 *
 * ---
 * Copyright 2019 by Ryan Cramer Design, LLC
 *
 */

class GoogleMail extends GoogleClientClass {

	/**
	 * @param array $options
	 * @return \Google_Service|\Google_Service_Gmail
	 * @throws \Google_Exception
	 *
	 */
	public function getService(array $options = array()) {
		return new \Google_Service_Gmail($this->getClient($options));
	}

}