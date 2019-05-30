<?php namespace ProcessWire;

/**
 * Google Calendar API helper class for GoogleClientAPI module
 * 
 * ---
 * Copyright 2019 by Ryan Cramer Design, LLC
 *
 */

class GoogleCalendar extends GoogleClientClass {

	/**
	 * Google calendar ID to use for API calls
	 * 
	 * @var string
	 * 
	 */
	protected $calendarId = 'primary';

	/**
	 * @param array $options
	 * @return \Google_Service|\Google_Service_Calendar
	 * @throws \Google_Exception
	 * 
	 */
	public function getService(array $options = array()) {
		return new \Google_Service_Calendar($this->getClient($options));
	}

	/**
	 * Set the calendar ID via a shareable calendar URL
	 * 
	 * Accepts URLs in any of the following formats:
	 * 
	 * - https://calendar.google.com/calendar?cid=cxlhbkByYy1kLn4lcA
	 * - https://calendar.google.com/calendar/embed?src=ryan%40processwire.com&ctz=America%2FNew_York
	 * - https://calendar.google.com/calendar/ical/ryan%40processwire.com/public/basic.ics 
	 * 
	 * @param string $calendarUrl
	 * @return self
	 * @throws WireException
	 * 
	 */
	public function setCalendarUrl($calendarUrl) {
		$calendarUrl = str_replace('%40', '@', $calendarUrl);
		if(preg_match('![\?&;/](cid=|src=|ical/)([-_.@a-zA-Z0-9]+)!', $calendarUrl, $matches)) {
			$this->calendarId = $matches[2];
		} else {
			throw new WireException(
				"Unrecognized calendar URL format. " . 
				"Please use shareable calendar URL that contains a calendar ID (cid) in the query string"
			); 
		}
		return $this;
	}

	/**
	 * Set the calendar ID by URL or ID
	 * 
	 * @param string $calendar
	 * @return self
	 * @throws WireException
	 * 
	 */
	public function setCalendar($calendar) {
		if(strpos($calendar, '://') !== false) {
			$this->setCalendarUrl($calendar);
		} else {
			$this->setCalendarId($calendar);
		}
		return $this;
	}

	/**
	 * Set the current calendar ID
	 * 
	 * @param string $calendarId
	 * @return self
	 * 
	 */
	public function setCalendarId($calendarId) {
		$this->calendarId = $calendarId; 
		return $this;
	}
	
	/**
	 * Get the current calendar ID
	 * 
	 * @return string
	 * 
	 */
	public function getCalendarId() {
		return $this->calendarId; 
	}
	
	/**
	 * Get events for given calendar ID (example usage of Google Client)
	 *
	 * Default behavior is to return the next 10 upcoming events. Use the
	 * $options argument to adjust this behavior.
	 *
	 * USAGE EXAMPLE
	 * =============
	 * $google = $modules->get('GoogleClientAPI');
	 * $calendar = $google->calendar();
	 * $calendar->setCalendarId('primary'); // optional
	 * $events = $calendar->getEvents();
	 * foreach($events->getItems() as $event) {
	 *   $start = $event->getStart()->dateTime;
	 *   if(empty($start)) $start = $event->getStart()->date;
	 *   echo sprintf("<li>%s (%s)</li>", $event->getSummary(), $start);
	 * }
	 *
	 * @param array|string $options One or more of the following options:
	 *  - `calendarId` (string): Calendar ID to pull events from. If not specified
	 *     it will use whatever calendar specified in previous setCalendarId() call,
	 *     which has a default value of 'primary'.
	 *  - `maxResults` (int): Max number of results to return (default=10)
	 *  - `orderBy` (string): Field to order events by (default=startTime)
	 *  - `timeMin` (string|int): Events starting after this date/time (default=now)
	 *  - `timeMax` (string|int): Events up to this date/time (default=null)
	 *  - `q` (string): Text string to search for
	 * @param array $o Additional options for legacy support (deprecated). 
	 *   This argument used as $options if calendar ID was specified in first argument.
	 *   It is here to be backwards compatible with previous argumnet layout,
	 *   but should otherwise be skipped. 
	 * @return \Google_Service_Calendar_Events|bool
	 *
	 */
	public function getEvents($options = array(), array $o = array()) {
		
		$defaults = array(
			'calendarId' => '',
			'maxResults' => 10,
			'orderBy' => 'startTime',
			'singleEvents' => true,
			'timeMin' => date('c'),
			'timeMax' => null,
			'q' => '',
		);

		if(!is_array($options)) {
			// legacy support for calendar ID as first argument
			$calendarId = $options;
			$options = $o;
			$options['calendarId'] = $calendarId;
		}
		
		$options = array_merge($defaults, $options);
		$calendarId = empty($options['calendarId']) ? $this->calendarId : $options['calendarId'];

		try {
			$service = $this->getService();
		} catch(\Exception $e) {
			$this->error($e->getMessage(), Notice::log);
			return false;
		}

		// make sure times are in format google expects
		foreach(array('timeMin', 'timeMax') as $t) {
			if(!isset($options[$t]) || $options[$t] === null) continue;
			$v = $options[$t];
			if(is_string($v)) $v = ctype_digit("$v") ? (int) $v : strtotime($v);
			if(is_int($v)) $options[$t] = date('c', $v);
		}

		// remove options that are not applicable or not in use
		unset($options['calendarId']); 
		if(empty($options['q'])) unset($options['q']);
		if(empty($options['timeMax'])) unset($options['timeMax']);

		// return the events
		return $service->events->listEvents($calendarId, $options);
	}

	/**
	 * Test Google Calendar API
	 * 
	 * @return string
	 * 
	 */
	public function test() {
		$out = [];
		$sanitizer = $this->wire('sanitizer');
		try {
			$events = $this->getEvents([ 'timeMin' => time() ]);
			foreach($events->getItems() as $event) {
				$start = $event->getStart()->dateTime;
				if(empty($start)) $start = $event->getStart()->date;
				$out[] = "<tr><td>" . $sanitizer->entities($event->getSummary()) . "</td><td>$start</td></tr>"; 
			}
		} catch(\Exception $e) {
			$out[] = "Google Calendar test failed: " . 
				get_class($e) . ' ' . 
				$e->getCode() . ' — ' . 
				$sanitizer->entities($e->getMessage());
		}
		if(count($out)) {
			$out = "<table border='1'>" . implode("\n", $out) . "</table>";
		} else {
			$out = "No upcoming events found.";
		}
		$out = "<div><strong>Google Calendar — Upcoming Events Test:</strong></div>$out";
		return $out; 
	}
}