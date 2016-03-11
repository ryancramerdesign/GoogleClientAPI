# Google Client and Calendar modules

## About these modules

This package includes two modules: GoogleClientAPI and MarkupGoogleCalendar.
The GoogleClientAPI is the driver here, while the MarkupGoogleCalendar
module is here to serve as a demonstration of GoogleClientAPI. The GoogleClientAPI 
module must be installed and configured before using the MarkupGoogleCalendar module.

These modules are also created as a demonstration for installation of modules via 
Composer. In this case, the modules require installation via Composer because of 
external dependencies available through Packagist, though we'll be adding support 
for other installation methods soon. 

### Requirements

- ProcessWire 3.0.10 or newer
- PHP 5.4 or newer
- Composer

----------------------

## Google Client API module

### Installation

In your ProcessWire installation root execute the following command from the terminal:

````````
composer require processwire/google-client-api
````````	

Login to your ProcessWire admin and go to: Modules > Refresh. Click "Install" next to
the Google Client API module (which should appear on the "Site" tab). 

The module now needs to be connected with a Google account. Full instructions on how 
to do this will be posted shortly at https://processwire.com/blog/.

----------------------

# Markup Google Calendar module

Renders a calendar with data from google. This module demonstrates use of
and requires the GoogleClientAPI module, which must be installed and configured
prior to using this module. 

See the `_mgc-event.php` file which is what handles the output markup. You should
copy this file to `/site/templates/_mgc-event.php` and modify it as you see fit. 
If you do not copy to your /site/templates/ directory then it will use the 
default one in the module directory.

Please note that all render methods cache output by default for 1 hour. You can 
change this by adjusting the $cacheExpire property of the module. 

## Usage

`````````````````	
<?php
$cal = $modules->get('MarkupGoogleCalendar');
$cal->calendarID = 'your-calendar-id'; // Your Google Calendar ID (default=primary)
$cal->cacheExpire = 3600; // how many seconds to cache output (default=3600)
$cal->maxResults = 100; // maximum number of results to render (default=100)

// use any one of the following
echo $cal->renderMonth(); // render events for this month
echo $cal->renderMonth($month, $year); // render events for given month
echo $cal->renderDay(); // render events for today
echo $cal->renderDay($day, $month, $year); // render events for given day
echo $cal->renderUpcoming(10); // render next 10 upcoming events
echo $cal->renderRange($timeMin, $timeMax); // render events between given min/max dates/times
``````````````````

More details and options can be found in the phpdoc comments for each
of the above mentioned methods. 