<?php namespace ProcessWire;

/**
 * Interactive configuration and testing methods for GoogleClientAPI module
 * 
 * @todo allow pasting in client id and client secret independently? or allow upload of json
 *
 */
class GoogleClientConfig extends Wire {

	/**
	 * @var GoogleClientAPI
	 *
	 */
	protected $module;

	/**
	 * Construct
	 *
	 * @param GoogleClientAPI $module
	 *
	 */
	public function __construct(GoogleClientAPI $module) {
		$module->wire($this);
		$this->module = $module;
	}
	
	/**
	 * Install the Google PHP API Client library
	 *
	 * @param string $version i.e. "2.2.3"
	 * @return bool
	 *
	 */
	protected function installGoogleLibrary($version) {

		/** @var WireFileTools $files */
		$files = $this->wire('files');
		$version = trim($version, '. ');

		if(strlen($version) < 4 || !preg_match('/^[\.0-9]+$/', $version)) {
			$this->error(
				"Please use version number format “0.0.0” where each “0” is any number " . 
				"(i.e. " . GoogleClientAPI::defaultLibVersion . ")"
			);
			return false;
		}

		set_time_limit(3600);

		if(empty($version)) $version = GoogleClientAPI::defaultLibVersion;

		$downloadUrl =
			"https://github.com/googleapis/google-api-php-client/releases/" .
			"download/v$version/google-api-php-client-$version.zip";

		$libPath = $this->module->getGoogleLibPath(); // site/assets/GoogleClientAPI/google-api-php-client/
		$apiPath = $this->module->getGoogleLibPath(true); // site/assets/GoogleClientAPI/
		$zipName = 'download.zip';
		$zipFile = $apiPath . $zipName;
		$htaFile = $apiPath . '.htaccess';
		$completed = false;
		$n = 0;

		if(!is_dir($apiPath)) $files->mkdir($apiPath, true);
		if(is_file($zipFile)) $files->unlink($zipFile);
		if(is_dir($libPath)) $files->rmdir($libPath, true);

		if(!is_file($htaFile)) {
			// block web access to this path, not really necessary, but just in case
			file_put_contents($htaFile, "RewriteEngine On\nRewriteRule ^.*$ - [F,L]");
			$files->chmod($htaFile);
		}

		while(is_file($zipFile)) {
			// ensure we do not unzip something that was already present (not likely)
			$zipFile = $apiPath . (++$n) . $zipName;
		}

		try {
			// download ZIP
			$http = new WireHttp();
			$http->download($downloadUrl, $zipFile);
			if(!is_file($zipFile)) throw new WireException("Error downloading to: $zipFile");
			// $this->message("Downloaded $downloadUrl => $zipFile"); 
		} catch(\Exception $e) {
			$this->error($e->getMessage());
		}

		if(is_file($zipFile)) try {
			$unzipped = $files->unzip($zipFile, $apiPath);
			$this->message("Unzipped $zipFile (" . count($unzipped) . " files)");
			clearstatcache();
			foreach(new \DirectoryIterator($apiPath) as $file) {
				if(!$file->isDir() || $file->isDot()) continue;
				$files->rename($file->getPathname(), $libPath);  // /google-api-php-client/
				break;
			}
			$autoloadFile = $this->module->getGoogleAutoloadFile();
			$completed = is_file($autoloadFile);
			if(!$completed) $this->error("Unable to find: $autoloadFile");
		} catch(\Exception $e) {
			$this->error($e->getMessage());
		}

		if($completed) {
			$this->message("Installed: $libPath");
		} else {
			$this->error("Unable to install: $libPath");
			$files->rmdir($libPath, true);
		}

		if(is_file($zipFile)) {
			$files->unlink($zipFile);
		}

		return $completed;
	}

