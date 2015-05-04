<?php
// General Translation file (i18n) for Properties gadget
// Jaws 2005 <c>
// Generated by: Generatei18N.pl Perl Script <Jaws Developers>
//

/**
 * Meta Data
 *
 * "Project-Id-Version: Properties"
 * "Last-Translator: Alan Valkoun"
 * "Language-Team: EN"
 * "MIME-Version: 1.0"
 * "Content-Type: text/plain; charset=UTF-8"
 * "Content-Transfer-Encoding: 8bit"
 */

define('_EN_PROPERTIES_NAME', "Properties");
define('_EN_PROPERTIES_DESCRIPTION', "A gadget to create and manage Properties.");

// Actions
define('_EN_PROPERTIES_ACTION_INDEX', "Display an index of Property categories.");
define('_EN_PROPERTIES_ACTION_CATEGORY', "Display a single Property category.");

// Layout elements
define('_EN_PROPERTIES_LAYOUT_DISPLAY', "Google Map");
define('_EN_PROPERTIES_LAYOUT_DISPLAY_DESCRIPTION', "Displays a Google Map.");
define('_EN_PROPERTIES_LAYOUT_SEARCH', "Property Search Form");
define('_EN_PROPERTIES_LAYOUT_SEARCH_DESCRIPTION', "Displays a Search Box and options for filtering properties on your site.");
define('_EN_PROPERTIES_LAYOUT_GLOBALMAP', "Global Map");
define('_EN_PROPERTIES_LAYOUT_GLOBALMAP_DESCRIPTION', "Displays a Map of continents/global regions.");
define('_EN_PROPERTIES_LAYOUT_CITIESMAP', "Cities Map");
define('_EN_PROPERTIES_LAYOUT_CITIESMAP_DESCRIPTION', "Displays a Map of all cities that contain properties.");
define('_EN_PROPERTIES_LAYOUT_REGIONSMAP', "Regions Map");
define('_EN_PROPERTIES_LAYOUT_FORM', "Display Reservation Form");
define('_EN_PROPERTIES_LAYOUT_FORM_DESCRIPTION', "Shows a form for reserving rental Properties.");
define('_EN_PROPERTIES_LAYOUT_CALENDAR', "Display Availability Calendar");
define('_EN_PROPERTIES_LAYOUT_CALENDAR_DESCRIPTION', "Shows a calendar of available dates for reserving rental Properties.");
define('_EN_PROPERTIES_LAYOUT_CATEGORYMAP_DESCRIPTION', "Displays a Map of all Properties in this Category.");
define('_EN_PROPERTIES_LAYOUT_SLIDESHOW_DESCRIPTION', "Displays a photo Slideshow of all Properties in this Category.");
define('_EN_PROPERTIES_LAYOUT_SHOWONE_DESCRIPTION', "Displays photo of a random Property in this Category.");

// ACLs
define('_EN_PROPERTIES_ACL_DEFAULT', "Administrate Properties");
define('_EN_PROPERTIES_ACL_OWNPROPERTY', "Add/Edit/Delete their own Properties");
define('_EN_PROPERTIES_ACL_MANAGEPROPERTIES', "Manage Properties");
define('_EN_PROPERTIES_ACL_MANAGEPUBLICPROPERTIES', "Manage Public Properties");

