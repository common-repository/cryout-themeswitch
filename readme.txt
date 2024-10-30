=== Cryout Theme Switch ===
Contributors: Cryout Creations
Donate link: https://www.cryoutcreations.eu/donate/
Tags: theme, admin, switch, swap
Requires at least: 4.5
Tested up to: 6.6
Stable tag: 1.0.4
Requires PHP: 5.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Quickly and easily swap between themes. Adds a theme switcher to the WordPress Admin Bar with all parent/child themes, filtering and favorites list. Compatible with WordPress 4+

== Description ==

Quickly and easily swap between themes. Adds a theme switcher to the WordPress Admin Bar with all parent/child themes, filtering and favorites list. Compatible with WordPress 4+
Performs transparent theme switch by redirecting to the initial URL on both the frontend and in the dashboard.

== Installation ==

= Automatic installation =

1. Navigate to Plugins in your dashboard and click the Add New button.
2. Type in "Cryout ThemeSwitch" in the search box on the right and press Enter, then click the Install button next to the plugin title. 
3. After installation Activate the plugin, and the quick switcher will appear in the admin bar.

= Manual installation =

1. Upload `cryout-themeswich` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress and the quick switcher will appear in the admin bar.


== Screenshots ==

1. Plugin preview - Theme Search
2. Plugin preview - Themes list

== Changelog ==

= 1.0.4 =
* Fixed 'Creation of dynamic property' deprecation warnings with PHP 8.3

= 1.0.3 =
* Fixed network-enabled themes detection on Multisite installs

= 1.0.2 =
* Fixed jQuery deprecation warnings with WordPres 5.6
* Fixed child theme vertical alignment in favorites list
* Bumped supported WordPress version to 5.6

= 1.0.1 =
* Added visual indicators for child themes in favorites list
* Added parent theme slug indicator for child themes
* Added activation check for roles capable of switching themes only
* Improved themes list sorting
* Fixed saving favorites list fails with WordPress 5.3
* Fixed notice of undefined index 'path' sometimes being displayed on frontend on theme switch

= 1.0.0 =
* Added configurable favorite themes admin submenu list (and settings page)
* Added extra information for identically named themes
* Improved active theme emphasis
* Improved theme search autofocus
* Fixed themes with identical names all being indicated as active when one is

= 0.5.3 = 
* Fixed redirection with subfolder installs

= 0.5.2 =
* Added redirect to source page when switching themes
* Fixed pressing Enter on the theme filter input submitting ghost form

= 0.5.1 = 
* Added scrollbar when the themes list is too long to fit the screen
* Added switch menu on the frontend visible admin bar as well
* Added accessibility support

= 0.5 =
* Initial release. 
* Loosely based on Matty's Theme QuickSwitch plugin
