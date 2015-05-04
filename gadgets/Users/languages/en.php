<?php
/**
 * Meta Data
 *
 * "Project-Id-Version: Users"
 * "Last-Translator: Helgi Þormar Þorbjörnsson <dufuz@php.net>"
 * "Language-Team: EN"
 * "MIME-Version: 1.0"
 * "Content-Type: text/plain; charset=UTF-8"
 * "Content-Transfer-Encoding: 8bit"
 */

define('_EN_USERS_NAME', "Users");
define('_EN_USERS_DESCRIPTION', "User administration.");

/* ACLs */
define('_EN_USERS_ACL_DEFAULT', "Use users");
define('_EN_USERS_ACL_MANAGEUSERS', "User management");
define('_EN_USERS_ACL_MANAGEGROUPS', "Group management");
define('_EN_USERS_ACL_MANAGEPROPERTIES', "Properties management");
define('_EN_USERS_ACL_MANAGEUSERACLS', "User's ACLs management");
define('_EN_USERS_ACL_MANAGEGROUPACLS', "Group's ACLs management");
define('_EN_USERS_ACL_EDITACCOUNTPASSWORD', "Edit account password by users");
define('_EN_USERS_ACL_EDITACCOUNTINFORMATION', "Edit account information by users");
define('_EN_USERS_ACL_EDITACCOUNTPROFILE', "Edit account profile by users");
define('_EN_USERS_ACL_EDITACCOUNTPREFERENCES', "Edit account preferences by users");

/* Layout */
define('_EN_USERS_LAYOUT_LOGINBOX', "Login Box");
define('_EN_USERS_LAYOUT_LOGINBOX_DESC', "Display Login Box.");
define('_EN_USERS_LAYOUT_LOGINLINKS', "Login Links");
define('_EN_USERS_LAYOUT_LOGINLINKS_DESC', "Displays links to Admin and User sections.");
define('_EN_USERS_LAYOUT_LOGINBAR', "Login Bar");
define('_EN_USERS_LAYOUT_LOGINBAR_DESC', "Displays Login Bar.");
define('_EN_USERS_LAYOUT_REGISTER', "Register Form for Group {0}.");
define('_EN_USERS_LAYOUT_REGISTER_DESCRIPTION', "Displays Form to Register {0}.");
define('_EN_USERS_LAYOUT_REGISTERDEFAULT', "Default Register Form");
define('_EN_USERS_LAYOUT_REGISTERDEFAULT_DESCRIPTION', "Displays form to Register into default user group.");
define('_EN_USERS_LAYOUT_USERINFO', "User Details {0}");
define('_EN_USERS_LAYOUT_USERINFO_DESCRIPTION', "Displays detailed user information {0}");
define('_EN_USERS_LAYOUT_FIVEUSERSOFGROUP', "Five Users of Group {0}");
define('_EN_USERS_LAYOUT_FIVEUSERSOFGROUP_DESCRIPTION', "Displays five random users of group {0}");
define('_EN_USERS_LAYOUT_ADVANCED_FILTER', "Search Users");
define('_EN_USERS_LAYOUT_ADVANCED_FILTER_DESCRIPTION', "Displays advanced options for filtering users.");
define('_EN_USERS_LAYOUT_ADVANCED_FILTER_DEFAULT_SEARCH_TEXT', "Search by Keyword");
define('_EN_USERS_LAYOUT_RECOMMENDATIONS', "{0} Recommendations");
define('_EN_USERS_LAYOUT_RECOMMENDATIONS_DESCRIPTION', "Displays user recommendations.");
define('_EN_USERS_LAYOUT_COMMENTS', "{0} Newsfeed");
define('_EN_USERS_LAYOUT_COMMENTS_DESCRIPTION', "Displays {0} newsfeed activity.");
define('_EN_USERS_LAYOUT_FOLLOW_BUTTONS', "Follow Buttons");
define('_EN_USERS_LAYOUT_FOLLOW_BUTTONS_DESCRIPTION', "Displays follow buttons for subscribing to gadget stuff.");
define('_EN_USERS_ADMINLINK_LOGIN', "Admin Log-in");
define('_EN_USERS_ADMINLINK_CONTROLPANEL', "Control Panel");
define('_EN_USERS_USERLINK_LOGIN', "User Log-in");
define('_EN_USERS_USERLINK_LOGOUT', "User Log-out");
define('_EN_USERS_USERLINK_REGISTER', "Register User");
define('_EN_USERS_USERLINK_ACCOUNTHOME', "Account Home");
define('_EN_USERS_USERLINK_EDITPROFILE', "Edit Profile");
define('_EN_USERS_USERLINK_SETTINGS', "Edit Settings");

