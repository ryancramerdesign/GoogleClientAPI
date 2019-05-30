<?php namespace ProcessWire;

/**
 * Google Sheets API helper class for GoogleClientAPI module
 * 
 * Before using this class please see Google Sheets key concepts, especially "Spreadsheet ID" and "A1 Notation":
 * https://developers.google.com/sheets/api/guides/concepts
 * 
 * Example:
 * ~~~~~~
 * // Get Google sheets (required for all examples below)
 * $google = $modules->get('GoogleClientAPI');
 * $sheets = $google->sheets();
 * 
 * // Print out rows 1-5 from a spreadsheet
 * $sheets->setSpreadsheetUrl('https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit#gid=SHEET_ID');
 * $rows = $sheets->getRows(1, 5); 
 * print_r($rows); 
 * 
 * // Create a new spreadsheet, add a header row and another row
 * $sheets->addSpreadsheet('Hello World');
 * $sheets->setRows(1, [ // set rows starting from row 1
 *   [ 'First name', 'Last name', 'Email', 'Age' ], // row 1: our header row
 *   [ 'Ryan', 'Cramer', 'ryan@processwire.com', 44 ] // row 2: example data
 * ]); 
 * 
 * // Append one new row to a spreadsheet
 * $sheets->appendRow([ 'Ryan', 'Cramer', 'ryan@processwire.com', 44 ]);
 * ~~~~~~
 * 
 * Note the term “Spreadsheet” refers to an entire spreadsheet, while the term 
 * “Sheet” refers to a tab within a spreadsheet.
 * 
 * @method \Google_Service_Sheets service()
 * 
 * ---
 * Copyright 2019 by Ryan Cramer Design, LLC
 * 
 */
class GoogleSheets extends GoogleClientClass {

	/**
	 * Current working spreadsheet ID
	 * 
	 * @var string
	 * 
	 */
	protected $spreadsheetId = '';

	/**
	 * ID of the current tab/sheet in the spreadsheet, i.e. 0 or 123 or null if not set by a setSheet() call
	 * 
	 * @var null
	 * 
	 */
	protected $sheetId = null;
	
	/**
	 * Title of the current tab/sheet in the spreadsheet, i.e. "Sheet1" or null if not specified by a setSheet() call
	 *
	 * @var null
	 *
	 */
	protected $sheetTitle = null;

	/**
	 * Set current spreadsheet by ID or URL (auto-detect) and optionally set sheet
	 * 
	 * @param string $spreadsheet Spreadsheet ID or URL
	 * @return self
	 * 
	 */
	public function setSpreadsheet($spreadsheet) {
		if(strpos($spreadsheet, '://') !== false) {
			$this->setSpreadsheetUrl($spreadsheet);
		} else {
			$this->setSpreadsheetId($spreadsheet);
		}
		return $this;
	}

	/**
	 * Set the current working spreadsheet by URL
	 * 
	 * This automatically extracts the spreadsheet ID and sheet ID.
	 * If you call this, it is NOT necessary to call setSpreadsheetId() or setSheetId()
	 * 
	 * @param string $url
	 * @throws WireException if given URL that isn’t recognized as Google Sheets URL
	 * @return self
	 * 
	 */
	public function setSpreadsheetUrl($url) {
		if(strpos($url, '/d/') === false) {
			throw new WireException(
				'Unrecognized Google Sheets URL. Must be in this format: ' . 
				'https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit#gid=SHEET_ID'
			);
		}
		$sheetId = '';
		list(, $spreadsheetId) = explode('/d/', $url, 2); // SPREADSHEET_ID/edit#gid=SHEET_ID
		list($spreadsheetId, $s) = explode('/', $spreadsheetId, 2); // 'SPREADSHEET_ID', 'edit#gid=SHEET_ID'
		if(strpos($s, 'gid=') !== false && preg_match('/gid=(\d+)/', $s, $matches)) $sheetId = $matches[1];
		$this->setSpreadsheetId($spreadsheetId);
		if($sheetId !== '') $this->setSheetId($sheetId);
		return $this;
	}

