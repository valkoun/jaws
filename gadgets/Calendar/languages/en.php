<?php
// General Translation file (i18n) for Calendar gadget
//

/**
 * Meta Data
 *
 * "Project-Id-Version: Calendar"
 * "Last-Translator: Alan Valkoun"
 * "Language-Team: EN"
 * "MIME-Version: 1.0"
 * "Content-Type: text/plain; charset=UTF-8"
 * "Content-Transfer-Encoding: 8bit"
 */

define('_EN_CALENDAR_NAME', "Calendar");
define('_EN_CALENDAR_DESCRIPTION', "A gadget to create and manage Calendars.");

// Actions
define('_EN_CALENDAR_ACTION_DEFAULT', "Display full Calendar.");
define('_EN_CALENDAR_ACTION_DETAIL', "Display an event.");
define('_EN_CALENDAR_ACTION_CATEGORYINDEX', "Display an index of Calendar categories.");
define('_EN_CALENDAR_ACTION_WEEK', "Display the week's events.");

// Layout elements
define('_EN_CALENDAR_LAYOUT_MINI', "Calendar");
define('_EN_CALENDAR_LAYOUT_MINI_DESCRIPTION', "Shows mini calendar of public events. On a user's page, a mini calendar of the user's events is shown.");
define('_EN_CALENDAR_LAYOUT_UPCOMINGEVENTS', "List Upcoming Events");
define('_EN_CALENDAR_LAYOUT_UPCOMINGEVENTS_DESCRIPTION', "Lists upcoming public events.  On a user's page, a list of the user's upcoming events is shown.");
define('_EN_CALENDAR_LAYOUT_CATEGORIES', "List of Calendars");
define('_EN_CALENDAR_LAYOUT_CATEGORIES_DESCRIPTION', "Lists the public Calendars.  On a user's page, a list of the user's calendars are shown.");
define('_EN_CALENDAR_LAYOUT_WEEK', "Calendar Week");
define('_EN_CALENDAR_LAYOUT_WEEK_DESCRIPTION', "Shows 7 days of the Calendar.  On a user's page, 7 days of the user's calendar is shown.");
define('_EN_CALENDAR_LAYOUT_YEAR', "Calendar Year");
define('_EN_CALENDAR_LAYOUT_YEAR_DESCRIPTION', "Shows 12 months of the Calendar.  On a user's page, 12 months of the user's calendar is shown.");
define('_EN_CALENDAR_LAYOUT_FULL', "Display \"{0}\"");
define('_EN_CALENDAR_LAYOUT_FULL_DESCRIPTION', "Shows a Calendar that you've created.");
define('_EN_CALENDAR_LAYOUT_CATEGORYUPCOMINGEVENTS', "List Upcoming Events in \"{0}\"");
define('_EN_CALENDAR_LAYOUT_CATEGORYUPCOMINGEVENTS_DESCRIPTION', "Lists upcoming Public Events from a Calendar that you've created");
define('_EN_CALENDAR_LAYOUT_RESERVATION', "Availability Reservation Form");
define('_EN_CALENDAR_LAYOUT_RESERVATION_DESCRIPTION', "Shows an arrival date form for checking Availability across All Calendars");
define('_EN_CALENDAR_LAYOUT_SHOWCOMMENTS', "Calendar Newsfeed");
define('_EN_CALENDAR_LAYOUT_SHOWCOMMENTS_DESCRIPTION', "Shows all newsfeed activity for the Calendar gadget.");
define('_EN_CALENDAR_LAYOUT_SHOWGROUPCOMMENTS', "Calendar Newsfeed of group {0}");
define('_EN_CALENDAR_LAYOUT_SHOWGROUPCOMMENTS_DESCRIPTION', "Shows all Calendar newsfeed activity for group {0}.");
define('_EN_CALENDAR_LAYOUT_SHOWGROUPCALENDAR', "Full Calendar of group {0}");
define('_EN_CALENDAR_LAYOUT_SHOWGROUPCALENDAR_DESCRIPTION', "Shows full Calendar of evnets of users in group {0}.");