/* Group Management */
define('_EN_USERS_GROUPS_GROUPNAME', "Group name");
define('_EN_USERS_GROUPS_GROUPID', "Group ID");
define('_EN_USERS_GROUPS_GROUP', "Group");
define('_EN_USERS_GROUPS_GROUPS', "Groups");
define('_EN_USERS_GROUPS_ALL_GROUPS', "All Groups");
define('_EN_USERS_GROUPS_ADD', "Add a group");
define('_EN_USERS_GROUPS_NOGROUP', "No Group");
define('_EN_USERS_GROUPS_NO_SELECTION', "Please select a group from your left");
define('_EN_USERS_GROUPS_GROUP_INFO', "Group Information");
define('_EN_USERS_GROUPS_ALREADY_EXISTS', "Group {0} already exists");
define('_EN_USERS_GROUPS_INCOMPLETE_FIELDS', "Please fill the group name field");
define('_EN_USERS_GROUPS_UPDATE', "Update Group");
define('_EN_USERS_GROUPS_EDIT', "Edit Group");
define('_EN_USERS_GROUPS_DELETE', "Delete Group");
define('_EN_USERS_GROUPS_FOUNDER', "Group Founder");
define('_EN_USERS_GROUPS_PERMISSIONS', "Group permissions");
define('_EN_USERS_GROUPS_ACL_UPDATED', "Group privileges have been updated");
define('_EN_USERS_GROUPS_GROUP_NOT_EXIST', "The requested group does not exist.");
define('_EN_USERS_GROUPS_ADD_USERS', "Add users");
define('_EN_USERS_GROUPS_ADD_USER', "Add user to group");
define('_EN_USERS_GROUPS_CONFIRM_DELETE', "Are you sure you want to delete this group?");
define('_EN_USERS_GROUPS_CURRENTLY_EDITING_GROUP', "You are currently editing group {0}");
define('_EN_USERS_GROUPS_MARK_USERS', "Select the users you want to add to the group");
define('_EN_USERS_GROUPS_ACL_RESETED', "Group privileges have been reset");
define('_EN_USERS_DIRECTORY_USERS_NOT_FOUND', "No users were found.");
define('_EN_USERS_DIRECTORY_GROUPS_NOT_FOUND', "No groups were found.");
define('_EN_USERS_DIRECTORY_FRIEND_RECOMMENDATIONS_TITLE', "Recommendations");

/* Group Management Responses*/
define('_EN_USERS_GROUPS_CREATED', "Group {0} has been created.");
define('_EN_USERS_GROUPS_UPDATED', "Group {0} information has been updated");
define('_EN_USERS_GROUPS_DELETED', "Group {0} has been deleted.");
define('_EN_USERS_GROUPS_MANAGE_REQUESTS', "New Group Requests");
define('_EN_USERS_GROUPS_UPDATED_USERS', "The relations between users and groups have been updated");
define('_EN_USERS_USERS_REQUEST_GROUP_ACCESS', "Join {0}");
define('_EN_USERS_USERS_REQUEST_GROUP_STRING', "Are you sure you want to join {0}?");
define('_EN_USERS_USERS_REQUEST_FRIEND_GROUP', "Follow {0}");
define('_EN_USERS_USERS_REQUEST_FRIEND_GROUP_STRING', "Are you sure you want to follow the group {0}?");
define('_EN_USERS_USERS_REQUEST_FRIEND', "Follow User");
define('_EN_USERS_USERS_REQUEST_FRIEND_STRING', "Are you sure you want to follow the user {0}?");
define('_EN_USERS_USERS_REMOVE_FRIEND', "Remove Friend");
define('_EN_USERS_USERS_REMOVE_FRIEND_STRING', "Are you sure you want to un-friend the user {0}?");
define('_EN_USERS_USERS_REQUESTED_GROUP_ACCESS', "Your request has been sent. Thanks.");
define('_EN_USERS_USERS_AUTHORIZED_GROUP_ACCESS', "User has been added to group {0}.");

