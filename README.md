# UM User Visitors and Visits
Extension to Ultimate Member for the display of User Profile Visitors and User Profile Visits.

## UM Settings -> Extensions -> User Visitors & Visits
1. User Roles - Select the User Roles for display of the Visits & Visitors forms in User Profiles.
2. Time limit new entries - Visitor time limit for new visit entries to be saved. Select a number in minutes/hours ( 5 minutes to 24 hours ). During this time period duplicate visits are not saved as a new visit. Disable time limit, set time to 0
3. Visitors - Member Directory Form - Create a Member Directory Form for display of User Profile Visitors.
4. Visits - Member Directory Form - Create a Member Directory Form for display of User Profile Visits.
5. Display days ago in Members Directory - If not selected WP date format will be used.
6. Summary of User counters before the Directory - If not selected User counters will use "today/week/month/total" format otherwise "last days" format.
7. Number of User counters for "last days" format - Enter the number of days for User counters display.
8. WP All Users columns - Select the sortable columns for Plugin's UM Predefined fields in WP All Users page.

## UM Settings -> Appearance -> Profile Menu
1. Who can see Visitors Tab?
2. Who can see Visits Tab?

## Member Directory
1. Create two new Directories for display of Visitors and Visits
2. Assign these Forms in the Plugin settings.
3. Select Directory sorting at least 'Visitor times' and 'Visit times'

## UM Predefined Fields in Forms Builder:
1. Last activity: vv_last_activity
2. Last logout: vv_last_logout
3. Last update: vv_last_update
4. Last login by UM: _um_last_login

## UM Dashboard
1. Statistic metabox

## Shortcodes
1. [vv_show_activity] Display of last user activity in human format.
2. [vv_show_total_visits]Header text[/vv_show_total_visits]
3. [vv_show_total_visitors]Header text[/vv_show_total_visitors]
4. [vv_show_key_visits key="week"]You have been looking at %s other profiles this week[/vv_show_key_visits] Possible keys: today, week, month, total
5. [vv_show_key_visitors key="today"]Your profile has been viewed %s times today[/vv_show_key_visitors] Possible keys: today, week, month, total
6. [vv_show_daily_visits limit="7"]Your number of visits to other profiles each day last week[/vv_show_daily_visits]
7. [vv_show_daily_visitors limit="7"]Your number of visitors to your profile each day last week[/vv_show_daily_visitors]
8. [vv_dashboard] Header text [/vv_dashboard]

## WP All Users
1. Sortable columns for the Plugin's UM Predfined fields
2. Modal links for Visits and Visitors with display of all shortcodes 

## Translations
1. Textdomain: um-visitors

## Updates
1. Version 1.1.0 Code improvements
2. Version 1.2.0 Reduced info in UM Dashboard. New shortcode [vv_dashboard]
3. Version 1.3.0 CSS classes, Dashboard header, New counter options for Directory, Who can see Vists/Visitors Tab?
4. Version 1.4.0 Code improvements, Members Directory hiding Users account option tested.
5. Version 1.5.0 Removal of deleted users from Directory
6. Version 1.6.0 Shortcode formatting
7. Version 1.6.1 UM Dashboard column 3

## Installation
1. Install by downloading the plugin ZIP file and install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
2. Activate the Plugin: Ultimate Member - User Visitors and Visits