	/**
	 * Module configuration
	 *
	 * @param InputfieldWrapper $form
	 *
	 */
	public function getModuleConfigInputfields(InputfieldWrapper $form) {

		$modules = $this->wire('modules');
		$session = $this->wire('session');
		$input = $this->wire('input');
		$redirectURL = $this->module->redirectURL ? $this->module->redirectURL : $input->httpUrl(true);
		$user = $this->wire('user');
		$module = $this->module;

		if($module->configUserID && $module->configUserID != $user->id) {
			$configUser = $this->wire('users')->get((int) $module->configUserID);
			$userName = $configUser && $configUser->id ? $configUser->name : "id=$this->module->configUserID";
			$this->error(sprintf($this->_('Configuration of this module is limited to user: %s'), $userName));
			return;
		}

		// -------------

		/** @var InputfieldFieldset $fs */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Google API PHP client library');
		$fs->icon = 'google';
		$fs->set('themeOffset', 1);
		$form->add($fs);

		/** @var InputfieldCheckbox $f */
		$f = $modules->get('InputfieldCheckbox');
		$f->attr('name', '_install_lib');
		$libVersion = $module->getGoogleLibVersion();
		if($libVersion) {
			$fs->collapsed = Inputfield::collapsedYes;
			$fs->label .= ' ' . sprintf($this->_('(version %s installed)'), $libVersion);
			$f->label = $this->_('Change version?');
			$f->description = $this->_('After successfully changing the version, please use the “force re-authenticate” option that appears further down on this screen.');
			$required = true;
		} else {
			$f->label = $this->_('Install now?');
			$f->description =
				$this->_('The required Google API PHP client library is not yet installed.') . ' ' .
				$this->_('You may install it automatically by clicking this checkbox.');
			$required = false;
		}
		$f->description .= ' ' . sprintf(
				$this->_('If you prefer, you may clone/download/unzip and install it yourself into %s.'),
				$module->getGoogleLibPath(false, true)
			);
		$f->notes =
			sprintf($this->_('Checking the box installs the library into %s.'), $module->getGoogleLibPath(false, true)) . " \n" .
			$this->_('Please note the library is quite large and may take several minutes to download and install.');
		$fs->add($f);

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', '_install_lib_ver');
		$f->label = $this->_('Google API PHP client library version');
		$f->description = sprintf(
			$this->_('Enter the library version from the [releases](%s) page that you want to install, or accept the default, then click submit.'),
			'https://github.com/googleapis/google-api-php-client/releases'
		);
		$f->attr('placeholder', GoogleClientAPI::defaultLibVersion);
		$f->attr('value', GoogleClientAPI::defaultLibVersion);
		$f->showIf = '_install_lib>0';
		$fs->add($f);

		if($input->post('_install_lib') && $input->post('_install_lib_ver')) {
			$session->setFor($this, 'install_lib', $input->post->name('_install_lib_ver'));
		} else if($session->getFor($this, 'install_lib')) {
			$version = $session->getFor($this, 'install_lib');
			$session->removeFor($this, 'install_lib');
			$this->installGoogleLibrary($version);
			$session->redirect($input->url(true));
		}

		// -----------------
		
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Google API services authentication');
		$fs->icon = 'google';
		$fs->set('themeOffset', 1);
		if(!$required) {
			$fs->collapsed = Inputfield::collapsedYes;
			$fs->description = $this->_('Please install the Google API PHP client library before configuring this section.'); 
		} else {
			$fs->description = sprintf(
				$this->_('Please follow [these instructions](%s) to complete this section.'), 
				'https://github.com/ryancramerdesign/GoogleClientAPI/blob/master/README.md'
			); 
		}
		$form->add($fs);

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', 'applicationName');
		$f->label = $this->_('Application name');
		$f->attr('value', $module->applicationName);
		$f->required = $required;
		$fs->add($f);

		/** @var InputfieldTextarea $f */
		$f = $modules->get('InputfieldTextarea');
		$f->attr('name', 'scopes');
		$f->label = $this->_('Scopes (one per line)');
		$f->attr('value', implode("\n", $module->scopes));
		$f->description =
			sprintf($this->_('A list of available scopes can be found [here](%s).'), 'https://developers.google.com/identity/protocols/googlescopes') . ' ' .
			$this->_('Note that any changes to scopes will redirect you to Google to confirm the change.');
		$f->notes = '**' . $this->_('Example:') . "**\nhttps://www.googleapis.com/auth/spreadsheets\nhttps://www.googleapis.com/auth/calendar.readonly";
		$f->required = $required;

		if(!strlen($module->scopesHash) && count($module->scopes)) $module->scopesHash = $module->scopesHash();
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->attr('name', 'authConfig');
		$f->label = $this->_('Authentication config / client secret JSON');
		$f->description = $this->_('Paste in the client secret JSON provided to you by Google.');
		$f->attr('value', $module->authConfig);
		$f->required = $required;
		$f->collapsed = Inputfield::collapsedPopulated;
		$fs->add($f);

		/** @var InputfieldCheckbox $f */
		if($module->authConfig) {
			$f = $modules->get('InputfieldCheckbox');
			$f->attr('name', '_reauth');
			$f->label = $this->_('Force re-authenticate with Google now?');
			$f->description = $this->_('If you get any permission errors during API calls you may need to force re-authenticate with Google.');
			$f->collapsed = Inputfield::collapsedYes;
			$fs->add($f);
		}

		if(GoogleClientAPI::debug) {
			$f = $modules->get('InputfieldTextarea');
			$f->attr('name', '_accessToken');
			$f->label = 'Access Token (populated automatically)';
			$f->attr('value', is_array($module->accessToken) ? json_encode($module->accessToken) : $module->accessToken);
			$f->collapsed = Inputfield::collapsedYes;
			$fs->add($f);

			$f = $modules->get('InputfieldTextarea');
			$f->attr('name', '_refreshToken');
			$f->label = 'Refresh Token (populated automatically)';
			$f->attr('value', $module->getRefreshToken());
			$f->collapsed = Inputfield::collapsedYes;
			$fs->add($f);
		}

		$module->saveAccessToken();

		$reAuth = $module->authConfig && md5($module->authConfig) != $module->authConfigHash;
		if(!$reAuth) $reAuth = $module->scopesHash && $module->scopesHash != $module->scopesHash();
		if(!$reAuth) $reAuth = $input->post('_reauth') ? true : false;
		if($reAuth) $session->setFor($this, 'authConfigTest', 1);

		if(!$input->requestMethod('POST') && ($input->get('code') || $session->getFor($this, 'authConfigTest'))) {
			$session->setFor($this, 'authConfigTest', null);
			$test = json_decode($module->authConfig, true);
			if(is_array($test) && count($test)) {
				$module->accessToken = '';
				$module->getClient();
				// $this->message("Setup new access token");
			} else {
				$this->error('Authentication config did not validate as JSON, please check it');
				$this->warning($module->authConfig);
			}
		}

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', 'redirectURL');
		$f->label = $this->_('Redirect URL (auto-generated)');
		$f->description = $this->_('Please provide this URL to Google as part of your API configuration.');
		$f->attr('value', $redirectURL);
		$f->notes = $this->_('Note: this is generated automatically and you should not change it.');
		if($module->authConfig) {
			$f->collapsed = Inputfield::collapsedYes;
		} else {
			$this->warning(sprintf($this->_('FYI: Your “Authorized redirect URI” (for Google) is: %s'), "\n$redirectURL"));
		}
		$fs->add($f);

		/** @var InputfieldRadios $f */
		$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'configUserID');
		$f->label = sprintf($this->_('Only superuser “%s” may view and configure this module?'), $user->name);
		$f->description = $this->_('Answering “Yes” here ensures that other superusers in the system cannot view your client secret JSON or modify module settings.');
		$f->addOption($this->wire('user')->id, sprintf($this->_('Yes (%s)'), $user->name));
		$f->addOption(0, $this->_('No'));
		$f->attr('value', $module->configUserID);
		$f->icon = 'user-circle-o';
		$f->set('themeOffset', 1);
		if(!$module->configUserID) $f->collapsed = Inputfield::collapsedYes;
		$form->add($f);