/* Group Management Errors*/
define('_EN_USERS_GROUPS_NOT_CREATED', "There was a problem creating group {0}.");
define('_EN_USERS_GROUPS_NOT_UPDATED', "There was a problem updating group {0}.");
define('_EN_USERS_GROUPS_CANT_DELETE', "There was a problem deleting group {0}.");
define('_EN_USER_GROUP_CANT_ADD', "There was a problem adding user to group{0}.");
define('_EN_USER_GROUP_CANT_DELETE', "There was a problem deleting user from group.");
define('_EN_USER_GROUP_CANT_UPDATE', "There was a problem updating user/group relation.");
define('_EN_USERS_ERROR_REQUEST_GROUP_ACCESS', "There was a problem submitting your request. Please try again tomorrow.");
define('_EN_USERS_ERROR_REQUEST_FRIEND_LOGGED', "Please log-in to continue.");
define('_EN_USERS_ERROR_REQUEST_GROUP_FRIEND_LOGGED', "Please log-in to continue.");

/* User Management */
define('_EN_USERS_ACCOUNT_INFO', "Account Information");
define('_EN_USERS_PERSONAL_INFO', "Personal Information");
define('_EN_USERS_USERS_ADD', "Add User");
define('_EN_USERS_ACCOUNT_EDIT', "Edit User");
define('_EN_USERS_ACCOUNT_DELETE', "Delete User");
define('_EN_USERS_ACCOUNT_LOGIN_AS_USER', "Log-in As User");
define('_EN_USERS_ACCOUNT_VIEW_FILES', "View User's Files");
define('_EN_USERS_ACLRULES', "ACL Rules");
define('_EN_USERS_USERS_USERNAME', "Username");
define('_EN_USERS_USERS_PASSWORD', "Password");
define('_EN_USERS_USERS_NICKNAME', "Nickname");
define('_EN_USERS_USERS_FIRSTNAME', "First name");
define('_EN_USERS_USERS_LASTNAME', "Last name");
define('_EN_USERS_USERS_USERID', "User ID");
define('_EN_USERS_USERS_WEBSITE', "Website");
define('_EN_USERS_USERS_COMPANY', "Company");
define('_EN_USERS_USERS_COMPANY_TYPE', "Business Type");
define('_EN_USERS_USERS_COMPANY_TYPE_RETAIL', "Retail"); 
define('_EN_USERS_USERS_COMPANY_TYPE_RESTAURANT', "Restaurant & Coffee"); 
define('_EN_USERS_USERS_COMPANY_TYPE_SERVICES', "Services"); 
define('_EN_USERS_USERS_COMPANY_TYPE_MEDICAL', "Medical & Pharmacy"); 
define('_EN_USERS_USERS_COMPANY_TYPE_MEDIA', "Media"); 
define('_EN_USERS_USERS_COMPANY_TYPE_SALON', "Salon & Personal Care"); 
define('_EN_USERS_USERS_COMPANY_TYPE_HEALTH', "Health & Fitness"); 
define('_EN_USERS_USERS_COMPANY_TYPE_HOMEGARDEN', "Home & Garden"); 
define('_EN_USERS_USERS_COMPANY_TYPE_ENTERTAINMENT', "Entertainment"); 
define('_EN_USERS_USERS_COMPANY_TYPE_FINANCIAL', "Financial"); 
define('_EN_USERS_USERS_COMPANY_TYPE_ARTSCULTURE', "Arts & Culture"); 
define('_EN_USERS_USERS_COMPANY_TYPE_LODGING', "Lodging"); 
define('_EN_USERS_USERS_COMPANY_TYPE_MANUFACTURING', "Manufacturing"); 
define('_EN_USERS_USERS_COMPANY_TYPE_GROCERY', "Grocery & Market"); 
define('_EN_USERS_USERS_COMPANY_TYPE_FARM', "Farm"); 
define('_EN_USERS_USERS_COMPANY_TYPE_NONPROFIT', "Non-Profit"); 
define('_EN_USERS_USERS_ADDRESS', "Address");
define('_EN_USERS_USERS_ADDRESS2', "Address 2");
define('_EN_USERS_USERS_CITY', "City");
define('_EN_USERS_USERS_COUNTRY', "Country");
define('_EN_USERS_USERS_REGION', "State/Province");
define('_EN_USERS_USERS_POSTAL', "Zip/Postal");
define('_EN_USERS_USERS_PHONE', "Telephone");
define('_EN_USERS_USERS_OFFICE', "Office");
define('_EN_USERS_USERS_TOLLFREE', "Toll-Free");
define('_EN_USERS_USERS_FAX', "Fax");
define('_EN_USERS_USERS_MERCHANT_ID', "Merchant ID");
define('_EN_USERS_USERS_KEYWORDS', "Keywords");
define('_EN_USERS_USERS_INTERESTS', "Interests");
define('_EN_USERS_USERS_COMMA_SEPARATED', "(Comma-separated)");
define('_EN_USERS_USERS_DESCRIPTION', "About You");
define('_EN_USERS_USERS_NOTIFICATION', "Notifications");
define('_EN_USERS_USERS_NOTIFICATION_EMAIL', "Notify me by E-mail and on the site");
define('_EN_USERS_USERS_NOTIFICATION_WEBSITE', "Notify me only on the site");
define('_EN_USERS_USERS_NOTIFICATION_SMS', "Notify me by Text Message");
define('_EN_USERS_USERS_ALLOW_COMMENTS', "Allow Comments");
define('_EN_USERS_USERS_IDENTIFIER', "Identifier");
define('_EN_USERS_USERS_TYPE', "Type");
define('_EN_USERS_USERS_TYPE_SUPERADMIN', "Super administrator");
define('_EN_USERS_USERS_TYPE_ADMIN', "Administrator");
define('_EN_USERS_USERS_TYPE_NORMAL', "Single user");
define('_EN_USERS_USERS_GENDER', "Gender");
define('_EN_USERS_USERS_MALE', "Male");
define('_EN_USERS_USERS_FEMALE', "Female");
define('_EN_USERS_USERS_BIRTHDAY', "Birthday");
define('_EN_USERS_USERS_BIRTHDAY_SAMPLE', "e.g., 2009/08/31");
define('_EN_USERS_USERS_SHOW_ALL', "Show all");
define('_EN_USERS_FIND_USER', "Find user");
define('_EN_USERS_USERS_SEND_AUTO_PASSWORD', "You can leave this empty and we will send a random password to your email");
define('_EN_USERS_USERS_PASSWORD_VERIFY', "Verify Password");
define('_EN_USERS_USERS_NO_SELECTION', "Please select an user");
define('_EN_USERS_USERS_PASSWORDS_DONT_MATCH', "The password entries do not match.");
define('_EN_USERS_USERS_PASSWORD_TOO_LONG', "The password you've entered is too long. It must be less than 32 characters.");
define('_EN_USERS_USERS_INCOMPLETE_FIELDS', "Some fields haven't been filled in.");
define('_EN_USERS_USERS_ALREADY_EXISTS', "There is another user using the same username ({0}).");
define('_EN_USERS_EMAIL_ALREADY_EXISTS', "There is another user using the same email ({0}).");
define('_EN_USERS_USERS_CONFIRM_NO_CHANGES', "Are you sure you don't want to save the data?");
define('_EN_USERS_USERS_SELECT_A_USER', "Select a user from the left.");
define('_EN_USERS_USER_NOT_EXIST', "The requested user does not exist.");
define('_EN_USERS_USERS_EDIT', "Edit User");
define('_EN_USERS_USERS_ACCOUNT_INFO', "Account Information");
define('_EN_USERS_USERS_ACCOUNT_PREF', "Account Preferences");
define('_EN_USERS_USERS_ACCOUNT_UPDATE', "Update Account");
define('_EN_USERS_USERS_PERMISSIONS', "Permissions");
define('_EN_USERS_USER_CONFIRM_DELETE', "Delete this user and all information this user has submitted?");
define('_EN_USERS_USER_MEMBER_OF_GROUPS', "{0} is a member of the groups below");
define('_EN_USERS_USER_MEMBER_OF_NO_GROUPS', "Currently {0} is not in any group");
define('_EN_USERS_THIS_USER', 'This user');
define('_EN_USERS_USER_CANT_AUTO_TURN_OFF_CP', "You can't turn off your privileges for all ControlPanel");
define('_EN_USERS_GROUPS', "Users groups");
define('_EN_USERS_USER_CURRENTLY_EDITING', "You are currently editing user {0}");
define('_EN_USERS_LOGIN_TITLE', "Login");
define('_EN_USERS_NOCHANGE_PASSWORD', "Leave this empty if you do not wish to change your password.");
define('_EN_USERS_RESET_ACL', "Reset ACL");
define('_EN_USERS_RESET_ACL_CONFIRM', "Are you sure you want to reset (delete) the permissions?");
define('_EN_USERS_PERSONAL_USER_EDIT', "Personal Information");
define('_EN_USERS_ADVANCED_USER_EDIT', "User options");
define('_EN_USERS_ADVANCED_OPTS_EDITOR', "User editor");
define('_EN_USERS_ADVANCED_OPTS_LANGUAGE', "Preferred language");
define('_EN_USERS_ADVANCED_OPTS_THEME', "Preferred theme");
define('_EN_USERS_ADVANCED_OPTS_NOT_YET', "No value defined yet");
define('_EN_USERS_MENU_IMPORTUSERS', "Import Users");
define('_EN_USERS_IMPORTUSERS_TYPE', "File Type");
define('_EN_USERS_IMPORTUSERS_TABDELIMITED', "Tab-delimited");
define('_EN_USERS_IMPORTUSERS_COMMASEPARATED', "Comma-separated");
define('_EN_USERS_IMPORTUSERS_FILE', "File");

