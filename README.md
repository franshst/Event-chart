Event chart is a Joomla front end module, to be used with Joomla Events Booking.
It displays a line graph of cumulative registrants with respect to the registration date, where the horizontal axis is in days with respect to the start of the event.


See https://joomdonation.com/joomla-extensions/events-booking-joomla-events-registration.html

Currently in testing stage, use with caution.

Usage:
After install, go to Website Modules, and setup the module by giving your default filters. Place your module where you need it. Enjoy!

Remarks on use or bugs can be placed in the github issues, no guaranty I can work on it though.

Building:
Needs Python 3 and uglify-js (`npm install -g uglify-js`). The build fails if uglify-js is missing.
Run `python3 build.py <version>` (e.g. `python3 build.py 0.1.4`). It minifies js/mod_eventchart.js and creates mod_eventchart.zip, mod_eventchart.xml and eventchart_update.xml in the repository root.

Deploying:
Commit your changes first, then authenticate the GitHub CLI (`gh auth login`).
Run `./deploy.sh <version>` (e.g. `./deploy.sh 0.1.4`; a leading `v` is optional). It calls build.py, takes the release notes from the commit messages since the latest GitHub release, creates and pushes tag `v<version>`, and publishes mod_eventchart.zip and eventchart_update.xml as a prerelease.