	/**
	 * Set the current working spreadsheet by ID
	 * 
	 * If you will also be setting a sheet, make sure you call the setSheet* method after this rather than before. 
	 * 
	 * @param string $spreadsheetId
	 * @param string|int $sheet Optional sheet title or sheetId, within the spreadsheet
	 * @return self
	 * 
	 */
	public function setSpreadsheetId($spreadsheetId, $sheet = '') {
		if($spreadsheetId !== $this->spreadsheetId) {
			$this->sheetTitle = null;
			$this->sheetId = null;
			$this->spreadsheetId = $spreadsheetId;
		}
		if($sheet !== '') $this->setSheet($sheet); 
		return $this;
	}

	/**
	 * Get the current spreadsheet ID
	 * 
	 * @return string
	 * 
	 */
	public function getSpreadsheetId() {
		if(empty($this->spreadsheetId)) {
			throw new WireException("Please call setSpreadsheetId() before calling any methods on GoogleSheets");
		}
		return $this->spreadsheetId;
	}

	/**
	 * Set the current sheet/tab
	 * 
	 * @param string|int $sheet Sheet title or id
	 * @return self
	 * 
	 */
	public function setSheet($sheet) {
		if(is_int($sheet) || ctype_digit("$sheet")) {
			$this->setSheetId($sheet);
		} else if(is_string($sheet) && strlen($sheet)) {
			$this->setSheetTitle($sheet);
		}
		return $this;
	}

	/**
	 * Get the current sheet/tab by ID (#gid in spreadsheet URL)
	 * 
	 * @param string|int $sheetId
	 * @return self
	 * 
	 */
	public function setSheetId($sheetId) {
		$this->sheetId = (int) $sheetId;
		$this->sheetTitle = null;
		return $this;
	}

	/**
	 * Set the current sheet/tab in the spreadsheet by title
	 *
	 * @param string $title
	 * @return self
	 *
	 */
	public function setSheetTitle($title) {
		$this->sheetTitle = $title;
		$this->sheetId = null;
		return $this;
	}

	/**
	 * Get the current sheet/tab ID (aka gid)
	 * 
	 * @return int||null
	 * 
	 */
	public function getSheetId() {
		if($this->sheetId === null && $this->sheetTitle) return $this->sheetId(); 
		return (int) $this->sheetId; 
	}

	/**
	 * Get the current sheet/tab title (label that user clicks on for tab in spreadsheet)
	 *
	 * @return int||null
	 *
	 */
	public function getSheetTitle() {
		if($this->sheetTitle === null && $this->sheetId !== null) return $this->sheetTitle(); 
		return $this->sheetTitle === null ? '' : $this->sheetTitle; 
	}

	/**
	 * Get the Google Sheets service
	 * 
	 * #pw-internal
	 * 
	 * @param array $options
	 * @return \Google_Service|\Google_Service_Sheets
	 * @throws WireException
	 * @throws \Google_Exception
	 * 
	 */
	public function getService(array $options = array()) {
		return new \Google_Service_Sheets($this->getClient($options));
	}
	
	/**
	 * Add/create a new spreadsheet and set it as the current spreadsheet
	 * 
	 * ~~~~~
	 * $spreadsheet = $google->sheets()->addSpreadsheet('My test spreadsheet');
	 * $spreadsheetId = $spreadsheet->spreadsheetId;
	 * ~~~~~
	 * 
	 * @param string $title Title for spreadsheet
	 * @param bool $setAsCurrent Set as the current working spreadsheet? (default=true)
	 * @return \Google_Service_Sheets_Spreadsheet
	 * @throws \Google_Exception
	 * 
	 */
	public function addSpreadsheet($title, $setAsCurrent = true) {
		$spreadsheet = new \Google_Service_Sheets_Spreadsheet([
			'properties' => [
				'title' => $title
			]
		]);
		$spreadsheet = $this->service()->spreadsheets->create($spreadsheet, [
			'fields' => 'spreadsheetId,spreadsheetUrl'
		]);
		if($setAsCurrent) $this->setSpreadsheetId($spreadsheet->spreadsheetId);
		return $spreadsheet;
	}

