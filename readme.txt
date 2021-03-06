=== Plugin Name ===
Contributors: benbalter
Donate link: http://ben.balter.com/donate/
Tags: encryption, security, private, lock, posts, 
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 0.1

WordPress Plugin that provides data-at-rest encryption of post content

== Description ==

Encrypts selected WordPress posts prior to writing to the database. Provides an additional layer of security for storing sensitive information in shared hosting and other at-risk environments.

Uses 256-bit AES encryption. [More information on encryption used](http://www.itnewb.com/tutorial/PHP-Encryption-Decryption-Using-the-MCrypt-Library-libmcrypt)

An encryption metabox is added to the edit-post screen. Simply click the checkbox to enable encryption and enter a password. You will be prompted for the password prior to reading or editing.

Note: because the password is not stored, if you lose the password, you will be unable to decrypt the post.

While theoretically **more** secure than storing posts in plain text, this plugin is not a replacement for traditional secure data storage. Use at your own risk. Provided as is.

== Changelog ==

= 0.1 =
* Initial release

== Upgrade Notice ==