// Errors
define('_EN_PROPERTIES_ERROR_PROPERTY_NOT_ADDED', "There was a problem adding the property.");
define('_EN_PROPERTIES_ERROR_PROPERTY_NOT_UPDATED', "There was a problem updating the property.");
define('_EN_PROPERTIES_ERROR_PROPERTY_NOT_DELETED', "There was a problem deleting the property.");
define('_EN_PROPERTIES_ERROR_PROPERTY_NOT_FOUND', "The property you requested could not be found.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_ADDED', "There was a problem adding the category.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED', "There was a problem updating the category.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED', "There was a problem deleting the category.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND', "The category you requested could not be found.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_LINKED', "There was a problem linking the category.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED', "There was a problem unlinking the category.");
define('_EN_PROPERTIES_ERROR_POST_NOT_ADDED', "There was a problem adding the post.");
define('_EN_PROPERTIES_ERROR_POST_NOT_UPDATED', "There was a problem updating the post.");
define('_EN_PROPERTIES_ERROR_POST_NOT_DELETED', "There was a problem deleting the post.");
define('_EN_PROPERTIES_ERROR_POST_NOT_FOUND', "The post you requested could not be found.");
define('_EN_PROPERTIES_ERROR_POST_LIMIT_REACHED', "You have reached the maximum number of posts that can be added to this property.");
define('_EN_PROPERTIES_ERROR_PROPERTYAMENITY_NOT_ADDED', "There was a problem adding the amenity.");
define('_EN_PROPERTIES_ERROR_PROPERTYAMENITY_NOT_UPDATED', "There was a problem updating the amenity.");
define('_EN_PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED', "There was a problem deleting the amenity.");
define('_EN_PROPERTIES_ERROR_PROPERTYAMENITY_NOT_FOUND', "The amenity you requested could not be found.");
define('_EN_PROPERTIES_ERROR_AMENITYTYPE_NOT_ADDED', "There was a problem adding the amenity type.");
define('_EN_PROPERTIES_ERROR_AMENITYTYPE_NOT_UPDATED', "There was a problem updating the amenity type.");
define('_EN_PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED', "There was a problem deleting the amenity type.");
define('_EN_PROPERTIES_ERROR_AMENITYTYPE_NOT_FOUND', "The amenity type you requested could not be found.");
define('_EN_PROPERTIES_ERROR_MINPRICE_LIMIT', "The Price of this property cannot be lower than {0}.");
define('_EN_PROPERTIES_ERROR_MAXPRICE_LIMIT', "The Price of this property cannot be higher than {0}.");
define('_EN_PROPERTIES_ERROR_USER_STATUS_LIMIT', "The property Status must be one of the following: {0}.");
define('_EN_PROPERTIES_ERROR_UNKNOWN_COLUMN', "An unknown sort column was provided.");
///FIXME they should not have the same translation.
define('_EN_PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED', "The property index could not be loaded.");
define('_EN_PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED', "The category index could not be loaded.");
define('_EN_PROPERTIES_ERROR_REGIONS_NOT_RETRIEVED', "The region index could not be loaded.");
define('_EN_PROPERTIES_ERROR_INDEX_NOT_LOADED', "The index could not be loaded.");
define('_EN_PROPERTIES_ERROR_PROPERTY_NOT_MASSIVE_DELETED', "There was a problem while deleting the group of properties");
define('_EN_PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', "The page could not be loaded: {0}.");
define('_EN_PROPERTIES_ERROR_FIELD_NOT_NUMERIC', "The {0} field can only contain numbers.");
define('_EN_PROPERTIES_ERROR_GADGETCONTENT_NOT_RETRIEVED', "The requested Gadget content could not be loaded: {0}.");
define('_EN_PROPERTIES_ERROR_KEY_NOT_SAVED', "There was a problem saving the key(s).");
define('_EN_PROPERTIES_ERROR_INVALID_TITLE', "The title cannot be empty, please choose a unique name for this item.");
define('_EN_PROPERTIES_ERROR_INVALID_FAST_URL', "There is already a menu item with that title, please choose a unique name for this item.");
define('_EN_PROPERTIES_ERROR_TITLE_EMPTY', "You must provide a title.");
define('_EN_PROPERTIES_ERROR_SAVE_QUICKADD', "There was a problem adding the item.");
define('_EN_PROPERTIES_ERROR_USER_AMENITYTYPES_NOT_DELETED', "There was a problem deleting the user's amenity types.");
define('_EN_PROPERTIES_ERROR_USER_AMENITYTYPE_NOT_DELETED', "There was a problem deleting the user's amenity type.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTIES_NOT_DELETED', "There was a problem deleting the user's properties.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTY_NOT_DELETED', "There was a problem deleting the user's property.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTYPARENTS_NOT_DELETED', "There was a problem deleting the user's property categories.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTYPARENT_NOT_DELETED', "There was a problem deleting the user's property category.");
define('_EN_PROPERTIES_ERROR_USER_AMENITIES_NOT_UPDATED', "There was a problem updating the user's property amenities.");
define('_EN_PROPERTIES_ERROR_USER_AMENITYTYPES_NOT_UPDATED', "There was a problem updating the user's amenity types.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTYPARENTS_NOT_UPDATED', "There was a problem updating the user's property categories.");
define('_EN_PROPERTIES_ERROR_USER_PROPERTIES_NOT_UPDATED', "There was a problem updating the user's properties.");

// Strings
define('_EN_PROPERTIES_QUICKADD_ADDPROPERTYPARENT', "Category");
define('_EN_PROPERTIES_QUICKADD_ADDPROPERTY', "Property");
define('_EN_PROPERTIES_TITLE_PROPERTY_INDEX', "Property Category Index");
define('_EN_PROPERTIES_DESCRIPTION_PROPERTY_INDEX', "Displays an index of Property categories.");
define('_EN_PROPERTIES_TITLE_NOT_FOUND', "Not Found");
define('_EN_PROPERTIES_CONTENT_NOT_FOUND', "The property(s) you requested could not be found, please contact the site administrator if you believe it should exist.");
define('_EN_PROPERTIES_MENU_ADMIN', "View Categories");
define('_EN_PROPERTIES_MENU_PROPERTIES', "View Properties");
define('_EN_PROPERTIES_MENU_AMENITY', "View Amenities");
define('_EN_PROPERTIES_MENU_AMENITYTYPES', "View Amenity Categories");
define('_EN_PROPERTIES_MENU_CATEGORY', "Edit Category");
define('_EN_PROPERTIES_MENU_PROPERTY', "Edit Property");
define('_EN_PROPERTIES_MENU_POST', "Edit Post");
define('_EN_PROPERTIES_MENU_SETTINGS', "Settings");
define('_EN_PROPERTIES_EDIT_PROPERTIES', "Edit Properties");
define('_EN_PROPERTIES_EDIT_AMENITIES', "Edit Amenities");
define('_EN_PROPERTIES_EDIT_AMENITY', "Edit Amenity");
define('_EN_PROPERTIES_EDIT_AMENITYTYPE', "Edit Amenity Category");
define('_EN_PROPERTIES_LAST_UPDATE', "Last Update");
define('_EN_PROPERTIES_NO_CATEGORY', "No categories were found.");
define('_EN_PROPERTIES_POST_CREATED', "The post has been created.");
define('_EN_PROPERTIES_POST_UPDATED', "The post has been updated.");
define('_EN_PROPERTIES_POST_DELETED', "The post has been deleted.");
define('_EN_PROPERTIES_PROPERTY_CREATED', "The property has been created.");
define('_EN_PROPERTIES_PROPERTY_UPDATED', "The property has been updated.");
define('_EN_PROPERTIES_PROPERTY_DELETED', "The property has been deleted.");
define('_EN_PROPERTIES_PROPERTY_MASSIVE_DELETED', "The group of properties have been deleted");
define('_EN_PROPERTIES_PROPERTY_AUTOUPDATED', "The property has been auto saved");
define('_EN_PROPERTIES_PROPERTYPARENT_CREATED', "The category has been created.");
define('_EN_PROPERTIES_PROPERTYPARENT_UPDATED', "The category has been updated.");
define('_EN_PROPERTIES_PROPERTYPARENT_DELETED', "The category has been deleted.");
define('_EN_PROPERTIES_PROPERTYPARENT_MASSIVE_DELETED', "The group of categories have been deleted");
define('_EN_PROPERTIES_PROPERTYPARENT_AUTOUPDATED', "The category has been auto saved");
define('_EN_PROPERTIES_PROPERTYAMENITY_CREATED', "The amenity has been created.");
define('_EN_PROPERTIES_PROPERTYAMENITY_UPDATED', "The amenity has been updated.");
define('_EN_PROPERTIES_PROPERTYAMENITY_DELETED', "The amenity has been deleted.");
define('_EN_PROPERTIES_PROPERTYAMENITY_MASSIVE_DELETED', "The group of amenities have been deleted");
define('_EN_PROPERTIES_PROPERTYAMENITY_AUTOUPDATED', "The amenity has been auto saved");
define('_EN_PROPERTIES_AMENITYTYPE_CREATED', "The amenity type has been created.");
define('_EN_PROPERTIES_AMENITYTYPE_UPDATED', "The amenity type has been updated.");
define('_EN_PROPERTIES_AMENITYTYPE_DELETED', "The amenity type has been deleted.");
define('_EN_PROPERTIES_AMENITYTYPE_MASSIVE_DELETED', "The group of amenity types have been deleted");
define('_EN_PROPERTIES_AMENITYTYPE_AUTOUPDATED', "The amenity type has been auto saved");
define('_EN_PROPERTIES_ADD_PROPERTY', "Add Property");
define('_EN_PROPERTIES_ADD_PROPERTYPARENT', "Add Category");
define('_EN_PROPERTIES_ADD_AMENITY', "Add Amenity");
define('_EN_PROPERTIES_ADD_AMENITYTYPES', "Add Amenity Category");
define('_EN_PROPERTIES_UPDATE_PROPERTY', "Update Property");
define('_EN_PROPERTIES_PROPERTYPARENT', "Category");
define('_EN_PROPERTIES_PAGE', "Property");
define('_EN_PROPERTIES_POSTS', "Posts");
define('_EN_PROPERTIES_POST_CONFIRM_DELETE', "Are you sure you want to delete this post?");
define('_EN_PROPERTIES_CONFIRM_DELETE_PROPERTY', "Are you sure you want to delete this property?");
define('_EN_PROPERTIES_CONFIRM_MASIVE_DELETE_PROPERTY', "Are you sure you want to delete selected properties?");
define('_EN_PROPERTIES_CONFIRM_RSS_HIDE', "Are you sure you want to not show this property?");
define('_EN_PROPERTIES_DRAFT', "Not Active");
define('_EN_PROPERTIES_PUBLISHED', "Active");
define('_EN_PROPERTIES_IMAGE', "Image");
define('_EN_PROPERTIES_RSSURL', "Import URL");
define('_EN_PROPERTIES_OVERRIDECITY', "Override City on Import");
define('_EN_PROPERTIES_ACTIVE', "Status");
define('_EN_PROPERTIES_TITLE', "Title");
define('_EN_PROPERTIES_DESCRIPTIONFIELD', "Description");
define('_EN_PROPERTIES_PARENT', "Parent");
define('_EN_PROPERTIES_RANDOMIZE', "Randomize Listings");
define('_EN_PROPERTIES_STATUS_FORSALE', "For Sale");
define('_EN_PROPERTIES_STATUS_FORRENT', "For Rent");
define('_EN_PROPERTIES_STATUS_FORLEASE', "For Lease");
define('_EN_PROPERTIES_STATUS_UNDERCONTRACT', "Under Contract");
define('_EN_PROPERTIES_STATUS_SOLD', "Sold");
define('_EN_PROPERTIES_STATUS_RENTED', "Rented");
define('_EN_PROPERTIES_STATUS_LEASED', "Leased");
define('_EN_PROPERTIES_GOOGLE_KEY', "Google Key");
define('_EN_PROPERTIES_GOOGLE_EXTRA', "You must sign up for a properties key from Google for the domain: {0}. <a target=\"_blank\" href=\"http://code.google.com/apis/properties/signup.html\">Click here to sign-up for a Maps key</a>");
define('_EN_PROPERTIES_KEY_SAVED', "The key(s) have been saved.");
define('_EN_PROPERTIES_VIEW_DETAILS', "View Property");
define('_EN_PROPERTIES_NO_LISTING_DETAILS', "No details were provided for this listing.");
define('_EN_PROPERTIES_SETTINGS_USER_STATUS_LIMIT', "Types of Properties Users Can Add");
define('_EN_PROPERTIES_USER_AMENITYTYPES_DELETED', "The user's amenity types have been deleted.");
define('_EN_PROPERTIES_USER_PROPERTIES_DELETED', "The user's properties have been deleted.");
define('_EN_PROPERTIES_USER_PROPERTYPARENTS_DELETED', "The user's property categories have been deleted.");
define('_EN_PROPERTIES_USER_PROPERTIES_UPDATED', "The user's properties have been updated.");