/* MyAccount */
define('_EN_USERS_MYACCOUNT_UPDATED', "Your account details have been updated.");
define('_EN_USERS_MYACCOUNT_PASSWORDS_DONT_MATCH', "Your password and password verification do not match.");
define('_EN_USERS_MYACCOUNT_INCOMPLETE_FIELDS', "Please fill all the fields if you want to update your account.");
define('_EN_USERS_MYACCOUNT', "My Account");
define('_EN_USERS_EDIT_ACCOUNT', "Edit Account");
define('_EN_USERS_EDIT_PROFILE', "Edit Profile");
define('_EN_USERS_EDIT_PREFERENCES', "Edit Preferences");
define('_EN_USERS_PREFERENCES_UPDATED', "Your preferences have been updated.");
define('_EN_USERS_CONTROLPANEL', "Control Panel");
define('_EN_USERS_ACCOUNTHOME_NO_GADGETS', "There was a problem retrieving account panes.");
define('_EN_USERS_ACCOUNTHOME_PANE_NOT_UPDATED', "There was a problem updating the gadget pane.");
define('_EN_USERS_ACCOUNTHOME_PANE_UPDATED', "Gadget pane has been updated.");
define('_EN_USERS_USERS_ACCOUNT_HOME', "Welcome");
define('_EN_USERS_ACCOUNTHOME_SUBSCRIPTION_NOT_DELETED', "There was a problem deleting the subscription.");
define('_EN_USERS_ACCOUNTHOME_SUBSCRIPTION_DELETED', "Your subscription has been deleted.");
define('_EN_USERS_ACCOUNTHOME_NO_COMMENTS', "Nothing has been added yet.");
define('_EN_USERS_ACCOUNTHOME_NO_SUBSCRIPTIONS', "It seems this timeline is empty. Share something to get started.");
define('_EN_USERS_ACCOUNTHOME_CREATE_SUBSCRIPTIONS', "Click here to subscribe to keywords.");
define('_EN_USERS_ACCOUNTPUBLIC_NO_UPDATES', "{0}, and start connecting today.");
define('_EN_USERS_ACCOUNTPUBLIC_CANT_LOAD_PROFILE', "There was a problem loading the account profile you requested. Please contact us if you believe this is an error.");
define('_EN_USERS_ACCOUNTHOME_SHAREBUTTON', "Share Something");
define('_EN_USERS_ACCOUNTHOME_PANE_UPDATES', "All");
define('_EN_USERS_ACCOUNTHOME_PANE_UPDATES_TITLE', "Newsfeed");
define('_EN_USERS_ACCOUNTHOME_PANE_FRIENDS', "Users");
define('_EN_USERS_ACCOUNTHOME_PANE_FRIENDS_TITLE', "Users");
define('_EN_USERS_ACCOUNTHOME_PANE_GROUPS', "Groups");
define('_EN_USERS_ACCOUNTHOME_PANE_GROUPS_TITLE', "Groups");
define('_EN_USERS_CONFIRM_REMOVE_FRIEND', "Are you sure you want to un-friend this user?");
define('_EN_USERS_ACCOUNTHOME_REMOVE_FRIEND', "Remove Friend");
define('_EN_USERS_ACCOUNTHOME_SEND_FRIEND_MESSAGE', "Send Message");
define('_EN_USERS_CONFIRM_DELETE_USER_FROM_GROUP', "Are you sure you want to remove this user from the '{0}' group?");
define('_EN_USERS_CONFIRM_ADD_USER_TO_GROUP', "Are you sure you want to add this user to the '{0}' group?");
define('_EN_USERS_ACCOUNTHOME_ADD_USER_TO_GROUP', "Add to {0} group");
define('_EN_USERS_SHARECOMMENT_TITLE', "Sharing");
define('_EN_USERS_SHARECOMMENT_EVERYONE', "Everyone");
define('_EN_USERS_SHARECOMMENT_EVERYONE_DESC', "Share with everyone (anyone can view this post)");
define('_EN_USERS_SHARECOMMENT_FRIENDS_ONLY', "All Friends");
define('_EN_USERS_SHARECOMMENT_FRIENDS_ONLY_DESC', "Share only with your friends (and those subscribed to you)");
define('_EN_USERS_SHARECOMMENT_SPECIFIC_USERS', "Specific Friends");
define('_EN_USERS_SHARECOMMENT_SPECIFIC_USERS_DESC', "Share with specific users (or groups)");
define('_EN_USERS_COMMENT_REPLY', "You have a new message on {0}");
define('_EN_USERS_COMMENT_MAIL_VISIT', "To view the message thread: ");
define('_EN_USERS_COMMENT_MAIL_VISIT_URL', '<a href="{0}">Click here to reply</a>');
define('_EN_USERS_COMMENT_MAIL_SUBSCRIBEPREFS_URL', '<a href="{0}">Click here to change subscription preferences.</a>');
define('_EN_USERS_COMMENT_MAIL_WELCOME', "Hello,");
define('_EN_USERS_COMMENT_MAIL_WROTE', 'wrote:');
define('_EN_USERS_ERROR_COMMENT_NOT_ADDED', 'There was a problem trying to add the message.');
define('_EN_USERS_MESSAGING_ACTIVE', 'Published');
define('_EN_USERS_MESSAGING_EMAILPAGE', 'Campaign');
define('_EN_USERS_MESSAGING_EMAILPAGES', 'Campaigns');
define('_EN_USERS_MESSAGING_ADD_PAGE', 'Add Campaign');
define('_EN_USERS_MESSAGING', 'Messaging');
define('_EN_USERS_MESSAGING_MESSAGES', 'Messages');
define('_EN_USERS_MESSAGING_ADD_MESSAGE', 'Add Message');
define('_EN_USERS_MESSAGING_SYNDICATION', 'Syndication');
define('_EN_USERS_MESSAGING_LAST_UPDATE', 'Last Update');
define('_EN_USERS_COMMENT_MAILED_TO_USERS', "Your message was sent to {0} users.");
define('_EN_USERS_ERROR_COMMENT_NOT_MAILED_TO_USERS', "There was a problem sending to {0} users.");
define('_EN_USERS_ACCOUNTHOME_ADDGROUPBUTTON', "New Group");
define('_EN_USERS_QUICKADD_ADDGROUP', "Add Group");