// ACLs
define('_EN_CALENDAR_ACL_DEFAULT', "Administrate Calendar gadget");
define('_EN_CALENDAR_ACL_OWNEVENT', "Add/Edit/Delete their own Event");
define('_EN_CALENDAR_ACL_OWNPUBLICEVENT', "Add/Edit/Delete their own Public Event");
define('_EN_CALENDAR_ACL_OWNCATEGORY', "Add/Edit/Delete their own Calendar Category");
define('_EN_CALENDAR_ACL_MANAGECATEGORIES', "Manage all Calendar Categories");
define('_EN_CALENDAR_ACL_MANAGEEVENTS', "Manage all Events");
define('_EN_CALENDAR_ACL_MANAGEPUBLICEVENTS', "Manage all Public Events");

// Responses
define('_EN_CALENDAR_EVENT_ADDED', "Event has been added.");
define('_EN_CALENDAR_EVENT_UPDATED', "Event has been updated.");
define('_EN_CALENDAR_EVENT_DELETED', "Event has been deleted.");
define('_EN_CALENDAR_CATEGORY_ADDED', "Calendar has been added.");
define('_EN_CALENDAR_CATEGORY_UPDATED', "Calendar has been updated.");
define('_EN_CALENDAR_CATEGORY_DELETED', "Calendar has been deleted.");
define('_EN_CALENDAR_CATEGORY_MASSIVE_DELETED', "The group of Calendars have been deleted.");
define('_EN_CALENDAR_USER_CALENDARS_UPDATED', "The user's calendars have been updated.");
define('_EN_CALENDAR_USER_CALENDARS_DELETED', "The user's calendars have been deleted.");
define('_EN_CALENDAR_USER_EVENTS_UPDATED', "The user's events have been updated.");
define('_EN_CALENDAR_USER_EVENTS_DELETED', "The user's events have been deleted.");

//Ajax stuff
define('_EN_CALENDAR_CATEGORY_CONFIRM_DELETE', "Are you sure you want to delete this calendar? This will delete all events for this calendar as well.");
define('_EN_CALENDAR_CONFIRM_MASIVE_DELETE_CATEGORY', "Are you sure you want to delete the group of calendars? This will delete all events for these calendars as well.");
define('_EN_CALENDAR_EVENT_CONFIRM_DELETE', "Are you sure you want to delete this event?");

// Errors
define('_EN_CALENDAR_EVENT_NOT_ADDED', "There was a problem adding the event.");
define('_EN_CALENDAR_EVENT_NOT_UPDATED', "There was a problem updating the event.");
define('_EN_CALENDAR_EVENT_CANT_DELETE', "There was a problem deleting the event.");
define('_EN_CALENDAR_EVENT_NOT_FOUND', "The event you requested could not be found.");
define('_EN_CALENDAR_RECURRINGEVENT_NOT_ADDED', "There was a problem adding the recurring event.");
define('_EN_CALENDAR_RECURRINGEVENT_NOT_UPDATED', "There was a problem updating the recurring event.");
define('_EN_CALENDAR_RECURRINGEVENT_CANT_DELETE', "There was a problem deleting the recurring event.");
define('_EN_CALENDAR_GET_EVENTSOFCALENDAR', "There was a problem retrieving the Calendar's events.");
define('_EN_CALENDAR_GET_RECURRINGEVENTSOFCALENDAR', "There was a problem retrieving the Calendar's recurring events.");
define('_EN_CALENDAR_CATEGORY_NOT_ADDED', "There was a problem adding the Calendar.");
define('_EN_CALENDAR_CATEGORY_NOT_UPDATED', "There was a problem updating the Calendar.");
define('_EN_CALENDAR_CATEGORY_CANT_DELETE', "There was a problem deleting the Calendar.");
define('_EN_CALENDAR_ERROR_CATEGORY_NOT_MASSIVE_DELETED', "There was a problem deleting the group of Calendars.");
define('_EN_CALENDAR_CATEGORY_NOT_FOUND', "The Calendar you requested could not be found.");
define('_EN_CALENDAR_GET_CATEGORY', "There was a problem retrieving the Calendar.");
define('_EN_CALENDAR_ERROR_RECURRINGSCHEDULE', "Please provide days/dates that this recurring event repeats on.");
define('_EN_CALENDAR_ERROR_UNKNOWN_COLUMN', "An unknown sort column was provided.");
define('_EN_CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED', "The Calendar index could not be loaded.");
define('_EN_CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', "The Calendar page could not be loaded: {0}.");
define('_EN_CALENDAR_ERROR_DETAIL_NOT_LOADED', "The detail page could not be loaded: {0}.");
define('_EN_CALENDAR_ERROR_USER_EVENTS_NOT_UPDATED', "There was a problem updating the user's events.");
define('_EN_CALENDAR_ERROR_USER_CALENDARS_NOT_UPDATED', "There was a problem updating the user's calendars.");
define('_EN_CALENDAR_ERROR_USER_CALENDAR_NOT_DELETED', "There was a problem updating the user's calendar.");
define('_EN_CALENDAR_ERROR_USER_CALENDARS_NOT_DELETED', "There was a problem updating the user's calendars.");
define('_EN_CALENDAR_ERROR_USER_EVENT_NOT_DELETED', "There was a problem updating the user's event.");
define('_EN_CALENDAR_ERROR_USER_EVENTS_NOT_DELETED', "There was a problem updating the user's events.");

