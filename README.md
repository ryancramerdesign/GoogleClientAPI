# Google Client API for ProcessWire

This module manages and simplifies the authentication and setup between 
Google service APIs and ProcessWire, and it is handled in the module settings. 
The module also currently provides common API calls for Google Sheets and 
Google Calendar via dedicated classes, enabling a simpler interface to these
services than is available through Google’s client libraries. 

This package also includes the MarkupGoogleCalendar module, which is useful
for rendering calendars in your site, but also serves as a good demonstration
of using the GoogleClientAPI module. 

### Requirements

- ProcessWire 3.0.123 or newer
- PHP 5.4 or newer (PHP 7+ recommended)
- A Google account that you want to enable APIs for

----------------------

## Installation

Google sometimes changes things around in their APIs interface, though the essence remains the same. 
These instructions have gone through 3 iterations since 2015 to keep them up to date with Google's 
changes, and the current iteration was last updated July 22, 2019. If you encounter significant 
differences on the Google side, please let us know. 

### Step A: Install module files

- Download the module’s [ZIP file](https://github.com/ryancramerdesign/GoogleClientAPI/archive/master.zip), 
  unzip and place the files in a new directory named: 
  `/site/modules/GoogleClientAPI/`
  
- Login to your ProcessWire admin and go to: Modules > Refresh. 

- Click “Install” next to the *GoogleClientAPI* module (which should appear on 
  the “Site” tab). 
  
- You should now be at the module’s configuration screen, remain here for the next step.   
  
### Step B: Install Google Client library

- On the module configuration screen, you should now see a checkbox giving you the option to 
  install the Google PHP API Client library. 
  
- Proceed with installing the library, either by checking the box and clicking Submit, or 
  downloading and installing yourself to the location it provides. 
  
- Note that the library is quite large, so it may take a few minutes to complete installation.  

### Step C: Enable APIs in Google Console

1. Open a new/window tab in your browser, go to [Google Console](https://console.developers.google.com) 
   and login (if not already). It may ask you to agree to terms of service—check the box and continue. 
   
2. Now you will be in the “Google API and Services” Dashboard. It will ask you to select a project. 
   Chances are you don't yet have one, so click **“Create”**.
   
3. You should now be at the “New Project” screen. Optionally modify the project name or location, or 
   just accept the defaults. Then click the **“Create”** button to continue.
     
   *In my case, I entered "ProcessWire website services" for the project name and left the 
   location as-is.*
   
4. Now you'll be back at the Dashboard and there will be a link that says 
   **“Enable APIs and Services”**, go ahead and click that link to continue. 
   
5. The next screen will list all the Google APIs and services. Click on the API/service that you’d 
   like to enable. 
   
6. On the screen that follows the service you clicked, click the **“Enable”** button to enable the 
   API for that service. 
     
### Step D: Creating credentials in Google Console     
   
1. After enabling your chosen API service(s), the next screen will show a notification that says:
   
   > To use this API, you may need credentials. Click 'Create credentials' to get started. 
   
   Go ahead and click the **“Create credentials”** button as it suggests and fill in the following
   inputs that it displays to you:
   
   - **Which API are you using?** — Select the API you want to use. 
   - **Where will you be calling the API from?** — Select “Web server”.
   - **What data will you be accessing?** — Select “User data”.
   
   Then click the **“What credentials do I need?”** button. 
   
2. After clicking the button above, it may pop up a box that says “Set up OAuth consent screen”, 
   in which case you should click the **“Set up consent screen”** button. The “OAuth consent 
   screen” has several inputs, but you don't need to fill them all in if you don't want to. 
   I do recommend completing the following though:
   
   - **Application name:** You can enter whatever you'd like here, but in my case I entered:
     “ProcessWire Google Client API”. 
     
   - **Application logo:** you can leave this blank.
   
   - **Support email:** you can accept the default value. 
   
   - **Scopes for Google APIs:** leave as-is, you'll be completing this part in ProcessWire.
   
   - **Authorized domains:** Enter the domain name where your website is running and hit enter. 
       If it will be running at other domains, enter them as well and hit enter for each. 
     
   *The next 3 inputs are only formalities. Only you will be seeing them, so they aren't really
   applicable to our project, but we have to fill them in anyway. You can put in any URLs on 
   your website that you want to.* 
     
   - **Application homepage:** Enter your website’s homepage URL. This will probably the the same
     as your first “authorized domain” but with an `http://` or `https://` in front of it. 
     
   - **Application privacy policy link:** Enter a link to your website privacy policy, or some  
     other URL in your website, if you don't have a privacy policy. Only you will see it.
     
   - **Application terms of service:** Enter a URL or leave it blank.   
   
   After completing the above inputs, click the **“Save”** button. 
   
3. The next screen will present you with a new **“Create Credentials”** button. 
   Click that button and it will reveal a drop down menu, select **“OAuth client ID”**. 
   Complete these inputs on the screen that follows: 
   
   - **Application type:** Select “Web application”
   
   - **Name:** Accept the default “Web client 1”, or enter whatever you’d like.
  
   - **Authorized JavaScript origins:** You can leave this blank.
   
   - **Authorized redirect URIs:** To get this value, you'll need switch windows/tabs to go back 
     to your ProcessWire Admin in: Modules > Configure > GoogleClientAPI. There will be a 
     notification at the top that says this: 
     ~~~~~ 
     Your “Authorized redirect URI” (for Google) is:  
     https://domain.com/processwire/module/edit?name=GoogleClientAPI
     ~~~~~
     
     Copy the URL out of the notification that appears on your screen and paste it into the
     “Authorized redirect URIs” input on Google’s screen, and hit ENTER. 
     
   - If you see a **“Refresh”** button, go ahead and click it. 
  
4. When you've filled in all of the above inputs, you should see a **“Create OAuth Client ID”** 
   button, please go ahead and click it to continue, and move on to step E below. 
   
   
### Step E: Download credentials JSON file from Google
  
1. If the next screen says “Download Credentials”, go ahead and click the **“Download”** 
   button now. It will download a `client_id.json` file (or some other named JSON file) to 
   your computer. 
  
   *If you don't see a download option here, it’s okay to proceed, you'll see it on the next step.*
  
2. Click the **“Done”** button. You will now be at the main “Credentials” screen which lists 
   your OAuth clients. 
   
3. If you haven't yet downloaded the JSON file, click the little download icon that appears on 
   the right side of the “OAuth 2.0 Client IDs” table on this screen to download the file. Note
   the location of the file, or go ahead and load it into a text editor now. We'll be returning
   to it shortly in step F below. 

4. You are done with Google Console though please stay logged in to it. For the next step we'll 
   be going back into the ProcessWire admin.   

### Step F: Authenticating ProcessWire with Google   

*Please note: In this step, even though you'll be in ProcessWire, you'll want to be sure you are still logged 
in with the Google account that you were using in step 3.*
 
1. Now we will fill in settings on the ProcessWire side. You'll want to be in the GoogleClientAPI 
   module settings in your ProcessWire admin at: Modules > Configure > GoogleClientAPI. 
   Complete the following inputs in the module settings: 
  
   - **Application name:** Enter an application name. Likely you want the same one you entered 
     into Google, i.e. “ProcessWire Google Client API”, or whatever you decided. 
  
   - **Scopes (one per line):** for this field you are going to want to paste in one or more 
     scopes (which look like URLs). The scopes are what specifies the permissions you want for 
     the APIs you have enabled. Determine what scopes you will be using and paste them into 
     this field. There's a good chance you'll only have one. 
     [View all available scopes](https://developers.google.com/identity/protocols/googlescopes).
     Examples of scopes include: 
    
     `https://www.googleapis.com/auth/calendar.readonly` for read-only access to calendars.  
     `https://www.googleapis.com/auth/spreadsheets` for full access to Google Sheets spreadsheets.   
     `https://www.googleapis.com/auth/gmail.send` for access to send email on your behalf.  
      
    - **Authentication config / client secret JSON:** Open/load the JSON file that you downloaded
      earlier into a text editor. Select all, copy, and paste that JSON into this field. 
    
    Click the **“Submit”** button to save the module settings. 
    
2. After clicking the Submit button in the previous step, you should now find yourself at a 
   Google screen asking you for permission to access the requested services. **Confirm all access.**
  
   - Depending on the scope(s) you requested, it may tell you that your app is from an unverified 
     developer and encourage you to back out. It might even look like a Google error screen, but 
     don't worry, all is well — find the link to proceed, hidden at the bottom. Unless you aren't 
     sure if you trust yourself, keep moving forward with whatever prompts it asks to enable access. 
  
   - Once you have confirmed the access, it will return you to the GoogleClientAPI module configuration 
     screen in ProcessWire. 
       
3. Your GoogleClientAPI module is now configured and ready to test!
   
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