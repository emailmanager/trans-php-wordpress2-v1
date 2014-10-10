=== Plugin Name ===
Contributors: tmertz
Tags: wp_mail, emailmanagerapp, replacement, emailmanager, mail, smtp,
Requires at least: 2.8
Tested up to: 3.1
Stable tag: 1.3.2

A simple plugin that replaces the default wp_mail function with Emailmanager (<a href="http://emailmanager.com/">http://emailmanager.com/</a>).
== Description ==

This plugin replaces wp_mail with a custom function that utilizes emailmanager.com to send email to your users.
There really isn't much more to say about the plugin.

= REQUIRED =
* PHP5
* cURL for PHP5

The plugin is proudly sponsored by <a href="http://stupid-studio.com/">Stupid Studio</a>, a mediahouse based in Denmark.

== Installation ==

1. Simply install from within WordPress.
1. Go to the admin page for Emailmanager Mail Replacement and fill out the fields.
1. Enable the plugin from the plugin admin page.

== Changelog ==

= 1.3.2 =
* Fixed a bug that occurred when you tried to test the plugin and had WordPress installed somewhere else than the root of your server. Thank you to <a href="http://wordpress.org/support/profile/theferf">theferf</a> for reporting the bug.
* Added some functionality to deal with issues arising as a result of some plugins trying to send to more than one email at a time (I'm looking at you Contact Form 7 :P ).
* Also fixed the 'Purge Logs' button, as it was broken in latest release.

= 1.3.1 =
* Fixed a slight, but annoying bug regarding the certificate file.
* Slimmed some of the code.

= 1.3 = 
* Fixed yet another bug with sending emails. 
* Fixed a small bug with the logging tool.

= 1.2.1 = 
* Fixed bug that prohibited plugin from sending emails.

= 1.2 =
* Added error logging, so if something doesn't work you can see it in the logs.
* Slight change to uninstall procedures.

= 1.1.1 =
* Fixed the URL for the logo for Stupid Studio.

= 1.1 =
* Added check to see whether or not the plugin is activated before replacing wp_mail.
* Also added function to enable and disable the plugin without deactivating it (deactivating the plugin deletes all information stored).

= 1.0 =
* Initial release.