/* User Management Responses */
define('_EN_USERS_USERS_CREATED', "User {0} has been created.");
define('_EN_USERS_USERS_UPDATED', "User {0} has been updated.");
define('_EN_USERS_USERS_ACL_UPDATED', "User privileges have been updated.");
define('_EN_USERS_USER_DELETED', "User {0} has been deleted.");
define('_EN_USERS_USERS_ACL_RESETED', "User privileges have been reseted.");
define('_EN_USERS_USERS_PERSONALINFO_UPDATED', "Personal information have been updated");
define('_EN_USERS_USERS_ADVANCED_UPDATED', "User advanced options have been updated");

/* User Management Errors */
define('_EN_USERS_EMBED_ERROR_INVALID_URL', "The URL provided was not correct.");
define('_EN_USERS_EMBED_ERROR_NOT_ADDED', "There was a problem embedding your gadget.");
define('_EN_USERS_EMBED_ERROR_GET_GADGET', "There was a problem retrieving the embedded gadget.");
define('_EN_USERS_EMBED_SITES', "Select a place to embed it:");
define('_EN_USERS_EMBED_PAGES', "Embed With:");
define('_EN_USERS_EMBED_NO_PAGES', "No pages were found.");
define('_EN_USERS_EMBED_USERID_SEARCH', "Find My User ID");
define('_EN_USERS_EMBED_ADD', "Embed It!");
define('_EN_USERS_EMBED_CONFIRM_DELETE', "Are you sure you want to un-embed this gadget?");
define('_EN_USERS_EMBED_ERROR_NOT_DELETED', "There was a problem un-embedding gadget.");
define('_EN_USERS_EMBED_ERROR_DELETED', "Your gadget was un-embedded successfully.");
define('_EN_USERS_USERS_NOT_CREATED', "There was a problem creating user {0}.");
define('_EN_USERS_USERS_NOT_UPDATED', "There was a problem updating user {0}.");
define('_EN_USERS_USERS_CANT_DELETE', "There was a problem deleting user {0}.");
define('_EN_USERS_USERS_CANT_DELETE_SELF', "You can't delete yourself.");
define('_EN_USERS_USERS_NOT_ADVANCED_UPDATED', "There was a problem updating advanced user options");
define('_EN_USERS_USERS_ERROR_GET_GADGET_STATUS', "There was a problem getting gadget status of {0} gadget");
define('_EN_USERS_USERS_CREATED_NOT_ADDED_TO_GROUP', "Can't add you to non-existent group {0}.");

