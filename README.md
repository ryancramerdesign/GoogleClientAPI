# Google Client API for ProcessWire

This module manages and simplifies the authentication and setup between 
Google service APIs and ProcessWire, and it is handled in the module settings. 
The module also currently provides common API calls for Google Sheets and 
Google Calendar via dedicated classes, enabling a simpler interface to these
services than is available through Google’s client libraries. 

This package also includes the MarkupGoogleCalendar module, which is useful
for rendering calendars in your site, but also serves as a good demonstration
of using the GoogleClientAPI module. 

This module can optionally be installed via Composer and was originally created
in part as a demonstration of this. 

### Requirements

- ProcessWire 3.0.123 or newer
- PHP 5.4 or newer (PHP 7+ recommended)
- Composer

----------------------

## Installation

### Step 1: Install module files

Choose either installation via Composer or installation via ZIP file download:

**A. Installation via Composer**: 

In your ProcessWire installation root execute the following command from the terminal:

~~~~~~
composer require processwire/google-client-api
~~~~~~

**B. Installation via ZIP file download**

- Download the module’s [ZIP file](https://github.com/ryancramerdesign/GoogleClientAPI/archive/master.zip), 
  unzip and place the files in a new directory named: 
  `/site/modules/GoogleClientAPI/`
- Download or clone [Google’s PHP client library](https://github.com/googleapis/google-api-php-client)
  and unzip/place files into a directory named:
  `/site/google-api-php-client/`
  
### Step 2: Enable in ProcessWire  

- Login to your ProcessWire admin and go to: Modules > Refresh. 
- Click “Install” next to the *GoogleClientAPI* module (which should appear on 
  the “Site” tab). 
- You should now be at the module’s configuration screen, remain here for step 3.   
  
### Step 3: Enable APIs in your Google account 

*This step assumes you are already at your GoogleClientAPI module configuration screen.
Please note that Google often changes small details in their console control panel so some of 
the details of the instructions may vary slightly.*

1. Open a new/window tab in your browser, go to [Google Console](https://console.developers.google.com) 
   and login. Google may want you to "Enable and Manage APIs" or accept some terms or the like. 
   You know the drill.
   (If you see a “Sign up for Free Trial” button, please note that’s NOT needed for this module.)

2. Once API access is enabled, click to “Create a new project”.
   Give your project a name, or accept the default, and continue.

3. Next, at the “Add credentials to your project” screen…

   - For “What API are you using?” select select the APIs you intend to use. The two most common
     to use with this module are the Calendar API or the Sheets API. 
   - For “Where will you be calling the API from?” select “Web server”.
   - For “What data will you be accessing?” select “User data”.

4. Click the “What credentials do I need?” button. This will take you to a screen where you 
   "Add credentials to your project":
   
   - Under the headline "Create an OAuth 2.0 client ID" look for the field "Authorized redirect URIs".
   - Switch to your ProcessWire screen and copy the redirect URL that you see at the bottom 
     of the *GoogleClientAPI* module settings.
   - Paste this URL into the "Authorized redirect URIs" field that you see in your Google window.
   - Click the "Create client ID" button, which will take you to the "Set up the OAuth 2.0 consent screen."

5. On the "Set up the OAuth 2.0 consent" screen…

   - Under the "Product name shown to users", enter a label that of your choice, appropriate for
     your intended APIs (i.e. "Events calendar" or "Form spreadsheets", etc).
   - Click the “submit” button when done.

6. Now you will be on the "Download credentials" section.

   - Click the "Download" button to download the JSON file that it provides to you.
   - Make note of where it saves to as you will need it shortly. Or just go ahead and open it 
     up in a text editor now if you'd like for the next step (4).
   - Click the "Done" button.
   
### Step 4: Configure *GoogleClientAPI* module

*For this step, go back to your ProcessWire admin > Google Client API module settings.*

1. The first field in the module configuration is called “Scopes”, and this is essentially
   a list of URLs where you define what services from Google that you’ll be using. Here is 
   a list of [all available scopes from Google](https://developers.google.com/identity/protocols/googlescopes). 
   Copy and paste in the scopes that you want to use. 
   While you can use any of the scopes with this module, the ones that correspond with the current
   API functions available in the *GoogleSheets* and *GoogleCalendar* classes in this module are:
   
   - `https://www.googleapis.com/auth/spreadsheets`
   - `https://www.googleapis.com/auth/calendar.readonly`
   
2. Complete the rest of the module settings:

   - For "Application name" enter the "Product name shown to users" that you provided to Google. 
     For example, "Events calendar", "Form spreadsheets", etc.
     
   - For "Authentication config / client secret JSON", open the JSON file that you downloaded 
     from Google earlier into a text editor. Copy the JSON data from the file and paste into 
     this field.
     
   - Click the "Submit" button to save your module configuration.

2. After clicking Submit, it will now take you to a Google authentication page.

   - Login with the account that you want to use your chosen APIs from (if not logged in already).
   - When Google asks you to allow access to the APIs, review to make sure everything is correct
     and click the "Allow" button.

3. If everything above was successful you should now be back at the module configuration screen 
   and you are now ready to start using the *GoogleClientAPI* module. 
   
### Step 5: Test things out    

- Assuming you have enabled spreadsheets or calendar in your "scopes" during configuration, you
  can use the built-in API tests available on the *GoogleClientAPI* module configuration screen 
  that you likely already have open. Scroll to the bottom and you'll see it. 
  
- The API tests section includes instructions, so we won't duplicate them here. You basically 
  paste in a URL to your spreadsheet or calendar and submit the form to see if it can 
  successfully connect to the resource. 

- If you encounter errors here, it will indicate that the module is not yet ready to use and
  you need to review all of your settings in the module and API credentials at Google. 

----------------------

# Markup Google Calendar module

This add-on helper module renders a calendar with data from Google. This module demonstrates 
use of and requires the *GoogleClientAPI* module, which must be installed and configured
prior to using this module. It requires the following scope in GoogleClientAPI:
`https://www.googleapis.com/auth/calendar.readonly`



See the `_mgc-event.php` file which is what handles the output markup. You should
copy this file to `/site/templates/_mgc-event.php` and modify it as you see fit. 
If you do not copy to your /site/templates/ directory then it will use the 
default one in the module directory.

Please note that all render methods cache output by default for 1 hour. You can 
change this by adjusting the $cacheExpire property of the module. 

## Usage

~~~~~
<?php
$cal = $modules->get('MarkupGoogleCalendar');
$cal->calendarID = 'your-calendar-id'; 
$cal->cacheExpire = 3600; // how many seconds to cache output (default=3600)
$cal->maxResults = 100; // maximum number of results to render (default=100)

// use any one of the following
echo $cal->renderMonth(); // render events for this month
echo $cal->renderMonth($month, $year); // render events for given month
echo $cal->renderDay(); // render events for today
echo $cal->renderDay($day, $month, $year); // render events for given day
echo $cal->renderUpcoming(10); // render next 10 upcoming events
echo $cal->renderRange($timeMin, $timeMax); // render events between given min/max dates/times
~~~~~

More details and options can be found in the phpdoc comments for each
of the above mentioned methods. 