	/**
	 * Get cells from the spreadsheet
	 * 
	 * @param string $range Specify one of the following:
	 *  - Range of rows to retrieve in format "1:3" where 1 is first row number and 3 is last row number
	 *  - Range of cells to retrieve in A1 format, i.e. "A1:C3" where A1 is starting col-row, and C3 is ending col-row.
	 * @param array $options
	 * @return array
	 * @throws \Google_Exception
	 * 
	 */
	public function getCells($range = '', array $options = array()) {
		$params = array(
			'majorDimension' => 'ROWS', // or COLUMNS
			'valueRenderOption' => 'FORMATTED_VALUE', // or UNFORMATTED_VALUE or FORMULA: https://developers.google.com/sheets/api/reference/rest/v4/ValueRenderOption
			'dateTimeRenderOption' => 'FORMATTED_STRING', // or SERIAL_NUMBER: https://developers.google.com/sheets/api/reference/rest/v4/DateTimeRenderOption
		);
		foreach(array_keys($params) as $key) {
			if(isset($options[$key])) $params[$key] = $options[$key];
		}
		$result = $this->service()->spreadsheets_values->get($this->getSpreadsheetId(), $this->rangeStr($range), $options);
		return $result->getValues();
	}

	/**
	 * Get requested rows (multiple)
	 * 
	 * @param int $fromRowNum
	 * @param int $toRowNum
	 * @param array $options
	 * @return array
	 * @throws \Google_Exception
	 * 
	 */
	public function getRows($fromRowNum, $toRowNum, array $options = array()) {
		return $this->getCells($this->rangeStr("$fromRowNum:$toRowNum"), $options); 
	}

	/** 
	 * Get requested row (single)
	 * 
	 * @param int $rowNum
	 * @param array $options
	 * @return array
	 * @throws \Google_Exception
	 *
	 */
	public function getRow($rowNum, array $options = array()) {
		$rows = $this->getRows($rowNum, $rowNum, $options); 
		return count($rows) ? $rows[0] : array();
	}

	/**
	 * Update/modify cells in a spreadsheet
	 * 
	 * The $range argument can specify multiple cells (for example, A1:D5) or a single cell (for example, A1). 
	 * If it specifies multiple cells, the $rows argument must be within that range. If it specifies a single cell, 
	 * the input data starts at that coordinate can extend any number of rows or columns.
	 *
	 * The $values argument should be a PHP array in this format:
	 * ~~~~~
	 * $data = [
	 *   [
	 *     'row 1 col A value',
	 *     'row 1 col B value'
	 *   ],
	 *   [
	 *     'row 2 col A value',
	 *     'row 2 col B value'
	 *   ],
	 *   // and so on for each row
	 * ];
	 * ~~~~~
	 *
	 * @param string $range Range in A1 notation, see notes above. 
	 * @param array $values Rows/cells you want to update in format shown above
	 * @param array $options Options to modify default behavior:
	 *  - `raw` (bool): Add as raw data? Raw data unlike user-entered data is not converted to dates, formulas, etc. (default=false)
	 *  - `overwrite` (bool): Allow overwrite existing rows rather than inserting new rows after range? (default=false)
	 *  - `params` (array): Additional params ($optParams argument) for GoogleSheets call (internal).
	 * @return \Google_Service_Sheets_AppendValuesResponse|\Google_Service_Sheets_UpdateValuesResponse
	 * @throws \Google_Exception
	 *
	 */
	public function setCells($range, array $values, array $options = array()) {
		
		$defaults = array(
			'raw' => false,
			'action' => 'update', // method to call from spreadsheet_values
			'replace' => true, 
			'params' => [], 
		);
		
		$options = array_merge($defaults, $options);
		$action = $options['action'];
		$body = new \Google_Service_Sheets_ValueRange([ 'values' => $values ]);
		$params = [ 'valueInputOption' => ($options['raw'] ? 'RAW' : 'USER_ENTERED') ];
		
		if($options['action'] === 'append') {
			$params['insertDataOption'] = $options['replace'] ? 'OVERWRITE' : 'INSERT_ROWS';
		}
		
		if(!empty($options['params'])) {
			$params = array_merge($params, $options['params']);
		}
		
		$range = $this->rangeStr($range); 
	
		/*
		 * spreasheet_values->update(
		 *   $spreadsheetId, 
		 *   $range, 
		 *   Google_Service_Sheets_ValueRange $postBody, 
		 *   $optParams = array()
		 * );	
		 * 
		 * $optParams for update() method: 
		 * 
		 * - string `responseValueRenderOption` Determines how values in the response should 
		 *   be rendered. The default render option is ValueRenderOption.FORMATTED_VALUE.
		 * 
		 * - string `valueInputOption` How the input data should be interpreted.
		 * 
		 * - string `responseDateTimeRenderOption` Determines how dates, times, and durations 
		 *   in the response should be rendered. This is ignored if response_value_render_option 
		 *   is FORMATTED_VALUE. The default dateTime render option is DateTimeRenderOption.SERIAL_NUMBER.
		 * 
		 * - bool `includeValuesInResponse` Determines if the update response should include the 
		 *   values of the cells that were updated. By default, responses do not include the updated 
		 *   values. If the range to write was larger than than the range actually written, the 
		 *   response will include all values in the requested range (excluding trailing empty rows 
		 *   and columns).
		 * 
		 */
		
		$result = $this->service()->spreadsheets_values->$action($this->getSpreadsheetId(), $range, $body, $params);
		
		if($action === 'append') {
			/** @var \Google_Service_Sheets_AppendValuesResponse $result */
			// $numCells = $result->getUpdates()->getUpdatedCells();
		} else {
			/** @var \Google_Service_Sheets_UpdateValuesResponse $result */
			// $numCells = $result->getUpdatedCells();
		}
		// $this->message("<pre>" . print_r($result, true) . "</pre>", Notice::allowMarkup);
		
		return $result;
	}