/* Properties */
define('_EN_USERS_PROPERTIES_ANON_REGISTER', "Anonymous users can register");
define('_EN_USERS_PROPERTIES_ANON_REPETITIVE_EMAIL', "Anonymous can register by repetitive email");
define('_EN_USERS_PROPERTIES_ANON_ACTIVATION', "Anonymous activation type");
define('_EN_USERS_PROPERTIES_ACTIVATION_AUTO', "Auto");
define('_EN_USERS_PROPERTIES_ACTIVATION_BY_USER', "By User");
define('_EN_USERS_PROPERTIES_ACTIVATION_BY_ADMIN', "By Admin");
define('_EN_USERS_PROPERTIES_ANON_TYPE', "Default user's type of registered user");
define('_EN_USERS_PROPERTIES_ANON_GROUP', "Default group of registered user");
define('_EN_USERS_PROPERTIES_PASS_RECOVERY', "Users can recover their passwords");
define('_EN_USERS_PROPERTIES_AUTH_METHOD', "Authentication Method");
define('_EN_USERS_PROPERTIES_PRIORITY', "Priority access");
define('_EN_USERS_PROPERTIES_PRIORITY_UGD', "First User, then Group and then Default values");
define('_EN_USERS_PROPERTIES_PRIORITY_GUD', "First Group, then User and then Default values");
define('_EN_USERS_PROPERTIES_PRIORITY_UD', "First User and then Default values");
define('_EN_USERS_PROPERTIES_PRIORITY_GD', "First Group and then Default values");
define('_EN_USERS_PROPERTIES_UPDATED', "Properties have been updated");
define('_EN_USERS_PROPERTIES_CANT_UPDATE', "There was a problem when updating the properties");
define('_EN_USERS_PROPERTIES_PROTECTED_PAGES', "Password Protected Pages");
define('_EN_USERS_PROPERTIES_DEFAULT_GROUP', "Default Group That Users Register Into");
define('_EN_USERS_PROPERTIES_ENABLED_GADGETS', "Gadgets Enabled for Users");
define('_EN_USERS_PROPERTIES_SOCIAL_SIGN_IN', "Social Sign-in (Sign-up with Google, Yahoo!, etc)");
define('_EN_USERS_PROPERTIES_REGISTER_REQUIRES_ADDRESS', "Registration requires address");