		$form->add($this->configTests());
	}

	/**
	 * Google client API tests
	 * 
	 * @throws WireException
	 * @throws WirePermissionException
	 * @return InputfieldFieldset
	 *
	 */
	protected function configTests() {

		$modules = $this->wire('modules');
		$input = $this->wire('input');
		$session = $this->wire('session');
		$requiresLabel = $this->_('Requires that at least one of the following URLs is in your “scopes” field above:');

		/** @var InputfieldFieldset $fs */
		$fs = $modules->get('InputfieldFieldset');
		$fs->attr('name', '_configTests');
		$fs->label = $this->_('API tests');
		$fs->collapsed = Inputfield::collapsedYes;
		$fs->description = $this->_('Once you have everything configured, it’s worthwhile to test APIs here to make sure everything is working with your Google credentials.');
		$fs->icon = 'certificate';
		$fs->set('themeOffset', 1);

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', '_testCalendar');
		$f->label = $this->_('Test Google Calendar API');
		$f->description =
			$this->_('Open a Google Calendar, go to the settings and get the “Calendar ID” or “Shareable link” URL to the calendar, and paste it below.') . ' ' .
			$this->_('This test will show you the next 10 upcoming events in the calendar.');
		$f->notes = $requiresLabel .
			"\nhttps://www.googleapis.com/auth/calendar" .
			"\nhttps://www.googleapis.com/auth/calendar.readonly";
		$f->collapsed = Inputfield::collapsedYes;
		$fs->add($f);

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', '_testSheets');
		$f->label = $this->_('Test Google Sheets API');
		$f->description =
			$this->_('Open a Google Sheets spreadsheet and copy/paste the URL from your browser address bar into here.') . ' ' .
			$this->_('This test will show you some stats about the spreadsheet.');
		$f->notes = $requiresLabel .
			"\nhttps://www.googleapis.com/auth/spreadsheets" .
			"\nhttps://www.googleapis.com/auth/spreadsheets.readonly";
		$f->collapsed = Inputfield::collapsedYes;
		$fs->add($f);

		if($input->post('_testCalendar')) {
			$session->setFor($this, 'testCalendar', $input->post('_testCalendar'));
		} else if($session->getFor($this, 'testCalendar')) {
			$calendarUrl = $session->getFor($this, 'testCalendar');
			$session->removeFor($this, 'testCalendar');
			$calendar = $this->module->calendar();
			$calendar->setCalendar($calendarUrl);
			$this->warning($calendar->test(), Notice::allowMarkup);
		}

		if($input->post('_testSheets')) {
			$session->setFor($this, 'testSheets', $input->post->url('_testSheets'));
		} else if($session->getFor($this, 'testSheets')) {
			$spreadsheetUrl = $session->getFor($this, 'testSheets');
			$session->removeFor($this, 'testSheets');
			$sheets = $this->module->sheets($spreadsheetUrl);
			$this->warning($sheets->test(), Notice::allowMarkup);
		}

		return $fs;
	}


}