	/**
	 * Update values for multiple rows
	 * 
	 * @param int $fromRowNum Row number to start update from
	 * @param array $rows Array of rows, each containing an array of column data
	 * @param array $options See options for updateCells() method
	 * @return \Google_Service_Sheets_AppendValuesResponse|\Google_Service_Sheets_UpdateValuesResponse|bool
	 * @throws \Google_Exception
	 * 
	 */
	public function setRows($fromRowNum, array $rows, array $options = array()) {
		$fromRowNum = (int) $fromRowNum;
		$numRows = count($rows);
		if(!$numRows) return false;
		$toRowNum = $fromRowNum + ($numRows - 1);
		$range = $this->rangeStr("A$fromRowNum:A$toRowNum");
		return $this->setCells($range, $rows, $options); 
	}
	
	/**
	 * Add/append rows to a spreadsheet
	 *
	 * The rows argument should be a PHP array in this format:
	 * ~~~~~
	 * $rows = [
	 *   [
	 *     'row 1 col A value',
	 *     'row 1 col B value'
	 *   ],
	 *   [
	 *     'row 2 col A value',
	 *     'row 2 col B value'
	 *   ],
	 *   // and so on for each row
	 * ];
	 * ~~~~~
	 *
	 * @param array $rows Rows you want to add in format shown above
	 * @param int $fromRowNum Append rows after block of rows that $fromRowNum is within (default=1)
	 * @param array $options Options to modify default behavior:
	 *  - `raw` (bool): Add as raw data? Raw data unlike user-entered data is not converted to dates, formulas, etc. (default=false)
	 * @return \Google_Service_Sheets_AppendValuesResponse
	 * @throws \Google_Exception
	 *
	 */
	public function appendRows(array $rows, $fromRowNum = 1, array $options = array()) {
		$options['action'] = 'append';
		$options['replace'] = false;
		return $this->setRows($fromRowNum, $rows, $options);
	}

	/**
	 * Add/append a single row to a spreadsheet
	 *
	 * The row argument should be a PHP array in this format:
	 * ~~~~~
	 * $row = [
	 *   'column A value',
	 *   'column B value',
	 *   'column C value',
	 *    // and so on for each column
	 * ];
	 * ~~~~~
	 *
	 * @param array $row Row you want to add in format shown above
	 * @param int $fromRowNum Append rows after block of rows that $fromRowNum is within (default=1)
	 * @param array $options Options to modify default behavior:
	 *  - `raw` (bool): Add as raw data? Raw data unlike user-entered data is not converted to dates, formulas, etc. (default=false)
	 * @return \Google_Service_Sheets_AppendValuesResponse
	 * @throws \Google_Exception
	 *
	 */
	public function appendRow(array $row, $fromRowNum = 1, array $options = array()) {
		return $this->appendRows([ $row ], $fromRowNum, $options);
	}