/* Permission message */
define('_EN_USERS_NO_PERMISSION_TITLE', "No permission");
define('_EN_USERS_NO_PERMISSION_DESC', "I'm sorry but you don't have permission to execute this action ({0}::{1}).");
define('_EN_USERS_NO_PERMISSION_ANON_DESC', "The reason is that you are logged as anonymous. A possible fix is to <a href=\"{0}\">login again</a> with a valid username.");

/* Anon registration */
define('_EN_USERS_REGISTER', "Create Account");
define('_EN_USERS_REGISTER_NOT_ENABLED', "Sorry but anonymous users can't register, ask the admininistrator for an account.");
define('_EN_USERS_REGISTER_ALREADY_LOGGED', "You are already logged with a non-anonymous account, click <a href=\"{0}\">here</a> if you want to logout");
define('_EN_USERS_REGISTER_VALID_USERNAMES', "Can only contain letters or numbers, with a length of more than 2 characters.");
define('_EN_USERS_REGISTER_USERNAME_NOT_VALID', "The username is already taken, or not valid. It can only contain letters or numbers, with a length of more than 2 characters");
define('_EN_USERS_REGISTER_EMAIL_NOT_VALID', "The email is not totally valid, please check it");
define('_EN_USERS_REGISTER_NOT_CREATED_SENDMAIL', "There was a problem while sending the password to {1}, however, for security reasons we deleted your account from the database. Try creating your account again.");
define('_EN_USERS_REGISTER_SUBJECT', "User registration - {0}");
define('_EN_USERS_REGISTER_HELLO', "Hello {0}");
define('_EN_USERS_REGISTER_ADMIN_MAIL_MSG', "A new account has been created"); 
define('_EN_USERS_REGISTER_MAIL_MSG', "We got an account registration that points to your email.\nIf you think this is an error please reply back telling us the error.");
define('_EN_USERS_REGISTER_ACTIVATION_MAIL_MSG', "Your account has been created.\nHowever, you need activate your account before you can use this website.");
define('_EN_USERS_REGISTER_ACTIVATION_SENDMAIL_FAILED', "There was a problem while sending the activation link to {0}, however, for security reasons we deleted your user from the database");
define('_EN_USERS_REGISTER_RANDOM_MAIL_MSG', "We got an account registration that points to your email.\nWe also decided to create a strong-random password for you, so we are sending it along to you here.");
define('_EN_USERS_REGISTER_BY_ADMIN_RANDOM_MAIL_MSG', "We got an account registration that points to your email.\nWe also decided to create a strong-random password for you, so we are sending it along to you here.\nHowever, an administrator needs to activate your account before you can use this website.");
define('_EN_USERS_REGISTER_BY_USER_RANDOM_MAIL_MSG', "We got an account registration that points to your email.\nWe also decided to create a strong-random password for you, so we are sending it along to you here.\nHowever, you need activate your account before you can use this website.");
define('_EN_USERS_REGISTER_RANDOM_SENDMAIL_FAILED', "There was a problem while sending the password to {0}, however, for security reasons we deleted your user from the database");
define('_EN_USERS_REGISTER_REGISTERED', "Account created");
define('_EN_USERS_REGISTER_REGISTERED_MSG', "Thanks! Your account has been created. To verify your account and log-in, please check the e-mail address you supplied{0} in a couple of minutes and follow the directions we've supplied you. If this isn't correct, <a href=\"{1}\">click here to try again</a>");

