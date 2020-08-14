=== WP Fusion ===
Contributors: verygoodplugins
Tags: infusionsoft, crm, marketing automation, user meta, sync, woocommerce
Requires at least: 4.0
Tested up to: 5.4.2
Stable tag: 3.33.18
Requires PHP: 5.6

The only plugin you need for integrating your WordPress site with your CRM.

== Description ==

WP Fusion is a WordPress plugin that connects what happens on your website to your CRM. Using WP Fusion you can build a membership site, keep your customers’ information in sync, capture new leads, record ecommerce transactions, and much more.

= Features =

* Automaticaly create new contacts in your CRM when new users are added in WordPress
	* Can limit user creation to specified user roles
	* Assign tags to newly-created users
* Restrict access to content based on a user's CRM tags
	* Option to redirect to alternate page if requested page is locked
	* Shortcodes to selectively hide/show content within posts
* Apply tags when a user visits a certain page (with configurable delay)
* Configurable synchronization of user meta fields with contact fields
	* Update a contact record in your CRM when a user's profile is updated
* LearnDash, Sensei, and LifterLMS integrations for managing online courses
* Integration with numerous membership and ecommerce plugins

== Installation ==

Upload and activate the plugin, then go to Settings >> WP Fusion. Select your desired CRM, enter your API credentials and click "Test Connection" to verify the connection and perform the first synchronization. This may take some time if you have many user accounts on your site. See our [Getting Started Guide](https://wpfusion.com/documentation/#getting-started-guide) for more information on setting up your application.

== Frequently Asked Questions ==
See our [FAQ](https://wpfusion.com/documentation/).

== Changelog ==

= 3.33.18 - 7/27/2020 =
* Added HTTP API logging option
* Added LifterLMS Groups beta integration
* Added LifterLMS voucher tagging support
* Added X-Redirect-By headers when WP Fusion performs a redirect
* Added unlock utility for re-exporting Event Espresso registrations
* Improved Event Espresso performance
* Fixed Salesforce contact ID lookup failing with emails with + symbols
* Fixed auto-login warning appearing when previewing Gravity Forms forms in the admin

= 3.33.17 - 7/20/2020 =
* Added Organizer fields for syncing with Tribe Events / Event Tickets
* Added support for Groundhogg Advanced Custom Meta Fields
* Added timezone offset back to Ontraport date field conversion
* Added Refresh Tags & Fields button to top of WPF settings page
* Added notice when checking out in WooCommerce as an admin
* Added automatic detection for Formidable Forms User Registration fields
* Added out of memory and script timeout error handling to activity logs
* Added Gravity Forms referral support to AffiliateWP integration
* Added notice to LearnDash course / lesson / topic meta box showing access rules inherited from course
* Removed job_title and social fields from core WP fields on Contact Fields list
* Improved performance of update_tags webhook with ActiveCampaign
* Improved - last_updated usermeta key will be updated via WooCommerce when a user's tags or contact ID are modified (for Metorik compatibility)
* Improved - "Active" tags will no longer be removed when a MemberPress subscription is cancelled
* Improved - If the user_meta shortcode is used for a field that has never been loaded from the CRM, WP Fusion will make an API call to load the field value one time
* Improved - Updated has_tag() function to accept an array or a string
* Fixed restricted posts triggering redirects on a homepage set to Your Latest Posts in Settings >> Reading
* Fixed Groundhogg custom fields updated over the REST API not being synced back to the user record
* Fixed undefined function bp_group_get_group_type_id() in BuddyPress
* Fixed broken import tool with Drip
* Dynamic tagging bugfixes
* AgileCRM timezone tweaks

= 3.33.16 - 7/13/2020 =
* Added "Resync contact IDs for every user" batch operation
* Added "LearnDash course enrollment statuses" batch operation
* Added notice if an auto-login link is visited by a logged-in admin
* Improved query filtering on BuddyPress activity stream
* Improved - LearnDash courses, lessons, and topics will inherit access permissions from the parent course
* Improved - Split Mautic site tracking into two modes (Standard vs. Advanced)
* Improved - If API call to get user tags fails or times out, the local tag cache won't be erased
* Fixed a new WooCommerce subscription not removing the payment failed tags from a prior failed subscription for the same product
* Fixed Preview With Tag not working if the user doesn't have a CRM contact record
* Fixed restricted post category redirects not working if no tags was specified
* Fixed Hide Term on post categories hiding terms in the admin when Exclude Administrators was off
* Fixed import tool not loading more than 1,000 contacts with AgileCRM
* Fixed AgileCRM not properly looking up email addresses for some contacts
* Fixed get_tag_id() returning tag name with Groundhogg since v3.33.15
* Refactored WooCommerce Subscriptions integration and removed cron task
* Memberoni bugfixes
* Updated .pot file

= 3.33.15 - 7/6/2020 =
* Updated User.com integration for new API endpoint
* Added BuddyPress groups statuses batch operation
* Added ability to create new tags in Groundhogg via WP Fusion
* Added setting for additional allowed URLs to Site Lockout feature
* Added Generated Password field for syncing with WooCommerce
* Added Membership Level Name field for syncing with WishList Member
* Improved support for syncing phone numbers with Sendinblue
* Users added to a multisite blog will now be tagged with the Assign Tags setting for that site
* Fixed Zoho field mapping not converting arrays when field type was set to "text"
* Fixed replies from restricted bbPress topics showing up in search results
* Fixed WooCommerce attributes only being detected from first 5 products instead of 100
* Fixed deletion tags not being applied on multisite sites when a user was deleted from a blog
* Fixed MemberPress subscription data being synced when a subscription status changed from Active to Active
* Fixed duplicate tags being applied when a MemberPress subscription and transaction were created from the same registration
* Fixed Filter Queries (Advanced) hiding restricted posts in the admin
* Fixed Contact Form 7 integration running when no feeds were configured
* Fixed Woo Memberships for Teams team name not syncing when a member was added to a team
* Fixed Mautic merging contact records from tracking cookie too aggressively
* Fixed archive restrictions not working if no required tags were specified
* Event Espresso bugfixes

= 3.33.14 - 6/29/2020 =
* Added priority option for Return After Login
* Added option to set a default owner for new contacts with Zoho
* Added Membership Status field for sync with WooCommerce Memberships
* Added product variation tagging for FooEvents attendees
* Improved multiselect support with Zoho
* Improved support for syncing multi-checkbox fields with Formidable Forms
* Fixed refreshing the logs page after flushing the logs flushing them again
* Fixed Group and Group Type tags not being applied in BuddyPress when an admin accepted a member join request
* GiveWP bugfixes

= 3.33.13 - 6/23/2020 =
* Fixed invalid redirect URI connecting to Zoho
* Fixed Loopify and Zoho getting mixed up during OAuth connection process

= 3.33.12 - 6/22/2020 =
* Added Modern Events Calendar integration
* Added status indicator for Inactive people in Drip
* Improved support for Mautic site tracking
* Improved translatability and updated pot files
* Fixed updated phone to primary_phone with Groundhogg
* Fixed Paused tags not getting removed when a WooCommerce membership comes back from Paused status during a Subscriptions renewal
* Fixed "Cancelled" tags getting applied to pending members during MemberPress Membership Statuses batch operation
* Fixed duplicate log entries when updating BuddyPress profiles
* Fixed contact ID not being detected in some Mautic webhooks
* Fixed syncing multi-checkbox fields from WPForms
* Fixes for syncing expiration dates with WooCommerce Memberships
* Fixed PHP warning while submitting Formidable forms with multi-checkbox values

= 3.33.11 - 6/18/2020 =
* Fixed fatal conflict when editing menu items if other plugins hadn't been updated to the WP 5.4 menu syntax
* Fixed AgileCRM tag name validation false positive on underscore characters
* Fixed logs items per page not saving in WP 5.4.2
* Fixed compatibility with Gifting for WooCommerce Subscriptions v2.1.1

= 3.33.10 - 6/15/2020 =
* Added WooCommerce Appointments integration
* Added WP Crowdfunding integration
* Added Subscription Status field for syncing with WooCommerce Subscriptions
* Added Last Order Payment Method field for syncing with WooCommerce
* Added Last Order Total field for syncing with WooCommerce
* Added WishList Membership Statuses batch operation
* Added wpf_get_contact_id_email filter
* Added wpf_batch_objects filter
* Added tag name validation to AgileCRM integration
* Added super secret WooCommerce Subscriptions debug report
* Reduced the amount of data saved by the background worker to help with max_allowed_packet issues
* Fixed address fields not being synced back to WordPress after an admin contact save in Groundhogg
* Fixed bug in loading MemberPress radio field values from the CRM
* Fixed Active tags getting reapplied when a WooCommerce Subscription status changed to Pending Cancel
* Fixed WishList Member v3 custom fields not syncing
* Fixed WishList Member Stripe registration creating contacts with invalid email addresses
* Fixed s2Member custom fields not being synced on profile update

= 3.33.9 - 6/8/2020 =
* Added support for syncing custom event fields with Tribe Events Calendar Pro
* Added support for BuddyPress Username Changer addon
* Added option to apply tags when a user is added to a BuddyPress group type
* Added ld_last_course_enrolled field for syncing with LearnDash
* Added tagging based on assignment upload for LearnDash topics
* Added customer_id field for sync with Easy Digital Downloads
* Added Remove Tags from Customer setting to Gifting for WooCommerce Subscriptions integration
* Fixed essay-type answers not syncing properly from LearnDash quizzes
* Fixed auto-enrollments not working with TutorLMS paid courses
* Fixed import tool not loading more than 10 contacts with Mailchimp
* Fixed import tool not loading more than 100 contacts with MailerLite
* Fixed Gifting for WooCommerce Subscriptions integration not creating a second contact record for the gift recipient when billing_email was enabled for sync

= 3.33.8 - 6/2/2020 =
* WooCommerce Subscribe All the Things tags will now be applied properly during a WooCommerce Subscription Statuses batch operation
* Fixed width of tag select boxes in LearnDash settings panel when Gutenberg was active

= 3.33.7 - 6/1/2020 =
* Added Beaver Themer integration
* Added global Apply Tags to Group Members setting for Restrict Content Pro
* Added support for syncing multiple event attendees with Tribe Tickets
* Added Username for sync with Kartra
* Added wpf_salesforce_lookup_field filter
* Added staging mode notice on the settings page
* Moved LearnDash course settings onto Settings panel
* Refactored WooCommerce Memberships integration and updated tagging logic to match WooCommerce Subscriptions
* Salesforce will now default to the field configured for sync with the user_email field as the lookup field for records
* Improved logging with Salesforce webhooks
* Fixed WooCommerce billing_country not converting to full country name when field type was set to "text"
* Fixed auto-login session from form submission ending if Allow URL Login was disabled
* WishList Member bugfixes
* Fixed SSL error connecting to Zoho's Indian data servers

= 3.33.6 - 5/25/2020 =
* Added setting for Prevent Reapplying Tags (Advanced)
* Added GiveWP Donors and GiveWP Donations batch operations
* Added Total Donated and Donations Count fields for sync with GiveWP
* Added Pending Cancellation and Free Trial status tagging for WooCommerce Memberships
* Added wpf_disable_tag_multiselect filter
* Added CloudFlare detection to webhook testing tool
* Added global Apply Tags to Customers setting for Easy Digital Downloads
* GiveWP integration will now only sync donor data for successful payments
* Improved error handling for invalid tag names with Infusionsoft
* Improved support for multiselect fields with Contact Form 7
* Fixed Filter Queries not working on search results in Advanced mode
* Fixed bug causing failed contact ID lookup to crash form submissions
* Fixed Infusionsoft not loading tag names with Hebrew characters
* Klaviyo bugfixes

= 3.33.5 - 5/18/2020 =
* Added Give Recurring Donations support
* Added Pods user fields support
* Added Remove Tags option to Restrict Content Pro integration
* Added Payment Failed tagging to Paid Memberships Pro
* Added "Paid Memberships Pro membership meta" batch operation
* Refactored and optimized Paid Memberships Pro integration
* Improved error handling for Salesforce access token refresh process
* Improved Restrict Content Pro inline documentation
* Improved filtering tool on the All Users list
* Offending file and line number will now be included on PHP error messages in the logs
* Added alternate method back to the batch exporter for cases when it's being blocked
* Fixed Cancelled tags getting applied in Paid Memberships Pro when a member expires
* Fixed Filter Queries not working on the blog index page
* Maropost bugfixes

= 3.33.4 - 5/11/2020 =
* Facebook OG scraper will now bypass access rules if SEO Show Excerpts is on
* Added validation to custom meta keys registered for sync on the Contact Fields list
* Added compatibility notices in the admin when potential plugin conflicts are detected
* Updated Fluent Forms integration
* Updated MemberPress membership data batch process to look at transactions in addition to subscriptions
* LearnDash enrollment transients are now cleared when a user is auto-enrolled into a group
* Intercom integration will now force the v1.4 API
* Fixed Spanish characters not showing in Infusionsoft tag names
* Fixed logs showing unenrollments from LearnDash courses granted by LearnDash Groups
* Fixed warning when using Restrict Content Pro and the Groups addon wasn't active
* Fixed guest checkout tags not being applied in Maropost
* Fixed set-screen-option filter not returning $status if column wasn't wpf_status_log_items_per_page (thanks @Pebblo)

= 3.33.3 - 5/5/2020 =
* Fixed an empty WooCommerce Subscriptions Gifting recipient email field on checkout overwriting billing_email
* Fixed Infusionsoft form submissions starting auto-login sessions even if the setting was turned off

= 3.33.2 - 5/4/2020 =
* Added WP-Members integration
* Added Users Insights integration
* Added WooCommerce Shipment Tracking integration
* Added event check-in and checkout tagging for Event Espresso
* Added dynamic tagging support for Event Espresso
* Added Remove Tags option for WooCommerce Memberships for Teams integration
* Added Team Name field for sync to WooCommerce Memberships for Teams integration
* Added support for tagging on Stripe Payment Form payments with Gravity Forms
* Fixed "Remove Tags" setting not being respected during a MemberPress Memberships batch operation
* Fixed Ultimate Member linked roles not being assigned when a contact is imported via webhook
* Fixed welcome emails not being sent by users imported from a Salesforce webhook with multiple contacts in the payload

= 3.33.1 - 4/27/2020 =
* Fixed fatal error in Teams for WooCommerce Memberships settings panel

= 3.33 - 4/27/2020 =
* Added WP ERP CRM integration
* Added Gifting for WooCommerce Subscriptions integration
* Added Events Manager integration
* Added WPComplete tagging for course completion
* Added support for WPForo usergroups auto-assignment via tag
* Added PHP error handling to logger
* Added Double Optin setting to Mailchimp integration
* Added Time Zone and Language fields for Infusionsoft
* Badges linked with tags in myCred will now be removed when the linked tag is removed
* Improvements to asynchronous checkout process
* Fixed Hide From Menus filter treating a taxonomy term as a post ID for access control
* Fixed Gravity Forms feeds running prematurely on pending Stripe transactions

= 3.32.3 - 4/20/2020 =
* Added Apply Tags - Profile Complete setting to Ultimate Member
* Updated WishList member integration for v3.x
* Translatepress language code can now be loaded from the CRM
* Removed "Profile Update Tags" setting
* Fixed coupon_code not syncing with WooCommerce
* Fixed unnecessary contact ID lookup in user import process

= 3.32.2 - 4/17/2020 =
* Added wpf_woocommerce_user_id filter
* Fixed MailerLite subscriber IDs not loading properly on servers with PHP_INT_MAX set to 32
* Fixed Status field not updating properly with Drip
* Fixed fatal error checking if order_date field was enabled for sync during a WooCommerce renewal payment

= 3.32.1 - 4/13/2020 =
* Added fallback method for background worker in case it gets blocked
* Added Filter Queries setting to Beaver Builder Posts module
* Added support for defining WPF_LICENSE_KEY in wp-config.php
* Added debug tool for MailerLite webhooks
* Added Status field for syncing with Drip
* Added support for wpForo User Custom Fields
* WooCommerce Subscription renewal dates will now be synced when manually edited by an admin
* Improved importer tool with ActiveCampaign
* Improved logging for MailerLite webhooks
* Fixed Ultimate Member registrations failing to sync data with multidimentional arrays
* Fixed optin_status getting saved to contact meta with Groundhogg
* Fixed "Cancelled" tags getting applied when a WooCommerce subscription was trashed
* Fixed PHP warning in updater when license wasn't active
* Fixed CRM field labels not showing in Caldera Forms
* Fixed Mautic not importing more than 30 contacts using import tool

= 3.32 - 4/6/2020 =
* Added Loopify CRM integration
* Added support for Advanced Forms Pro
* Added Set Current User option to auto-login system
* Added Send Confirmation Emails setting for MailPoet
* Added Enable Notifications option for MailerLite import webhooks
* s2Member membership level tags will now be applied when an s2Member role is changed
* Moved logs to the Tools menu
* Removed bulk actions from logs page
* Updated admin menu visibility interfaces for WP 5.4
* Fixed metadata loaded from the CRM into Toolset Types user fields not saving correctly
* Fixed temporary passwords getting synced when a password reset was requested in Ultimate Member
* Fixed sub-menu items not being hidden if parent menu item was hidden
* Fixed Gravity Forms Entries batch operation not detecting all entries
* Fixed order_id and order_date not syncing during a WooCommerce Subscriptions renewal order
* Fixed WooCommerce Subscription product name not being synced when a subscription item is switched
* Fixed email address changes not getting synced after confirmation via the admin user profile

= 3.31 - 3/30/2020 =
* Added Quentn CRM integration
* Improved support for multiselect fields in Gravity Forms
* Improved Trial Converted tagging for MemberPress
* Fixed Defer Until Activation not working with Ultimate Member when a registration tag wasn't specified
* Fixed affiliate cookies not being passed to asynchronous WooCommerce checkouts

= 3.30.4 - 3/23/2020 =
* Added WP Simple Pay integration
* Added Apply Tags on View option for taxonomy terms
* contactId can now be used as a URL parameter for auto-login with Infusionsoft
* Contacts will no longer be created in Ontraport without an email address
* Removed non-editable fields from Ontraport fields dropdowns
* Improved Return After Login feature with LearnDash lessons
* Fixed lead source variables not syncing to Ontraport
* Fixed lead source tracking data not syncing during registration

= 3.30.3 - 3/20/2020 =
* Added additional tagging options for WooCommerce Subscribe All The Things
* Added WPGens Refer A Friend integration
* Fixed issue with saving variations in WooCommerce 4.0.0 causing variations to be hidden
* Fixed Long Text type fields not being detected with WooCommerce Product Addons
* Fixed duplicate content in Gutenberg block

= 3.30.2 - 3/18/2020 =
* Added MemberPress transaction data batch operation
* Fixed payment failures in MemberPress not removing linked tags

= 3.30.1 - 3/16/2020 =
* Added Oxygen page builder integration
* Added support for Formidable Forms Registration addon
* Added WooCommerce Request A Quote integration
* Added Remove Tags option to MemberPress
* Added automatic data conversion for dropdown fields with Ontraport
* Added data-remove-tags option to link click tracking
* Added wpf_woocommerce_billing_email filter
* Added wpf_get_current_user_id() function
* Added wpf_is_user_logged_in() function
* Auto login system no longer sets $current_user global
* Fixed WooCommerce auto-applied coupons not applying when Hide Coupon Field was enabled
* Fixed Duplicate and Delete tool for MailerLite email address changes
* Fixed Formidable Forms entries not getting synced when updated
* Fixed conflict between LearnDash [course_content] shortcode and Elementor for restricted content messages
* Fixed duplicate contact ID lookup API call for new user registrations with existing contact records
* Fixed Paid Memberships Pro membership level settings not saving
* Refactored and optimized MemberPress integration
* Removed WooCommerce v2 backwards compatibility
* Compatibility updates for Advanced Custom Fields Pro v5.8.8
* Stopped loading meta for new user registrations with existing contact records

= 3.30 - 3/9/2020 =
* Added SendFox integration
* Added compatibility with WooCommerce Subscribe All The Things extension
* Added auto-enrollment tags for TutorLMS courses
* Fixed MemberPress membership levels not getting removed when the linked tag is removed
* Tribe Tickets bugfixes and compatibility updates

= 3.29.7 - 3/5/2020 =
* Added support for WooCommerce order status tagging with statuses created by WooCommerce Order Status Manager
* Fixed restricted content message not being output when multiple content areas were on a page
* Fixed New User Benchmark not firing with Groundhogg
* Fixed changed email addresses not syncing to Sendinblue
* Fixed names not syncing with profile updates in BuddyPress

= 3.29.6 - 3/2/2020 =
* Added option to send welcome email to new users imported from ConvertKit
* Added Apply Tags - Trial and Apply Tags - Converted settings to MemberPress
* Added Coupon Used field for sync with MemberPress
* Added Trial Duration field for sync with MemberPress
* Added Default Optin Status option for Groundhogg
* New user welcome emails are now sent after tags and meta data have been loaded
* Expired and Cancelled tags will now be removed when someone joins a Paid Memberships Pro membership level
* Removed admin authentication cookies from background worker
* Stopped converting dates to GMT with Ontraport
* Fixed Tags (Not) visibility bug with Beaver Builder

= 3.29.5 - 2/24/2020 =
* Added optin_status field for syncing with Groundhogg
* Added Defer Until Activation setting to BuddyPress
* Added Defer Until Activation setting to User Meta Pro
* Added wc_memberships_for_teams_team_role field for syncing with WooCommerce Memberships for Teams
* Added bulk edit support to WP Courseware courses and units
* Added wpf_forms_args filter to forms integrations
* New contacts added to Groundhogg will be marked Confirmed by default
* Added "Apply Tags - Enrolled" setting to LearnDash courses
* Fixed WooCommerce auto applied coupons not respecting coupon usage restrictions
* Fixed Recurring Payment Failed tags not being applied with Restrict Content Pro
* Fixed Mautic not listing more than 30 custom fields
* Fixed Mailchimp not loading more than 200 tags

= 3.29.4 - 2/17/2020 =
* Added Last Coupon Used field for syncing with WooCommerce
* Added support for global addons with WooCommerce Product Addons
* Added default fields to MailerLite for initial install
* Leads will now be created in Gist instead of Users if the subscriber doesn't have a user account
* Fixed auto-enrollments not working with more than 20 BuddyBoss groups
* Fixed error with myCRED when the Badges addon was disabled
* Fixed messed up formatting of foreign characters in Gutenberg block
* Fixed conflict between Clean Login and Convert Pro integrations
* Fixed underscores not loading in Infusionsoft tag labels

= 3.29.3 - 2/10/2020 =
* Added support for EDD Custom Prices addon
* Added Required Tags (not) setting to access control meta box
* Added an alert to the status bar of the background worker if API errors were encountered during data export
* Manually changing a WooCommerce subscription status to On Hold will now immediately apply On Hold tags instead of waiting for renewal payment
* Fixed background worker status check getting interrupted by new WooCommerce orders
* Fixed user_activation_key getting reset when importing new users and breaking Better Notifications welcome emails
* Fixed PHP error manually adding a member to a team in WooCommerce Memberships for Teams

= 3.29.2 - 2/4/2020 =
* Added wpf_auto_login_cookie_expiration filter
* Added wpf_salesforce_query_args filter
* Fixed Approved tags getting applied with Event Espresso when registrations are pending
* Fixed tags not applying with Event Espresso

= 3.29.1 - 2/3/2020 =
* Added WP Ultimo integration
* Added notice when linked / auto-enrollment tags are changed on a course or membership
* Added wpf_event_espresso_customer_data filter to Event Espresso
* Added option to Event Espresso to sync attendees in addition to the primary registrant
* Added additional event and venue fields for syncing with FooEvents
* Added additional event and venue fields for syncing with Event Espresso
* Added wp_s2member_auto_eot_time for syncing with s2Member
* Fixed Invalid Data errors when syncing a number to a text field in Zoho
* Fixed "Return After Login" not working with WooCommerce account login
* Maropost bugfixes

= 3.29 - 1/27/2020 =
* Added Klick-Tipp CRM integration
* Logged in users and form submissions will now be identified to the Gist tracking script
* WooCommerce order status tags will now be applied even if the initial payment wasn't processed by WP Fusion
* WooCommerce Subscriptions v3.0 compatibility updates
* Improved webhooks with MailerLite (can now handle multiple subscribers in a single payload)
* Suppressed HTML5 errors in Gutenberg block
* Fixed tags not getting removed from previous variation when a WooCommerce variable subscription was switched
* Groundhogg bugfixes
* Maropost bugfixes
* Sendinblue bugfixes

= 3.28.6 - 1/20/2020 =
* Added linked tags to Ranks with myCred
* Added BuddyPress Account Deactivator integration
* Added Entries Per Page to Screen Options in logs
* Fixed special characters in tag names breaking tags loading with Infusionsoft
* Copper bugfixes

= 3.28.5 - 1/15/2020 =
* Fixed notice with ConvertKit when WP_DEBUG was turned on
* Auto login sessions will now end on the WooCommerce Order Received page

= 3.28.4 - 1/13/2020 =
* Added support for myCred ranks
* Added Event Start Time field for syncing with Event Espresso
* Improved Paid Memberships Pro logging
* Fixed being unable to remove a saved tag on a single AffiliateWP affiliate
* Fixed special characters not getting encoded properly with Contact Form 7 submissions
* Fixed bug in updater and changelog display
* Slowed down batch operations with ConvertKit to get around API throttling
* Added logging for API throttling with ConvertKit
* Added support for dropdown-type fields with Copper
* Copper bugfixes

= 3.28.3 - 1/9/2020 =
* Fixed ActiveCampaign contact ID lookups returning error message when connected to non-English ActiveCampaign accounts

= 3.28.2 - 1/9/2020 =
* Performance improvements with LearnDash auto enrollments
* Improved debugging tools for background worker
* Menu item visibility bugfixes
* Gist compatibility updates for changed API methods

= 3.28.1 - 1/6/2020 =
* Added option for tagging on LearnDash assignment upload
* Added Share Logins Pro integration
* Tags will now be removed from previous status when a membership status is changed in WooCommerce Memberships
* Improved handling for email address changes with Sendinblue
* Give integration bugfixes
* GetResponse bugfixes

= 3.28 - 12/30/2019 =
* Added Zero BS CRM integration
* Added MailEngine CRM integration (thanks @pety-dc and @ebola-dc)
* Added wpf_user_can_access and wpf_divi_can_access filters to Divi integration
* Added option to merge order status into WooCommerce automatic tagging prefix
* Removed extra column in admin list table and moved lock symbol to after the post title
* Ultimate Member roles that are linked with a tag will no longer leave a user with no role if the tag is removed
* Added additional WooCommerce Memberships logging
* Menu item visibility bug fixes

= 3.27.5 - 12/23/2019 =
* Added option to restrict access to individual menu items
* Added FacetWP integration
* Added support for AffiliateWP Signup Referrals addon
* Added export tool for Event Espresso registrations
* Fixed BuddyPress groups not running auto-enrollments when a webhook is received

= 3.27.4 - 12/16/2019 =
* Improved support for custom fields with FooEvents
* Added wpf_aweber_key and wpf_aweber_secret filters
* Logged in users and guest form submissions will now be identified to the Autopilot tracking script
* Event Espresso integration will now sync the event date from the ticket, not the event
* Fixed Elementor Popups triggering on every page for admins
* Autopilot bugfixes

= 3.27.3 - 12/11/2019 =
* Added support for WP Event Manager - Sell Tickets addon
* Added support for Popup Maker subscription forms
* Improvements to applying tags with Kartra using the new Kartra API endpoints
* Fixed billing address fields not syncing with PayPal checkout in s2Member
* Fixed Restrict Content Pro linked tags being removed when a user cancelled their membership before the end of the payment period
* Fixed missing email addresses causing BirdSend API calls to fail
* Fixed issues with non well-formed HTML content causing errors in inner Gutenberg blocks
* Fixed auto un-enrollment from LearnDash courses not working when course access was stored in user meta
* Fixed Advanced Custom Fields integration overriding date formats from WooCommerce

= 3.27.2 - 12/3/2019 =
* Fixed load contact method with Sendinblue
* Gutenberg block will no longer output HTML if there's nothing to display

= 3.27.1 - 12/2/2019 =
* Added GravityView integration
* Added batch tool for Restrict Content Pro members
* Added additional built in Gist fields for syncing
* Added option to tag customers based on WooCommerce order status
* Added support for global webhooks with Sendinblue
* Restrict Content Pro rcp_status field will now be synced when a membership expires
* WooCommerce Smart Coupons bugfixes
* Fixed ACF date fields not converting to CRM date formats properly
* Fixed bug in Import Tool with Sendinblue
* Fixed BirdSend only loading 15 available tags
* Fixed GMT offset calculation with Ontraport date fields

= 3.27 - 11/25/2019 =
* Added BirdSend CRM integration
* Added WP Event Manager integration
* Added support for triggering LifterLMS engagements when a tag is applied
* Fixed WPF settings not saving on CPT-UI post type edit screen
* Fixed Woo Memberships for Teams team member tags not being applied with variable product purchases
* Updated Gist API URL
* Fixed import tool not loading more than 50 contacts with Sendinblue
* wpf_tags_applied and wpf_tags_removed will now run when tags are loaded from the CRM

= 3.26.5 - 11/18/2019 =
* Added Groundhogg company fields for sync
* Added Event Name, Event Venue, and Venue Address fields for sync to Event Espresso
* Improved site tracking with HubSpot for guests
* eLearnCommerce login tokens can now be synced on registration
* Fixed refreshing Zoho access token with Australian data server
* Improved support for Country field with Groundhogg
* Style compatibility updates for WP 5.3

= 3.26.4 - 11/11/2019 =
* Added Toolset Types integration
* Added event_date field to Event Espresso integration
* Added signup_type field to NationBuilder
* Updated LifterLMS auto enrollments to better deal with simultaneous webhooks
* WP E-Signature bugfixes
* Access Key is no longer hidden when connected to MailerLite
* Improved Mautic site tracking
* Improved handling of merged contacts with Mautic
* Improved compatibility with Gravity Forms PayPal Standard addon
* Give integration bugfixes

= 3.26.3 - 11/4/2019 =
* Added Fluent Forms integration
* Added AffiliateWP affiliates export option to batch tools
* Added Australia data server integration to Zoho integration
* Apply Tags on View tags won't be applied for LearnDash lessons that aren't available yet
* Mautic tracking cookie will now be set after a form submission
* Give integration will now only apply tags when a payment status is Complete
* Fixed bug with Intercom API v1.4
* Fixed bug with The Events Calendar Community Tickets addon

= 3.26.2 - 10/28/2019 =
* Added "capabilties" format for syncing capability fields
* Added India data server support to Zoho integration
* Improved handling of multi-select and dropdown field types in PeepSo
* Fixed return after login for redirects on hidden WooCommerce products

= 3.26.1 - 10/21/2019 =
* Added Memberoni integration
* Improved integration with PilotPress login process
* Woo Subscriptions actions will no longer run on staging sites
* Fixed conflict with ThriveCart auto login and UserPro

= 3.26 - 10/14/2019 =
* Added Klaviyo integration
* Fixed PeepSo multi-checkbox fields syncing values instead of labels
* Fixed Elementor Pro bug when Elementor content was stored serialized

= 3.25.17 - 10/9/2019 =
* Added support for Ranks with Gamipress
* Enabled Import Users tab for Intercom
* Added "role" and "send_notification" parameters for ThriveCart auto login
* Performance improvements and bugfixes for background worker

= 3.25.16 - 10/7/2019 =
* Added custom fields support to Give
* Added option to hide restricted wpForo forums
* Added "ucwords" formatting option to user_meta shortcode
* Ultimate Member roles will now be removed when a linked tag is removed
* Fixed special characters getting escaped on admin profile updates

= 3.25.15 - 9/30/2019 =
* Added WP E-Signature integration
* Added UserInsights integration
* Added option to hide WPF meta boxes from non admins
* Added support for syncing multi-input Name fields for WPForms
* Added Filter Queries setting to Elementor Pro Posts and Portfolio widgets
* Updated ActiveCampaign site tracking scripts
* Fixed NationBuilder not loading more than 100 available tags
* Fixed GiveWP recurring payments treating the donor as a guest
* Fixed PeepSo first / last name fields not syncing on registration forms
* Fixed fatal error when initializing GetResponse connection
* All site tracking scripts will now recognize auto login sessions

= 3.25.14 - 9/23/2019 =
* Added WPPizza integration
* Existing Elementor forms will now update available CRM fields automatically
* Added new filters and better session termination to auto login system
* Payment Failed tags will now be removed after a successful payment on a WooCommerce subscription
* Disabled comments during auto login sessions
* Fixed bug with WooCommerce Points and Rewards discounts not applying
* Fixes for HubSpot accounts with over 250 lists
* Sendinblue bugfixes

= 3.25.13 - 9/18/2019 =
* Sendinblue bugfixes
* Bugfixes for syncing LearnDash quiz answers

= 3.25.12 - 9/16/2019 =
* Added support for Woo Checkout Field Editor Pro
* Added CartFlows upsell tagging
* Added support for CartFlows custom fields
* Added ability to sync LearnDash quiz answers to custom fields
* Fixed Gravity Forms entries export issue with Create Tag(s) From Value fields
* Fixed Mailchimp contact ID getting disconnected after email address change
* Fixed BuddyPress fields not being detected on custom profile types
* Fixed WooCommerce automatic coupons not being applied properly when a minimum cart total was set
* Fixed NationBuilder Primary address fields not syncing
* Fixed updating email addresses in WooCommerce / My Account creating duplicate subscribers in Drip

= 3.25.11 - 9/9/2019 =
* Added Site Lockout feature
* Added Ahoy messaging integration
* Added prefix option for WooCommerce automatic tagging
* Added additional AffiliateWP fields
* Gravity Forms batch processor can now process all unprocessed entries
* Increased limit on LifterLMS Memberships Statuses batch operation to 5000
* Salon Booking tweaks
* Fixed restricting Woo coupon usage by tag
* Fixed WooCommerce auto-discounts not being applied when cart quantities updated
* Fixed loading CRM data into Ultimate Member multi-checkbox fields
* Fixed Mailchimp compatibility with other Mailchimp plugins
* Copper bugfixes

= 3.25.10 - 9/4/2019 =
* Fixed home page not respecting access restrictions in 3.25.8

= 3.25.9 - 9/4/2019 =
* Changed order of apply and remove tags in Woo Subscriptions
* Fixed Hold and Pending Cancel tags not being removed in Woo Subscriptions after a successful payment
* Improved MemberPress expired tagging
* FooEvents compatibility updates
* Fixed tags not being removed with Ontraport

= 3.25.8 - 9/3/2019 =
* Added Salon Booking integration
* Added Custom Post Type UI integration
* Added GDPR Consent and Agreed to Terms fields for syncing with Groundhogg
* Enabled welcome email in MailPoet when a contact is subscribed to a list
* WooCommerce will now use the user email address as the primary email for checkouts by registered users
* Made background worker less susceptible to being blocked
* Improved ActiveCampaign eCom customer lookup
* Fixed content protection on blog index page
* Fixed students getting un-enrolled from LearnDash courses if they were enrolled at the group level and didn't have a course linked tag

= 3.25.7 - 8/26/2019 =
* Added Uncanny LearnDash Groups integration
* Added event_name and venue_name to Event Tickets integration
* Event Tickets bugfixes for RSVP attendees
* Fixed "Create tags from value" option for profile updates
* Fixed initial connection to Groundhogg on Groundhogg < 2.0
* Fixed typo in NationBuilder fields dropdown
* WooCommerce deposits compatibility updates

= 3.25.6 - 8/19/2019 =
* Fix for error trying to get coupons from WooCommerce order on versions lower than 3.7

= 3.25.5 - 8/19/2019 =
* Added ability to create new user meta fields from the Contact Fields list
* Added support for Event Tickets Plus custom fields with WooCommerce
* Added ability to sync event check-ins from Event Tickets Plus to a custom field
* Added "Create tag from value" option to WPForms integration
* Added support for sending full country name in WooCommerce
* Added option to restrict WooCommerce coupon usage by tag
* Improved "Source" column in WPF logs
* Fixed event details not syncing on RSVP with Event Tickets
* Fix for Uncanny LearnDash Groups bulk-enrollment adding contacts with multiple names
* Fixed email address changes with Infusionsoft causing opt-outs
* Reverted asynchronous checkouts to use background queue instead of single request
* Performance improvements on sites with Memberium active

= 3.25.4 - 8/12/2019 =
* Added auto-login by email address for MailerLite
* Added Portuguese translation (thanks @João Alexandre)
* MailerLite will now re-subscribe subscribers when they submit a form
* Improved OAuth access token refresh process with Salesforce
* Access control meta box now requires the manage_options capability
* Fixed variable tags not getting removed during Woo subscription hold if no tags were configured for the main product
* Variable tags will now be removed when a Woo subscription is switched and Remove Tags is enabled
* Fix for WooCommerce Orders export process crashing on deleted products

= 3.25.3 - 8/6/2019 =
* Fixed fatal error in BuddyPress integration when Profile Types module was disabled
* Fixed WooCommerce orders exporter crashing when trying to access a deleted product
* Fixed wpf_woocommerce_payment_complete action not firing on renewal orders

= 3.25.2 - 8/5/2019 =
* Added support for tag linking with BuddyBoss Profile Types
* Added support for restricting access to a single bbPress discussion
* Restricted topics in BuddyBoss / bbPress will now be hidden from the Activity Feed if Filter Queries is on
* Performance improvements when editing WooCommerce Variations
* Performance improvements with Drip and WooCommerce guest checkouts
* Added additional monitoring tools for background process worker
* Cartflows bugfixes for Enhanced Ecommerce addon
* Fixed WooCommerce variable subscription tags not being removed on Hold status
* Fixed bug with borders being output on restricted Elementor widgets
* Fixed bug when sending a store credit with WooCommerce Smart Discounts

= 3.25.1 - 7/29/2019 =
* Added CartFlows integration
* Groundhogg 2.0 compatibility
* Drip site tracking will now auto-identify logged in users
* Added WooCommerce Order Notes field for syncing
* Fixed "Affiliate Approved" tags not being added when creating an AffiliateWP affiliate via the admin

= 3.25 - 7/22/2019 =
* Added MailPoet integration
* Added EDD Software Licensing integration
* Added TranslatePress integration
* Added support for MemberPress Corporate Accounts addon
* Added support for BuddyPress fields to the user_meta shortcode
* Additional tweaks to Austrailian state abbreviations with Ontraport
* Groundhogg tags now update without manual sync
* Fixed FooEvents tags getting removed during Woo Subscriptions renewal

= 3.24.17 - 7/15/2019 =
* Added Tutor LMS integration
* Added option to tag AffiliateWP affiliates on first referral
* WooCommerce integration will no longer apply tags / update meta during a Subscriptions renewal
* Groundhogg will now load tags and meta immediately instead of requiring sync
* Fixed incorrect expiration dates with Paid Memberships Pro
* Improved handling for State fields with Ontraport
* Fixed MemberPress coupon settings not saving
* Added LifterLMS membership start date as a field for syncing
* Dynamic name / SKU tags will now be removed when an order is refunded

= 3.24.16 - 7/8/2019 =
* Added GTranslate integration
* Added Customerly webhooks
* Added social media fields to Kartra
* Added option to remove tags when a page is viewed
* Added automatic SKU tagging in WooCommerce for supported CRMs
* Fixed notifications going out when using the built in import tool
* Restrict Content Pro beta 3.1 compatibility
* Better handling for missing last names in Salesforce
* When a PMPro membership is cancelled / expired the membership level name will be erased in the CRM

= 3.24.15 - 7/1/2019 =
* Added option to completely hide a taxonomy term based on tags
* Added support for built in Ultimate Member fields
* Added option to automatically tag customers based on WooCommerce product names
* Capsule bugfixes
* Bugfixes for Preview with Tag feature
* Fixed syncing changed email addresses with BuddyPress

= 3.24.14 - 6/24/2019 =
* Added new default profile fields for Drip
* Added support for catching Salesforce outbound messages with multiple contact IDs
* Added wpf_salesforce_auth_url filter for Salesforce
* Added date_joined field for Kartra
* Added WooCommerce Subscriptions subscription ID field for syncing
* Added multiselect support for HubSpot
* Added support for File Upload field with Formidable Forms
* Fixed Infusionsoft API errors with addWithDupCheck method
* Bugfixes for Restrict Content Pro 3.0
* Formidable Forms 4.0 compatibility updates
* Slowed down HubSpot batch operations to get around API limits

= 3.24.13 - 6/17/2019 =
* Added option to sync eLearnCommerce auto login token to a custom field
* Mautic performance improvements
* Linked tags from the previous level will now be removed when an RCP membership is manually changed
* Fixed Mautic webhooks failing when the contact ID had changed due to a merge
* Intercom bugfixes
* Groundhogg bugfixes

= 3.24.12 - 6/14/2019 =
* Added option to enable HubSpot site tracking scripts
* Added order_id field for syncing with WooCommerce
* Improved auto enrollment for LearnDash courses
* Reduced API calls required during EDD checkout
* Fixed ConvertKit contact ID lookup failing
* Fixed tags from WooCommerce product attributes getting applied when the attribute wasn't selected

= 3.24.11 - 6/10/2019 =
* Added better handling for ACF relationship fields
* Added password update syncing for MemberPress
* Added option to apply tags when a discount is used in Easy Digital Downloads
* Added option to restrict usage of discounts by tags in Easy Digital Downloads
* Added Last Lesson Completed and Last Course Completed fields for syncing with LifterLMS
* Added Last Lesson Completed and Last Course Completed fields for syncing with LearnDash
* Added unsubscribe notifications for ConvertKit
* Added "wpf_salesforce_auth_url" filter for overriding Salesforce authorization URL
* Restrict Content Pro linked tags will now be removed when a member upgrades
* Improvements to "Return after login" feature
* Fixed creating a contact in Zoho without a last name
* Fixed Beaver Builder elements being hidden from admins
* Fixed Event Tickets Plus tags not applying during WooCommerce checkout
* Fixed Filter Queries "Advanced" mode not working on multiple queries
* Fixed slashes getting added to tags with apostrophes in Mautic
* Tweaks to Filter Queries (Advanced) option
* Prevented linked tags from being re-applied when a Woo membership unenrollment is triggered

= 3.24.10 - 6/3/2019 =
* Added details about configured tags to protected content in post list table
* Added ThriveCart auto login / registration
* Added Pending Payment tags for Event Espresso
* Fixed settings getting reset when enabling ActiveCampaign site tracking

= 3.24.9 - 5/28/2019 =
* Added Email Changed event for Drip
* Fix for tags sometimes not appearing in settings dropdowns

= 3.24.8 - 5/27/2019 =
* Added dynamic tagging based on field values (for supported CRMs)
* Added Is X? fields for NationBuilder
* Added GetResponse support
* Enabled Sequential Upgrade for WishList Member
* Preview With Tag now bypasses Exclude Admins setting
* Fixed WooCommerce checkout not applying tags after an auto login session
* Fixed slashes in image URLs with Gravity Forms multi-file upload fields

= 3.24.7 - 5/20/2019 =
* Added WooCommerce Fields Factory integration
* Added support for syncing WooCommerce attribute selections to custom fields
* Added option to apply tags when an AffiliateWP affiliate is approved
* Added option to disable "Preview With Tag" in admin bar
* Added support for date fields in User Meta Pro
* Fixed bug with Login Meta Sync
* Fixed MailChimp looking up contacts from other lists
* Fixed redirect causing multiple API calls with contact ID lookup in Mautic
* Fixed empty date type fields sending 1/1/1970 dates
* Added WooCommerce order date meta field for syncing

= 3.24.6 - 5/13/2019 =
* Added active lists to list dropdowns with HubSpot
* Removed admin bar JS link rewriting
* Fix for sending 0 in Gravity Forms submissions

= 3.24.5 - 5/9/2019 =
* Fixed tags not applying correctly with Async Checkout when a user registered a new account
* Fixed WooCommerce Subscriptions variation tags not applying
* Toolset fixes for profile updates
* Fix for 3.24.4 turning off Filter Queries setting

= 3.24.4 - 5/6/2019 =
* Added WP Affiliate Manager support
* Added customer tagging for AffiliateWP
* Added Organisation field for syncing to Capsule
* Added "Advanced" mode for Filter Queries setting
* Added support for single checkboxes with Formidable Forms
* Added ability to modify field data formats via the Contact Fields list
* Added IP address when adding new contacts with Mautic
* Added "Add Only" option for Elementor forms
* Added option to restrict visibility of EDD price options
* Paid Memberships Pro now sends meta data before applying tags
* Deleting a WooCommerce Subscription will no longer apply Cancelled tags
* Fixed auto-enrollments into MemberPress membership levels via webhook not returning passwords
* Fixed "Expired" tags not applying with MemberPress
* Fixed date formatting with HubSpot
* Fixed syncing date fields with Capsule
* Compatibility updates for custom field formatting with Mailerlite

= 3.24.3 - 4/29/2019 =
* Added option to return people to originally requested content after login
* Added Contact ID merge field to Gravity Forms
* Improved Preview With Tag functionality
* Auto login with Mailchimp now works with email address
* WooCommerce Transaction Failed tags will now be removed after a successful checkout
* Limit logging table to 10,000 rows
* Copper bugfixes
* Fix for error when using GForms User Registration during an auto login session

= 3.24.2 - 4/22/2019 =
* Added Caldera Forms integration
* Added additional status tags for Restrict Content Pro
* Changed Woo taxonomy tagging to just use the Category taxonomy
* Modified async checkouts to use a remote post instead of AJAX
* WPForms bugfixes
* Platform.ly bugfixes
* Consolidated forms functionality into new WPF_Forms_Helper class

= 3.24.1 - 4/16/2019 =
* Fix for Paid Memberships Pro checkout error

= 3.24 - 4/15/2019 =
* Added Sendlane CRM integration
* Added WooCommerce category tagging
* Added AgileCRM site tracking scripts
* Added support for BuddyPress taxonomy multiselect fields
* Fixed expiration tags in Paid Memberships Pro
* Fixed MemberPress auto-enrollments setting expiration date in the past
* Fixes for multiselects in BuddyPress
* Fixes for XProfile fields on secondary field groups

= 3.23.7 - 4/8/2019 =
* Added account deactivation tag trigger for Ultimate Member
* Added WooCommerce Wholesale Lead Capture support
* Toolset forms compatibility updates
* Fixed logic error with "Required Tags (all)" setting
* Fixed Preview With Tag functionality in Beaver Builder
* Updated AWeber subscriber ID lookup to only use selected list

= 3.23.6 - 4/1/2019 =
* Added Teams for WooCommerce Memberships integration
* Added unit completion tagging for WP Courseware
* Added Organization Name field for ActiveCampaign
* LearnPress compatibility updates
* Better AWeber exception handling
* AccessAlly bug fixes
* Bugfixes for PeepSo and auto login sessions
* Fix for changing email addresses with Drip
* Fix for AffiliateWP affiliate data not being synced when Auto Register Affiliates was enabled

= 3.23.5 - 3/25/2019 =
* Added LifterLMS quiz tagging (thanks @thomasplevy)
* Added ability to restrict usage of EDD discount codes (thanks @pjeby)
* Added merge settings option to bulk edit
* Added setting to remove "Additional Fields" section from settings
* Added "hide" option to Convert Pro targeting rules
* Expired / Cancelled / etc tags will now be removed when an EDD subscription is re-activated
* Popup Maker compatibility updates
* AccessAlly bug fixes
* Fix for failed WooCommerce order blocking tagging on subsequent successful re-try
* Fix for Required Tags (all) option greyed out
* Paid Memberships Pro bugfixes

= 3.23.4 - 3/18/2019 =
* Added Convert Pro CTA targeting integation
* Added FooEvents integration
* Added date-format parameter to user_meta shortcode
* Added "Required tags (all)" option to post restriction meta box
* Added option for login meta sync
* Added option for tagging when WooCommerce orders fail on initial payment
* Improved pagination in WPF logs
* Mailerlite bugfixes
* Improved HubSpot error logging
* MemberPress expired tagging bugfixes
* Fix for restricting BuddyPress pages

= 3.23.3 - 3/1/2019 =
* Fixed bug in MailerLite integration

= 3.23.2 - 3/1/2019 =
* Added Event Espresso integration
* Restrict Content Pro v3.0 compatibility fixes
* Added additional status triggers for Mailerlite webhooks
* Fixes for wpf_user_can_access filter
* ConvertKit fixes for unconfirmed subscribers

= 3.23.1 - 2/25/2019 =
* CoursePress integration
* Added incoming webhook test tool
* Added WooCommerce Subscriptions Meta batch operation
* Improved Ontraport site tracking script integration
* MemberPress will now remove the payment fail tag when a payment succeeds
* Bugfixes for CartFlows upsells with WooCommerce
* Fix for syncing checkbox fields in Elementor forms
* Fix for MailerLite accounts syncing more than 100 groups
* Fix for syncing profile updates via Gravity Forms
* Fixes for Free Trial Over tags in WooCommerce Subscriptions

= 3.23 - 2/18/2019 =
* Added Mailjet CRM integration
* Added payment failed tagging for MemberPress
* Javascript bugfix for tags with apostrophes in them
* Changes to WooCommerce variations data storage
* Added option to only allow auto-login after form submission
* Fix for email addresses with + sign in MailChimp
* Fix for changed checkout field names in Paid Memberships Pro
* Fix for contact ID lookup with HubSpot
* Fix for background worker when PHP's memory_limit is set to -1
* Added ability to restrict WooCommerce Shop page
* bbPress template compatibility fixes

= 3.22.3 - 2/12/2019 =
* Added tags for Expired status in MemberPress
* Added admin users column showing user tags
* Added fields for syncing Woo Subscriptions subscription name and next payment date
* Option to hide Woo coupon field on Cart / Checkout (used with auto-applying coupons)
* Fix for restricted WooCommerce products showing "password protected" message

= 3.22.2 - 2/5/2019 =
* Elementor Popups integration
* Added ability to auto-apply discounts via tag with WooCommerce
* Added option to embed Mautic site tracking scripts
* Added Mautic mtc_id cookie tracking for known contacts
* Additional Woo Memberships statuses for tagging
* Comments are now properly hidden when a post is restricted and no redirects are specified
* Set 1 second sleep time for Drip batch processes to avoid API timeouts
* Platform.ly bugfixes
* Platform.ly webhooks added
* Fixes for custom objects with Ontraport
* Fixes for WooCommerce Deposits not tagging properly

= 3.22.1 - 1/31/2019 =
* Groundhogg bugfixes
* Drift tagging bugfixes
* WooCommerce 2.6 compatibility fixes
* Woo Subscriptions tagging bugfixes

= 3.22 - 1/28/2019 =
* NationBuilder CRM integration
* Groundhogg CRM integration
* Added batch processing tool for WooCommerce Memerships
* Added pagination to AccessAlly settings page
* Added additional AffiliateWP registration fields for sync
* Fix for Sendinblue not creating contacts if custom attributes weren't present
* Fix for being unable to remove tags from Woo variations
* Fix for Woo variations not saving correctly with Woo Memberships active
* Fix for imports larger than 50 with Capsule

= 3.21.2 - 1/21/2019 =
* Added Clean Login support
* Added Private Messages integration
* Added custom fields support for Kartra
* Added AffiliateWP referrer ID field for syncing
* Added Toggle field support for Formidable Forms
* Added PeepSo VIP Icons support
* Added Gist webhooks support
* Moved Formidable Forms settings to "Actions" to support conditions
* Fix for custom fields not syncing with MemberMouse registration
* Fix for missing Ninja Forms settings fields
* Fix for syncing multiselects / picklists with Zoho
* Fix for error when processing Woo Subscriptions payment status hold
* Fix for AJAX applying tags by tag ID
* Fix for wpf_update_tags shortcode in auto-login sessions
* Fix for error creating contacts in Intercom without any custom fields
* Additional Capsule fields / Capsule field syncing bugfixes
* Better internationalization support
* Added PHP version notice for sites running less than 5.6

= 3.21.1 = 1/14/2019 =
* Elementor Forms integration
* Advanced Ads support
* WooCommerce Addons v3.0 support
* Additional tagging options for WooCommerce Memberships
* Fix for variation tags sometimes being lost when saving a Woo product
* Support for updating Capsule email/phone/address fields without a type specifier
* Added tagging for when a LearnDash essay is submitted
* Allow for using tag labels in link click tracking

= 3.21 - 1/5/2019 =
* Copper CRM integration
* Fixes for syncing PeepSo account fields
* Fixes for LearnDash quiz results tagging with Essay type questions
* Fix for incomplete address error with MailChimp
* Support for syncing with unsubscribed subscribers in ConvertKit
* Fixes for user IDs in ConvertFox (Gist)
* Bugfix for logged-out behavior in Elementor
* Added "Process WP Fusion actions again" option to WooCommerce Order Actions
* PHP 5.4 fixes

= 3.20.4 - 12/22/2018 =
* Fixed "return value in write context" error in PHP 5.5

= 3.20.3 - 12/22/2018 =
* Added logged-out behavior to Elementor
* Added support for syncing roles when a user has multiple roles
* Added Pull User Meta batch operation
* Added support for picklist fields in Zoho
* Fix for syncing MemberPress membership level name during batch process
* Additional logging for WC Subscriptions status changes
* Added import by Topic for Salesforce
* Admin settings update to support Webhooks

= 3.20.2 - 12/14/2018 =
* Fix for JS error with Gutenberg block

= 3.20.1 - 12/14/2018 =
* Added Gutenberg content restriction block
* Better first name / last name handling for ConvertFox
* Fix for Event Tickets settings not saving

= 3.20 - 12/8/2018 =
* Autopilot CRM integration
* Customerly CRM integration
* Added Ninja Forms integration
* Added option for per-post restricted content messages
* Added user_registered date field for syncing
* Added option to sync MemberPress membership level name at checkout
* Added handling for changed contact IDs with Infusionsoft
* Userengage bugfixes
* Fix for BuddyPress multi-checkbox fields not syncing
* Fix for PeepSo group members not getting fully removed from groups
* Fix for MemberMouse password resets not syncing
* Reverted to earlier method for getting Woo checkout fields to prevent admin errors in WPF settings
* Fixed bug where bulk-editing pages would remove WPF access rules

= 3.19 - 11/29/2018 =
* Drift CRM integration
* wpForo integration
* "Give" plugin integration
* Bugfixes for MemberPress coupons
* Better support for Gravity Forms User Registration
* UserEngage bugfixes
* Fixed compatibility bugs with other plugins using Zoho APIs
* Added wpf_batch_sleep_time filter
* Better user meta handling on auto-login sessions

= 3.18.7 - 11/21/2018 =
* Popup Maker integration
* GamiPress linked tag bugfixes
* Added import tool for Mautic
* Added support for updating email addresses in Kartra

= 3.18.6 - 11/15/2018 =
* WPForms integration
* UserEngage bugfixes
* Ability to set WooCommerce product tags to apply at the taxonomy term level
* Fix for incorrect membership start date with Paid Memberships Pro

= 3.18.5 - 11/12/2018 =
* Fixed bug with WooCommerce that caused WPF settings page not to load

= 3.18.4 - 11/10/2018 =
* WPComplete integration
* Added async method for batch webhook operations
* Fix for restricted WooCommerce variations not showing in admin when Filter Queries is enabled
* Bugfixes for detecting WooCommerce custom checkout fields
* Added payment conditions for Stripe and PayPal for Gravity Forms
* Now allows updating PeepSo role by changing field value in CRM

= 3.18.3 - 10/27/2018 =
* Added batch processing tool for Gravity Forms entries
* Fixed outbound message endpoint creating error messages in Salesforce
* Better support for custom checkout fields in WooCommerce
* LifterLMS course/membership auto-enrollment tweaks
* Added Payment Failed option to Woo Subscriptions

= 3.18.2 - 10/22/18 =
* Added support for Salesforce topics
* Added tagging for MemberPress coupons
* Added option to sync user tags on login
* Added support for multi-checkboxes to Gravity Forms integration
* Capsule bugfixes

= 3.18.1 - 10/14/2018 =
* Added Weglot integration
* Restrict Content Pro bugfixes
* Kartra bugfixes for WooCommerce guest checkouts
* Divi integration bugfixes
* More flexible Staging mode

= 3.18 - 10/4/2018 =
* Added Platform.ly support
* Added logged in / logged out shortcodes
* Added option to choose contact layout for new contacts with Zoho
* Fix for AgileCRM campaign webhooks
* Fixes for checkboxes with Profile Builder
* WooCommerce Addons bugfixes
* Added custom fields support for Intercom

= 3.17.2 - 9/22/2018 =
* Added Divi page builder support
* Added update_tags endpoint for webhooks
* Fix for "restrict access" checkbox not unlocking inputs correctly
* Fix for import button not working in admin
* Cleaned up WooCommerce settings storage

= 3.17.1 - 9/17/2018 =
* Added support for WooCommerce Addons
* Improved leadsource tracking
* Added webhooks support for SalesForce
* Bugfixes for ConvertKit with email addresses containing "+" symbol
* Support for syncing passwords generated by EDD Auto Register
* Fix for MailChimp syncing tags limited to 10 tags
* Additional sanitizing of input data

= 3.17 - 9/4/2018 =
* HubSpot integration
* SendinBlue bugfixes
* Zoho authentication bugfixes
* Profile Builder bugfixes
* Added support for Paid Memberships Pro Approvals
* Added option for applying a tag when a contact record is updated
* Support for Gravity Forms applying local tags during auto-login session

= 3.16 - 8/27/2018 =
* Added MailChimp integration
* Added SendinBlue CRM integration
* Easy Digital Downloads 3.0 support
* Profile Builder Pro bugfixes

= 3.15.3 - 8/23/2018 =
* Added Profile Builder Pro integration
* AccessAlly integration
* WPML integration
* Added "wpf_crm_object_type" filter for Salesforce / Zoho / Ontraport
* Fix for date fields with Salesforce
* Improvements to logging display for API errors
* Added Elementor controls to sections and columns
* Support for multi-checkbox fields with Formidable Forms

= 3.15.2 - 8/12/2018 =
* Fix for applying tags via Gravity Form submissions with ConvertKit
* Fixed authentication error caused by resyncing tags with Salesforce
* Added Job Alerts support for WP Job Manager
* Auto-login session will now end on WooCommerce cart or checkout

= 3.15.1 - 8/3/2018 =
* WooCommerce memberships bugfixes
* Fixed PeepSo groups table limit of 10 groups
* Option to sync expiry date for WooCommerce Memberships
* Beaver Builder fix for visibility issues
* WooCommerce Checkout Field Editor Integration
* Added "remove tags" checkbox for EDD recurring price variations
* Maropost CRM integration

= 3.15 - 7/23/2018 =
* Tubular CRM integration
* Flexie CRM integration
* Added tag links for PeepSo groups
* Elementor integration
* WishList Member bugfixes

= 3.14.2 - 7/15/2018 =
* Added WPLMS support
* Improved syncing of multi-checkboxes with ActiveCampaign
* Added support for Paid Memberships Pro Registration Fields Helper add-on

= 3.14.1 - 7/3/2018 =
* Auto-login tweaks for Gravity Forms
* Added option to apply tags on LearnDash quiz fail
* LearnDash bugfixes
* Improvements to AgileCRM imports by tag
* Kartra API updates
* Allowed loading PMPro membership start date and end date from CRM
* MemberMouse syncing updates from admin edit member profile

= 3.14 - 6/23/2018 =
* UserEngage CRM integration
* Fix for auto-login links with AgileCRM
* Added refund tags for price IDs in Easy Digital Downloads
* Added leadsource tracking support for Gravity Forms form submissions
* Added "not" option for Beaver Builder content visibility
* Added access controls to bbPress topics

= 3.13.2 - 6/17/2018 =
* Added support for tagging on subscription status changes for EDD product variations
* Added support for syncing WooCommerce Smart Coupons coupon codes
* Fixed Salesflare address fields not syncing
* Improvements on handling for changed email addresses in MailerLite
* Fix for LifterLMS access plan tags not displaying correctly
* Fix for foreign characters in state names with Mautic

= 3.13.1 - 6/10/2018 =
* Gravity Forms bugfix

= 3.13 - 6/10/2018 =
* Salesflare CRM integration
* Corrected Kartra App ID
* Added option to show excerpts of restricted content to search engines
* Fix for refund tags not being applied in WooCommerce for guest checkouts
* Fix for issues with linked tags not triggering enrollments while running batch processes
* Ability to pause a MemberMouse membership by removing a linked tag
* Bugfixes for empty tags showing up in select
* Better handling for email address changes with MailerLite
* Salesforce bugfixes

= 3.12.9 - 6/2/2018 =
* Added "apply tags" functionality for Restrict Content Pro
* Added tag link for Gamipress achievements
* Added points syncing for Gamipress
* Added support for WooCommerce Smart Coupons
* Fix for "refund" tags getting applied when a WooCommerce order is set to Cancelled
* Fix for LifterLMS "Tag Link" adding a blank tag
* Removed ability to add tags from within WP for Ontraport
* Gravity Forms bugfix for creating new contacts from form submissions while users are logged in
* Support for Tribe Tickets v4.7.2

= 3.12.8 - 5/27/2018 =
* Added GDPR "Agree to terms" tagging for WooCommerce
* BuddyPress bugfixes
* Added ability to apply tags when a coupon is used in Paid Memberships Pro
* Ultimate Member 2.0 fix for tags not being applied at registration
* Bugfix for tags sometimes not saving correctly on widget controls

= 3.12.7 - 5/19/2018 =
* Beaver Builder integration
* Ultimate Member 2.0 bugfixes
* Added delay to Kartra contact creation to deal with slow API performance
* Fix for Kartra applying tags to non-registered users
* Support creating tags from within WP Fusion for Ontraport
* Added delay in WooCommerce Subscriptions renewal processing so tags aren't removed and reapplied during renewals
* Changed template_redirect priority to 15 so it runs after Force Login plugin

= 3.12.6 - 5/16/2018 =
* Bugfix for errors showing when auto login session starts

= 3.12.5 - 5/15/2018 =
* Added support for WooCommerce Deposits
* Added event location syncing for Tribe Tickets Plus
* Added BadgeOS points syncing
* WP Courseware settings page fix for version 4.3.2
* Added option to only log errors (instead of all activity)
* Bugfix for WooCommerce checkout not working properly during an auto-login session

= 3.12.4 - 5/6/2018 =
* Added event date syncing for Tribe Tickets Plus events with WooCommerce
* Fix for Zoho customers with EU accounts
* Support for syncing passwords automatically generated by LearnDash
* Restrict Content Pro bugfixes
* UM 2.0 bugfixes
* Allowed for auto-login using Drip's native ?__s= tracking link query var
* Fix for syncing to date type custom fields in Ontraport

= 3.12.3 - 4/28/2018 =
* Bugfix for "undefined constant" message on admin dashboard

= 3.12.2 - 4/28/2018 =
* Better support for query filtering for restricted posts
* Fixed a bug that caused tags not to be removed properly in Ontraport
* Fixed a bug that caused tags not to apply properly on LifterLMS membership registration
* Fixed a bug with applying tags when achievements are earned in Gamipress
* Fixed a bug with syncing password fields on ProfilePress registration forms
* Additional error handling for import functions

= 3.12.1 - 4/12/2018 =
* ProfilePress integration
* Added option to apply tags when a user is deleted
* Added setting for widgets to *hide* a widget if a user has a tag
* Added option to apply tags when a LifterLMS access plan is purchased
* More robust API error handling and reporting
* Fixed a bug in MailerLite where contact IDs wouldn't be returned for new users

= 3.12 - 3/28/2018 =
* Added Zoho CRM integration
* Added Kartra CRM integration
* Added ConvertFox CRM integration
* Added WP Courseware integration
* Changed WooCommerce order locking to use transients instead of post meta values
* Added membership role syncing to PeepSo integration
* Added User ID as an available field for sync

= 3.11.1 - 3/21/2018 =
* Added GamiPress integration
* Added PeepSo integration
* Added option to just return generated passwords on import, without requiring ongoing password sync
* "Push user meta" batch operation now pushes Paid Memberships Pro meta data correctly
* Fixed bug where ampersands would fail to send in Infusionsoft contact updates
* Cleaned up scripts and styles in admin settings pages

= 3.11 - 3/15/2018 =
* Capsule CRM integration
* Added LearnPress LMS integration
* Added batch-resync tool for LifterLMS memberships
* Tags linked to LearnDash courses will now be applied / removed when a user is manually added to / removed from a course
* Bugfixes for export batch operation
* Added "Pending Cancellation" tags for WooCommerce Subscriptions
* Improved handling for displaying user meta when using auto-login links
* Fix for AWeber API configuration errors breaking setup tab
* Improved AgileCRM handling for custom fields
* Added filter for overriding WPEP course buttons for restricted courses

= 3.10.1 - 3/3/2018 =
* Fixed a bug where sometimes a contact ID wouldn't be associated with an existing contact when a new user registers
* Added start date syncing for Paid Memberships Pro

= 3.10 - 2/24/2018 =
* MailerLite CRM integration
* Bugfixes for auto-login links with Gravity Forms
* MemberMouse bugfixes

= 3.9.3 - 2/19/2018 =
* Added option for auto-login after Gravity Form submission
* Changed auto-login links to use cookies instead of sessions
* Allowed the [user_meta] shortcode to work with auto-login links
* Modified Infusionsoft contact ID lookup to just use primary email field

= 3.9.2 - 2/15/2018 =
* Proper state and country field handling for Mautic
* Fix for malformed saving of Tag Link field in LifterLMS course settings

= 3.9.1 - 2/12/2018 =
* Added "Apply Tags - Cancelled" to Paid Memberships Pro settings
* Added Ontraport affiliate tracking
* Added Ontraport page tracking
* Improved LearnDash content restriction filtering
* Optimized unnecessary contact ID lookups when Push All User Meta was enabled

= 3.9 - 1/31/2018 =
* Added AWeber CRM integration
* Linked tags now automatically added / removed on LearnDash group assignment
* Added auto-enrollment for LifterLMS courses
* Added post-checkout process locking for WooCommerce to reduce duplicate transactions

= 3.8.1 - 1/21/2018 =
* Added [else] method to shortcodes
* Added loggedout method to shortcodes
* Performance enhancements
* ConvertKit now auto-removes webhook tags
* Added option to apply tags when a WooCommerce subscription converts from free to paid

= 3.8 - 1/8/2018 =
* Intercom CRM integration
* myCRED integration
* Added bulk import for Salesforce
* Added batch processing for s2Member
* Fixed bug with administrators not being able to view content in a tag-restricted taxonomy

= 3.7.6 - 12/30/2016 =
* Added batch processing tool for MemberPress subscriptions
* Added setting to exclude restricted posts from archives / indexes
* Added ActiveCampaign site tracking
* Added Infusionsoft site tracking
* Added Drip site tracking

= 3.7.5 - 12/21/2017 =
* WooCommerce bugfixes

= 3.7.4 - 12/15/2017 =
* Improvements to tag handling with ConvertKit
* Added collapsible table headers to Contact Fields table
* Fixed bug in Mautic with applying tags to new contacts
* UserPro bugfixes

= 3.7.3 =
* Added global setting for tags to apply for all WooCommerce customers
* Fixed issue with restricted WooCommerce variations not being hidden
* Fixed bug with syncing Ultimate Member password updates from the Account screen
* Fixed LifterLMS account updates not being synced

= 3.7.2 =
* UserPro bugfixes
* Fixed hidden Import tab

= 3.7.1 =
* Fix for email addresses not updating on CRED profile forms
* Fix for Hold / Failed / Cancelled tags not being removed on WooCommerce subscription renewal

= 3.7 =
* Added support for the Mautic marketing automation platform
* Toolset CRED integration (for custom registration / profile forms)
* Fix for newly added tags not saving to WooCommerce variations

= 3.6.1 =
* Updated for compatibility with Ontraport API changes

= 3.6 =
* WishList Member integration
* Fixed tag fields sometimes not saving on WooCommerce variations
* Added async checkout for EDD purchases

= 3.5.2 =
* Improvements to filtering products in WooCommerce shop
* Significantly sped up and increased reliability of WooCommerce Asynchronous Checkout functionality
* Added ability to apply tags when refunded in EDD
* Better Tribe Events integration

= 3.5.1 =
* Improvements to auto login link system
* Added duplicating Gravity Forms feeds
* Restrict Content Pro bugfixes
* Added admin tools for resetting wpf_complete hooks on WooCommerce / EDD orders

= 3.5 =
* Added support for Ultimate Member 2.0 beta
* Added Tribe Events Calendar support (including support for Event Tickets and Event Tickets Plus)
* Added list selection options for Gravity Forms with ActiveCampaign
* Fixed variable tag fields not saving in WooCommerce
* Fixed new user notification emails sometimes not going out
* ActiveCampaign API performance enhancements

= 3.4.1 =
* Bugfixes

= 3.4 =
* Added access controls for widgets
* Improved "Preview with Tag" reliability
* WooCommerce now sends country name correctly to Infusionsoft
* Added logging support for Woo Subscriptions
* Support for additional BadgeOS achievement types
* Support for switching subscriptions with Woo Subscriptions
* Added batch processing options for Paid Memberships Pro
* Fixed issue with shortcodes using some visual page builders

= 3.3.3 =
* Added BadgeOS integration
* Staging mode now works with logging tool
* "Apply to children" now applies to nested children
* Added backwards compatibility support for WC < 3.0
* Passwords auto-generated by WooCommerce can now be synced
* Fixed issues with MemberPress non-recurring products
* Updated EDDSL plugin updater
* Fixes for Gravity Forms User Registration add-on
* Cleaned up internal fields from Contact Fields screen
* Sped up Import tool for Drip
* Option to disable API queue framework for debugging

= 3.3.2 =
* ConvertKit imports no longer limited to 50 contacts
* Restrict Content Pro improvements
* Fixed bug when adding new tags via tag select dropdown
* Fixed bug with using tag names in wpf shortcode on some CRMs
* Importing users now respects specified role
* Fixed error saving user profile when running BuddyPress with Groups disabled

= 3.3.1 =
* 3.3 bugfixes

= 3.3 =
* New features:
	* Added new logging / debugging tools
	* Contact Fields list is now organized by related integration
	* Added options for filtering users with no contact ID or no tags
	* Added ability to restrict WooCommerce variations by tag
* New Integrations:
	* WooCommerce Memberships
	* Simple Membership plugin integration
	* WP Execution Plan LMS integration
* New Integration Features:
	* MemberMouse memberships can now be linked with a tag
	* Expiration Date field syncing for Restrict Content Pro subscriptions
	* BuddyPress groups can now be linked with a tag
	* Added Payment Method field for sync with Paid Memberships Pro
	* Expiration Date can now be synced for Paid Memberships Pro
	* Added registration date, expiration date, and payment method for MemberPress subscriptions
	* Added "Apply tags when cancelled" field to MemberPress subscriptions
* Bug fixes:
	* Fixed bugs with editing tags via the user profile
	* user_meta Shortcode now pulls data from wp_users table correctly
	* "Apply on view" tags will no longer be applied if the page is restricted
	* Link with Tag fields no longer allow overlap with Apply Tags fields in certain membership integrations
	* AgileCRM fixes for address fields
* Enhancements:
	* Optimized many duplicate API calls
	* Added Dutch and Spanish translation files

= 3.2.1 =
* Bugfixes

= 3.2 =
* Salesforce integration
* Fixed issue with automatically assigning membership levels in MemberPress via webhook
* Fixed incompatibility with Infusionsoft Form Builder plugin
* Improvements to Drip integration
* Improvements to WooCommerce order batch processing tools
* Numerous bugfixes and performance enhancements

= 3.1.3 =
* Drip CRM can now trigger new user creation via webhook
* User roles now update properly when changed via webhook
* Import tool can now import more than 1000 contacts from Infusionsoft
* Gravity Forms bugfixes
* WP Engine compatibility bugfixes

= 3.1.2 =
* Added filter by tag option in admin Users list
* Added ability to restrict all posts within a restricted category or taxonomy term
* Added ability to restrict all bbPress forums at a global level
* Fixed bug with Ultimate Member's password reset process with Infusionsoft
* Added additional Google Analytics fields to contact fields list
* Bugfix to prevent looping when restricted content is set to redirect to itself

= 3.1.1 =
* Fixed inconsistencies with syncing user roles
* Additional bugfixes for WooCommerce 3.0.3

= 3.1.0 =
* Added built in user meta shortcode system
* Added support for webhooks with ConvertKit
* Updates for WooCommerce 3.0
* Additional built in fields for Agile CRM users
* Fixed bug where incorrect tags would be applied during automated payment renewals
* Fixed debugging log not working

= 3.0.9 =
* Added leadsource tracking to new user registrations for Google Analytics campaigns or custom lead sources
* Link click tracking can now be used on other elements in addition to links
* Agile CRM API improvements
* Misc. bugfixes

= 3.0.8 =
* Drip bugfixes
* Agile CRM improvements and bugfixes
* Added EDD payments to batch processing tools
* Added EDD Recurring Payments to batch processing tools
* Misc. UI improvements
* Bugfixes and speed improvements to batch operations

= 3.0.7 =
* Integration with User Meta plugin
* Fixed bug where restricted page would be shown if no redirect was specified
* Better support for Ultimate Member "checkboxes" fields

= 3.0.6 =
* Import tool has been updated to use new background processing system
* Added WordPress user role to list of meta fields for sync
* Support for additional Webhooks with Agile CRM
* Bugfix for long load times when getting user tags

= 3.0.5 =
* New tags will be loaded from the CRM if a user is given a tag that doesn't exist locally
* Resync contact IDs / Tags moved from Resynchronize button process to Batch Operations
* ActiveCampaign integration can now load all tags from account (no longer limited to first 100)
* Bugfix for LifterLMS memberships tag link

= 3.0.4 =
* Paid Memberships Pro bugfixes

= 3.0.3 =
* WP Job Manager integration
* Added category / taxonomy archive access restrictions
* Tags can now be added/removed from the edit user screen
* Added tooltips with additional information to batch processing tools
* Batch processes now update in real time after reloading WPF settings page

= 3.0.2 =
* Bugfixes for version 3.0

= 3.0.1 =
* Bugfixes for version 3.0

= 3.0 =
* Added Formidable Forms integration
* Added bulk editing tools for content protection
* New admin column for showing restricted content
* New background worker for batch operations on sites with a large number of users
* Tags are now removed properly when WooCommerce order refunded / cancelled
* Added option to remove tags when LifterLMS membership cancelled
* Added "Tag Link" capability for Paid Memberships Pro membership levels
* User roles can now be updated via the Update method in a webhook or HTTP Post
* Introduced beta support for Drip webhooks
* Initial sync process for Drip faster and more comprehensive
* All integration functions are now available via wp_fusion()->integrations
* Updated and improved automatic updates
* Numerous speed optimizations and bugfixes

= 2.9.6 =
* Improved integration with Paid Memberships Pro and Contact Form 7
* Bugfix for Radio type fields with Ultimate Member

= 2.9.5 =
* Added "Staging Mode" - all WP Fusion functions available, but no API calls will be sent
* Added Advanced settings pane with debugging tools

= 2.9.4 =
* LifterLMS bugfixes
* Deeper MemberPress integration

= 2.9.3 =
* Support for Asian character encodings with Infusionsoft
* Improvements to Auto-login links for hosts that don't support SESSION variables

= 2.9.2 =
* Misc. bugfixes

= 2.9.1 =
* Added support for MemberPress
* Updates for WooCommerce Subscriptions 2.x

= 2.9 =
* AgileCRM CRM support
* Added support for Thrive Themes Apprentice LMS
* Added support for auto-login links
* Added ability to apply tags when a link is clicked

= 2.8.3 =
* Allows shortcodes in restricted content message

= 2.8.2 =
* Fix for users being logged out when syncing password fields
* Ontraport bugifxes and performance tweaks
* Better error handling and debugging information for webhooks

= 2.8.1 =
* Added option for customizing restricted product add to cart message
* Misc. bug fixes

= 2.8 =
* ConvertKit CRM support
* LifterLMS updates to support LLMS 3.0+
* Ability to apply tags for LifterLMS membership levels
* Restricted Woo products can no longer be added to cart via URL

= 2.7.5 =
* Fixed Infusionsoft character encoding for foreign characters
* Fixed default field mapping overriding custom field selections

= 2.7.4 =
* Fixed bug where tag select boxes on LearnDash courses were limited to one selection

= 2.7.3 =
* Fixed bugs where ActiveCampaign lists would be overwritten on contact updates
* Restricted menu items no longer hidden in admin menu editor
* Improved s2Member support
* Fix for applying tags with variable WooCommerce subscriptions

= 2.7.2 =
* Added s2Member integration
* Added support for applying tags when WooCommerce coupons are used
* Added support for syncing AffiliateWP affiliate information
* Fixed returning passwords for imported contacts
* Updates for compatibility with plugin integrations

= 2.7.1 =
* Added LifterLMS support
* Fix for password updates not syncing from UM Account page

= 2.7 =
* Added Restrict Content Pro Integration
* Tag mapping for LearnDash Groups
* Can now sync user password from Ultimate Member reset password page

= 2.6.8 =
* Fix for contact fields not getting correct defaults on first install
* Fixed wrong lists getting assigned when updating AC contacts
* Significant API performance optimizations

= 2.6.7 =
* Enabled webhooks from Ontraport

= 2.6.6 =
* Fixed error in GForms integration

= 2.6.5 =
* Added support for syncing PMPro membership level name
* Fixed tags not applying when WooCommerce orders refunded
* Bugfixes and performance optimizations

= 2.6.4 =
* Batch processing tweaks

= 2.6.3 =
* Admin performance optimizations
* Batch processing / export tool

= 2.6.2 =
* Fix for tag select not appearing under Woo variations
* Formatting filters for date fields in ActiveCampaign
* Added quiz support to Gravity Forms
* Optimizations and performance tweaks

= 2.6.1 =
* Drip bugfixes
* Fix for restricted WooCommerce products not being hidden on some themes

= 2.6 =
* Added Drip CRM support
* Option to run Woo checkout actions asynchronously

= 2.5.5 =
* Updates to support Media Tools Addon

= 2.5.4 =
* Added option to push generated passwords back to CRM
* Added ability to apply tags in LearnDash when a quiz is marked complete
* Added ability to link a tag with an Ultimate Member role for automatic role assignment

= 2.5.3 =
* Fixed bug with WooCommerce variations and user-entered tags
* Fixed BuddyPress error when XProfile was disabled

= 2.5.2 =
* Fix for license activations / updates on hosts with outdated CURL
* Updates to support WPF addons
* Re-introduced import tool for ActiveCampaign users
* PHP 7 optimizations

= 2.5.1 =
* Improvements to initial ActiveCampaign sync
* Added instructions for AC import

= 2.5 =
* Added Paid Memberships Pro support
* Added course / tag relationship mapping for LearnDash courses
* Added automatic detection and mapping for BuddyPress profile fields
* Added "Apply tags when refunded" option for WooCommerce products
* Updated HTTP status codes on HTTP Post responses
* Tweaks to Import function for Ontraport users
* Fix for duplicate contacts being created on email address change with ActiveCampaign
* Fix for resyncing contacts with + symbol in email address

= 2.4.1 =
* Bugfixes for Ontraport integration
* Added Contact Type field mapping for Infusionsoft

= 2.4 =
* Added Ontraport CRM integration

= 2.3.2 =
* MemberMouse beta integration
* Fix for license activation for users on outdated versions of CURL / SSL
* Fix for BuddyPress pages not locking properly

= 2.3.1 =
* Fixed error in bbPress integration on old PHP versions

= 2.3 =
* Added Contact Form 7 support
* All bbPress topics now inherit permissions from their forum
* Added ability to lock bbPress forums archive
* Fixed bug with importing users by tag
* Fixed error with shortcodes using Thrive Content Builder
* Removed Add to Cart links for restricted products on the Woo store page
* Added option to hide restricted products from Woo store page entirely
* Added support for applying tags based on EDD variations

= 2.2.2 =
* Fix for tag shortcodes on AC
* Improvements to tag selection on Woo subscriptions / variations
* Woo Subscription fields now show on variable subscriptions as well
* Updated included Select2 libraries
* Restricted content with no tags specified will now be restricted for non-logged-in-users

= 2.2.1 =
* Fixed fatal error with GForms integration on lower PHP versions

= 2.2 =
* Added support for re-syncing contacts in batches for sites with large numbers of users
* Added support for ActiveCampaign webhooks
* Added support for EDD Recurring Payments
* Simplified URL structure for HTTP POST actions and added debugging output
* Fix for "0" tag appearing with ActiveCampaign tags

= 2.1.2 =
* Fixed bug where AC profiles wouldn't update if email address wasn't present in the form
* Fix for redirect rules not being respected for admins
* Fix for user_email and display_name not updating via HTTP Post

= 2.1.1 =
* Fixed bug affecting [wpf] shortcodes with users who had no tags applied

= 2.1 =
* Added support for applying tags in Woo when a subscription expires, is cancelled, or is put on hold
* Added "Push All" option for incompatible plugins and "user_meta" updates triggered via functions
* Fix for ActiveCampaign accounts with no tags
* Isolated AC API to prevent conflicts with plugins using outdated versions of the same API

= 2.0.10 =
* Bugfix when using tag label in shortcode

= 2.0.9 =
* Fix for tag checking logic with shortcode

= 2.0.8 =
* Fix for has_tag() function when using tag label
* Fixes for conflicts with other plugins using older versions of Infusionsoft API
* Support for re-adding contacts if they've been deleted in the CRM

= 2.0.7 =
* Resync contact now deletes local data if contact was deleted in the CRM
* Update license handler to latest version
* Resynchronize now force resets all tags
* Moved upgrade hook to later in the admin load process

= 2.0.6 =
* Support for manually marking WooCommerce payments as completed
* Improved support for servers with limited API tools
* Fixed wp_fusion()->user->get_tag_id() function to work with ActiveCampaign
* Bugfixes to shortcode content restriction system
* Fix for fields with subfields occasionally not showing up in GForms mapping
* Fix for new Ultimate Member field formats

= 2.0.5 =
* Fix for user accounts not created properly when WooCommerce and WooSubscriptions were both installed
* Added "apply to related lessons" feature to Sensei integration
* WooCommerce will now track leadsources and save them to a customer's contact record

= 2.0.4 =
* Bugfix for PHP notices appearing when shortcodes were in use and current user had no CRM tags
* Added SQL escaping for imported tag labels and categories
* Fix for contact address not updating existing contacts on guest checkout
* Fix for ACF not pulling / pushing field data properly

= 2.0.3 =
* Bugfix for importing users where CRM fields were mapped to multiple local fields
* Bugfix for Setup tab not appearing on initial install

= 2.0.2 =
* Bugfix for notices appearing for admins when admin bar was in use

= 2.0.1 =
* Bugfix for "update" action in HTTP Posts

= 2.0 =
* Complete rewrite and refactoring of core code
* Integration with ActiveCampaign, supporting all of the same features as Infusionsoft
* Custom fields are now available as a dynamic dropdown
* Ability to re-sync tags and custom fields within the plugin
* Integration with Sensei LMS
* Infusionsoft integration upgraded to use XMLRPC 4.0
* 100's of bug fixes, performance enhancements, and other improvements

= 1.6.4 =
* Improved compatibility with other plugins that use the iSDK class
* Changes to options framework to support 3rd party addons
* Added backwards compatibility for PHP versions less than 5.3

= 1.6.3 =
* Fix for registering contacts that already exist in Infusionsoft

= 1.6.2 =
* Fix for saving WooCommerce variation configuration
* Added automatic detection for when contacts are merged
* Improvements to wpf_template_redirect filter
* Added ability to apply tags per Ultimate Member registration form
* Ability to defer adding the contact until after the UM account has been activated
* Fixed bug with tags not appearing on admin user profile page
* Added filters for unsetting post types
* Added wpf_tags_applied and wpf_tags_removed actions

= 1.6.1 =
* Added has_tag function
* Added wpf_template_redirect filter
* Improved detection of registration form fields
* Fixed PHP notices appearing when using ACF
* Updates for compatibility with WP 4.3.1

= 1.6 =
* Can feed Gravity Forms data to Infusionsoft even if the user isn't logged in on your site
* Added support for Easy Digital Downloads
* Fixed bug with pulling date fields into Ultimate Member

= 1.5.2 =
* Fixed a bug with the "any" shortcode method
* More robust handling for user creation

= 1.5.1 =
* Fixed bug with account creation and Ultimate Member user roles

= 1.5 =
* LearnDash integration: can now apply tags on course/lesson/topic completion
* Content restrictions can now apply to child content
* New Ultimate Member fields are detected automatically
* Added ability to set user role via HTTP Post 'add'
* Added 'any' option to shortcodes

= 1.4.5 =
* Fixed global redirects not working properly
* Fixed issue with Preview As in admin bar
* Added 'wpf_create_user' filter
* Allowed for creating / updating users manually
* API improvements

= 1.4.4 =
* Misc. bugfixes with last release

= 1.4.3 =
* Improved compatibility of WooCommerce checkout with caching plugins
* Fixed bug with static page redirects
* Improved Ultimate Member integration
* Added support for combining "tag" and "not" in the WPF shortcode
* Added support for separating multiple shortcode tags with a comma
* Reduced API calls when profiles are updated
* Fixed bugs with guest checkout in WooCommerce

= 1.4.2 =
* Fixed bug with Ultimate Member integration in last release

= 1.4.1 =
* "Resync Contact" now pulls meta data as well
* Can now validate custom fields by name as well as label
* Added warning messages for WP Engine users
* Improved support for Ultimate Member membership plugin
* Fixed bug with redirects on Blog page / archive pages

= 1.4 =
* Added support for locking bbPress forums based on tags
* Added wpf_update_tags and wpf_update_meta shortcodes
* Support for overriding the new user welcome email with plugins
* Fixed bug with API Key generation
* Fixed bug with tags not applying after the specified delay
* Improved integration with WooCommerce checkout

= 1.3.5 =
* Added integration with Ultimate Member plugin

= 1.3.4 =
* Added "User Role" selection to import tool
* Added actions for user added and user updated
* Added "lock all" button to preview bar dropdown
* Fixed bug where tag preview wouldn't work on a static home page
* Fixed bug where shortcodes within the `[wpf]` shortcode wouldn't execute

= 1.3.3 =
* Improved integration support for user meta / profile plugins

= 1.3.2 =
* Tags will be removed when a payment is refunded
* Added support for applying tags with product variations
* Fixed bug with pushing ACF meta data on profile save
* Added support for pulling ACF meta data on profile load

= 1.3.1 =
* Added wpf_woocommerce_payment_complete action
* Added search filter to redirect page select dropdown
* Fixed "Class 'WPF_WooCommerce_Integration'" not found bug

= 1.3 =
* Added ability to import contacts from Infusionsoft as new WordPress users
* Added new plugin API methods for updating meta data and creating new users (see the documentation for more information)
* Added "unlock all" option to frontend admin toolbar
* Tags applied by a WooCommerce subscription can be removed when the subscription fails to charge, a trial period ends, or the subscription is put on hold
* Added support for syncing password and username fields
* Fixed a bug with applying tags at WooCommerce checkout when the user isn't logged in

= 1.2.1 =
* Added pull_user_meta() template tag
* Fixed bug with pushing user meta when no contact ID is found

= 1.2 =
* Added support for syncing multiselect fields with a contact record
* Added ability to trigger a campaign goal when a user profile is updated
* Added ability to manually resync a user profile if a contact record is deleted / recreated
* Now supports syncing with Infusionsoft built in fields. See the Infusionsoft "Table Documentation" for field name reference
* Users registered through a UserPro registration form will now have their password saved in Infusionsoft
* Fixed several bugs with user account creation using a UserPro registration form
* Fixed bug where tag categories with over 1,000 tags wouldn't import fully
* Fixed a bug that would cause checkout to fail with WooCommerce if a user is in guest checkout mode
* Numerous other bugfixes, optimizations, and improvements

= 1.1.5 =
* Fixed bug that would cause a user profile to fail to load when an IS contact wasn't found
* "Preview with tag" dropdown now groups tags by category and sorts alphabetically
* Fixed a bug with applying tags at WooCommerce checkout
* Notices for inactive / expired licenses

= 1.1.4 =
* Check for UserPro header on initial sync bug fixed
* Removed PHP notices on meta box when no tags are present
* "Preview with tag" has been removed from admin screens

= 1.1.3 =
* Automatic update bug fixed

= 1.1.2 =
* Fixed bug where users without email address would kill initial sync

= 1.1.1 =
* Changed name to WP Fusion

= 1.1 =
* EDD software licensing added

= 1.0.3 =
* Cleaned up apply_tags function

= 1.0.2 =
* Misc. bugfixes
* Added ability to apply tags to contact on WooCommerce purchase

= 1.0.1 =
* Misc. bugfixes
* Added content selection dropdown on post meta box

= 1.0 =
* Initial release


== Shortcodes ==

To restrict content based on a user's CRM tags, wrap the desired content in the WP Fusion shortcode, like so:

`[wpf tag=45] Restricted Content [/wpf]`

You can also specify the tag name, like so:

`[wpf tag="New Customer"] Restricted Content [/wpf]`

To show content only if a user _doesn't_ have a certain tag, use the following syntax:

`[wpf not=45] Restricted Content [/wpf]`

To force an update of the current user's tags before loading the rest of the page, use:

`[wpf_update_tags]`

To force an update of the current user's meta data before loading the rest of the page, use:

`[wpf_update_meta]`