// Strings
define('_EN_CALENDAR_MENU_CALENDARS', "Manage Calendars");
define('_EN_CALENDAR_MENU_EVENTS', "Manage Calendar Events");
define('_EN_CALENDAR_ADD_CATEGORY', "Add Calendar");
define('_EN_CALENDAR_CONTENT_NOT_FOUND', "The calendar you requested could not be found, please contact the site administrator if you believe it should exist.");
define('_EN_CALENDAR_TITLE_NOT_FOUND', "Calendar not found");
define('_EN_CALENDAR_TITLE_INDEX', "Calendar Index");
define('_EN_CALENDAR_TITLE_DETAIL', "Calendar Detail");
define('_EN_CALENDAR_LAST_UPDATE', "Last Update");
define('_EN_CALENDAR_TYPE', "Type");
define('_EN_CALENDAR_STATUS', "Status");
define('_EN_CALENDAR_PUBLISHED', "Active");
define('_EN_CALENDAR_NOTPUBLISHED', "Not Active");
define('_EN_CALENDAR_TYPE_EVENTS', "Events");
define('_EN_CALENDAR_TYPE_AVAILABILITY', "Availability");
define('_EN_CALENDAR_TYPE_GOOGLE', "Google Calendar");
define('_EN_CALENDAR_GOOGLE_USERNAME', "Google Account Username");
define('_EN_CALENDAR_DESCRIPTIONFIELD', "Description");
define('_EN_CALENDAR_IMAGE', "Image");
define('_EN_CALENDAR_WHERE', "Where");
define('_EN_CALENDAR_SUMMARY', "Summary");
define('_EN_CALENDAR_TENTATIVE', "Tentative");
define('_EN_CALENDAR_RESERVED', "Reserved");
define('_EN_CALENDAR_STARTDATE', "Start Date");
define('_EN_CALENDAR_ENDDATE', "End Date");
define('_EN_CALENDAR_STARTTIME', "Start Time");
define('_EN_CALENDAR_ENDTIME', "End Time");
define('_EN_CALENDAR_MAX_OCCUPANCY', "Max Occupancy");
define('_EN_CALENDAR_EVENT_RSVP_JOIN', "Join");
define('_EN_CALENDAR_EVENT_RSVP_MAYBE', "Maybe");
define('_EN_CALENDAR_EVENT_RSVP_DECLINE', "Decline");
define('_EN_CALENDAR_QUICKADD_ADDCALENDARPARENT', "Calendar");
define('_EN_CALENDAR_QUICKADD_ADDEVENT', "Event");