/* Anon activation */
define('_EN_USERS_ACTIVATE_ACTIVATED', "Congratulations!");
define('_EN_USERS_ACTIVATE_NOTACTIVATED', "Uh Oh!");
define('_EN_USERS_ACTIVATE_ACTIVATION_LINK', "Activation link");
define('_EN_USERS_ACTIVATE_ACTIVATION_BY_ADMIN_MSG', "Your account has been created.\nHowever, this website requires account activation by the administrator group.\nAn e-mail has been sent to them and you will be informed when your account has been activated.");
define('_EN_USERS_ACTIVATE_ACTIVATION_BY_USER_MSG', "Your account has been created.\nHowever, this website requires account activation, an activation key has been sent to the e-mail address you provided.\nPlease check your e-mail for further information.");
define('_EN_USERS_ACTIVATE_ACTIVATED_BY_USER_MSG', "The account has been activated, you can <a href=\"{0}\">login</a> whenever you want.");
define('_EN_USERS_ACTIVATE_ACTIVATED_BY_ADMIN_MSG', "The account has been activated.");
define('_EN_USERS_ACTIVATE_ACTIVATED_MAIL_MSG', "Your account has been activated, you can login whenever you want.");
define('_EN_USERS_ACTIVATE_NOT_ACTIVATED_SENDMAIL', "There was a problem while sending the activation link to {0}, however, for security reasons we deleted your user from the database");
define('_EN_USERS_ACTIVATION_KEY_NOT_VALID', "Sorry, the activation key is not valid");
define('_EN_USERS_ACTIVATION_ERROR_ACTIVATION', "There was an error while activating the account");

/* Password recovery */
define('_EN_USERS_FORGOT_PASSWORD', "Forgot your password?");
define('_EN_USERS_FORGOT_REMEMBER', "Remember password");
define('_EN_USERS_FORGOT_MAIL_SENT', "An email has been sent with information to change your password");
define('_EN_USERS_FORGOT_USERMAIL_SENT', "An email has been sent with information to change your password");
define('_EN_USERS_FORGOT_ERROR_SENDING_MAIL', "There was an error while sending you an email with more information about recovering your password");
define('_EN_USERS_FORGOT_MAIL_MESSAGE', "Someone has asked us to remember your password. To change your password open the following link, otherwise just ignore this email (your password won't be changed).");
define('_EN_USERS_FORGOT_KEY_NOT_VALID', "Sorry, the key is not valid");
define('_EN_USERS_FORGOT_PASSWORD_CHANGED', "The new password (auto-generated) has been sent to your email");
define('_EN_USERS_FORGOT_PASSWORD_CHANGED_SUBJECT', "New password");
define('_EN_USERS_FORGOT_PASSWORD_CHANGED_MESSAGE', "A new password has been asigned to your account, you can find it below. In order to change it you need to login with your username ({0}) and this password, then you can update your profile.");
define('_EN_USERS_FORGOT_ERROR_CHANGING_PASSWORD', "There was an error while changing your password");
define('_EN_USERS_AUTHORIZE_MAIL_SENT', "An email has been sent with information to login to your account");
define('_EN_USERS_AUTHORIZE_ERROR_SENDING_MAIL', "There was an error while sending you an email with more information about logging in to your account");
define('_EN_USERS_AUTHORIZE_MAIL_MESSAGE', "To login to your account please open the following link");