	/**
	 * Insert blank cells in the spreadsheet
	 * 
	 * @param int $fromNum Row or column number to start inserting after or specify negative row/col number to insert before
	 * @param int $qty Quantity of rows/columns to insert
	 * @param bool $insertRows Insert rows? If false, it will insert columns rather than rows. 
	 * @param array $options
	 * @return \Google_Service_Sheets_BatchUpdateSpreadsheetResponse|bool
	 * @throws \Google_Exception
	 * 
	 */
	protected function insertBlanks($fromNum, $qty, $insertRows = true, array $options = array()) {
		if($qty < 1) return false;
		
		$insertBefore = $fromNum < 0;
		$fromNum = abs($fromNum);
		$startIndex = $insertBefore ? $fromNum - 1 : $fromNum;
		$endIndex = ($startIndex + $qty) - 1;
		$inheritFromBefore = $startIndex > 0 ? true : false;

		$request = new \Google_Service_Sheets_Request([
			'insertDimension' => [
				'range' => [
					'sheetId' => isset($options['sheetId']) ? $options['sheetId'] : 0,
					'dimension' => $insertRows ? 'ROWS' : 'COLUMNS',
					'startIndex' => $startIndex,
					'endIndex' => $endIndex,
				],
				// inherit properties from rows before newly inserted rows? (false=inherit after)
				'inheritFromBefore' => $inheritFromBefore,
			]
		]);

		$batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([ 'requests' => [ $request ] ]);
		$response = $this->service()->spreadsheets->batchUpdate($this->getSpreadsheetId(), $batchUpdateRequest);

		return $response;
	}
	
	/**
	 * Insert new rows after a specific row
	 * 
	 * @param array $rows
	 * @param int $rowNum Insert rows before this row number
	 * @param array $options
	 * @return \Google_Service_Sheets_UpdateValuesResponse
	 * 
	 */ 
	public function insertRowsAfter(array $rows, $rowNum, array $options = array()) {
		$this->insertBlanks($rowNum, count($rows), $options);
		$options['replace'] = false;
		$options['action'] = 'update';
		return $this->setRows($rowNum, $rows, $options);
	}

	/**
	 * Insert new rows before a specific row
	 *
	 * @param array $rows
	 * @param int $rowNum Insert rows before this row number
	 * @param array $options
	 * @return \Google_Service_Sheets_UpdateValuesResponse
	 *
	 */ 
	public function insertRowsBefore(array $rows, $rowNum, array $options = array()) {
		$this->insertBlanks($rowNum * -1, count($rows), $options);
		$options['replace'] = false;
		$options['action'] = 'update';
		return $this->setRows($rowNum, $rows, $options);
	}

	/**
	 * Update/set property for existing spreadsheet
	 * 
	 * @param string $propertyName Property name, like "title"
	 * @param string $propertyValue Property value
	 * @return \Google_Service_Sheets_BatchUpdateSpreadsheetResponse
	 * @throws \Google_Exception
	 * 
	 */
	public function setProperty($propertyName, $propertyValue) {
		$request = new \Google_Service_Sheets_Request([
			'updateSpreadsheetProperties' => [
				'properties' => [ $propertyName => $propertyValue ],
				'fields' => $propertyName
			]
		]);
		$batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([ 'requests' => [ $request ] ]);
		$response = $this->service()->spreadsheets->batchUpdate($this->getSpreadsheetId(), $batchUpdateRequest);
		return $response;
	}

	/**
	 * Get all properties for the spreadsheet (or optionally a specific property)
	 * 
	 * @param string|bool $property Specify property to retrieve, omit to return entire spreasheet, or boolean true for all properties.
	 * @return \Google_Service_Sheets_Spreadsheet|mixed
	 * @throws \Google_Exception
	 * 
	 */
	public function getProperties($property = '') {
		
		$spreadsheet = $this->service()->spreadsheets->get($this->getSpreadsheetId());
		
		if($property === true) {
			return $spreadsheet->getProperties();
		}
		
		switch($property) {
			case 'title':
			case 'timeZone':
			case 'locale':
				$value = $spreadsheet->getProperties()->$property;
				break;
			case 'url':	
				$value = $spreadsheet->spreadsheetUrl;
				break;
			default:	
				$value = $spreadsheet;
		}
		
		return $value;
	}

	/**
	 * Get all sheets/tabs in the Spreadsheet
	 *
	 * Returns array of arrays, each containing basic info for each sheet. If the $verbose option is true, then it
	 * returns array of \Google_Service_Sheets_Sheet objects rather than array of basic info for each sheet.
	 *
	 * @param bool $verbose Returns verbose objects? (default=false)
	 * @param string $indexBy Specify "title" or "sheetId" to return array indexed by those properties, or omit for no index (regular PHP array)
	 * @return array|\Google_Service_Sheets_Sheet[]
	 *
	 */
	public function getSheets($verbose = false, $indexBy = '') {
		$sheets = array();
		foreach($this->getProperties()->getSheets() as $sheet) {
			/** @var \Google_Service_Sheets_Sheet $sheet */
			if($verbose) {
				$sheets[] = $sheet;
				continue;
			}
			$properties = $sheet->getProperties();
			$gridProperties = $properties->getGridProperties();
			$sheetArray = [
				'title' => $properties->getTitle(),
				'sheetId' => $properties->getSheetId(),
				'sheetType' => $properties->getSheetType(),
				'index' => $properties->getIndex(),
				'hidden' => $properties->getHidden(),
				'numRows' => $gridProperties->getRowCount(),
				'numCols' => $gridProperties->getColumnCount(),
			];
			if($indexBy) {
				$key = $sheetArray[$indexBy];
				$sheets[$key] = $sheetArray;
			} else {
				$sheets[] = $sheetArray;
			}
		}
		return $sheets;
	}

	/**
	 * Get current sheet ID, auto-detecting from sheet title if necessary
	 * 
	 * @return int|mixed|null
	 * 
	 */
	protected function sheetId() {
		if($this->sheetId !== null) return $this->sheetId;
		if(!empty($this->sheetTitle)) {
			$sheets = $this->getSheets(false, 'title');
			if(isset($sheets[$this->sheetTitle])) {
				$this->sheetId = $sheets[$this->sheetTitle]['sheetId'];
				return $this->sheetId;
			}
		}
		return 0;
	}

	/**
	 * Get current sheet title, auto-detecting from sheet ID if necessary
	 * 
	 * @return null|string
	 * 
	 */
	protected function sheetTitle() {
		if(!empty($this->sheetTitle)) return $this->sheetTitle;
		if($this->sheetId !== null) {
			$sheets = $this->getSheets(false, 'sheetId');
			$this->sheetTitle = isset($sheets[$this->sheetId]) ? $sheets[$this->sheetId]['title'] : null;
		}
		return '';
	}

	/**
	 * Given a range string, prepare it for API call, plus update it to include sheet title when applicable
	 * 
	 * @param string $range
	 * @return string
	 * 
	 */
	protected function rangeStr($range) {
		
		if(strlen($range) && strpos($range, ':') === false) {
			$range = "$range:$range";
		}
		
		if(strpos($range, '!') === false) {
			// no sheet present
			$sheetTitle = $this->sheetTitle();
			if(!empty($sheetTitle)) {
				$sheetTitle = "'" . trim($sheetTitle, "'") . "'";
				if(strlen($range)) {
					// range of cells in sheet
					$range = $sheetTitle . "!$range";
				} else {
					// all cells in sheet
					$range = $sheetTitle;
				}
			}
		}
		
		return $range;
	}


	/**
	 * Test the Google Sheets API
	 * 
	 * @return string
	 * 
	 */
	public function test() {
		$sanitizer = $this->wire('sanitizer');
		$out = [];
		try {
			$title = $this->getProperties('title');
			$sheets = $this->getSheets();
			$out[] = "<strong>Google Sheets Spreadsheet:</strong> <u>" . $sanitizer->entities($title) . "</u>";
			foreach($sheets as $sheet) {
				$out[] = "Sheet: " . $sanitizer->entities($sheet['title']) . " ($sheet[numRows] rows, $sheet[numCols] columns)";
			}
		} catch(\Exception $e) {
			$out[] = "GoogleSheets test failed: " .
				get_class($e) . ' ' .
				$e->getCode() . ' ' . 
				$sanitizer->entities($e->getMessage());
		}
		return implode('<br />', $out);
	}
}

