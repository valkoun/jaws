<?php
/**
 * Users URL maps
 *
 * @category   GadgetMaps
 * @package    Users
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$GLOBALS['app']->Map->Connect('Users', 
                              'ShowComment', 
                              'comment/{fusegadget}/{id}',
                              'index.php',
                              array(
                                    'fusegadget' =>  '[[:alnum:][:space:][:punct:]]+',
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestGroupAccess', 
                              'group/request/{group}',
                              'index.php',
                              array(
                                    'group' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestedGroupAccess', 
                              'group/requested/confirm',
                              'index.php'
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestedFriendGroup', 
                              'group/friend/requested',
                              'index.php'
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestFriendGroup', 
                              'group/friend/{group}',
                              'index.php',
                              array(
                                    'group' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'GroupDirectory', 
                              'group/directory/{Users_gid}',
                              'index.php',
                              array(
                                    'Users_gid' =>  '[[:alnum:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'GroupPage', 
                              'group/{group}/{id}',
                              'index.php',
                              array(
                                    'group' =>  '[[:alnum:][:space:][:punct:]]+',
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'GroupPage', 
                              'group/{group}',
                              'index.php',
                              array(
                                    'group' =>  '[[:alnum:][:space:][:punct:]]+$'
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestedFriend', 
                              'user/friend/requested',
                              'index.php'
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RequestFriend', 
                              'user/friend/{friend_id}',
                              'index.php',
                              array(
                                    'friend_id' =>  '[[:alnum:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RemovedFriend', 
                              'user/removefriend/removed',
                              'index.php'
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'RemoveFriend', 
                              'user/removefriend/{friend_id}',
                              'index.php',
                              array(
                                    'friend_id' =>  '[[:alnum:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 
                              'UserDirectory', 
                              'user/directory/{Users_gid}',
                              'index.php',
                              array(
                                    'Users_gid' =>  '[[:alnum:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Users', 'LoginForm', 'user/login');
$GLOBALS['app']->Map->Connect('Users', 'DefaultAction', 'user/login');
$GLOBALS['app']->Map->Connect('Users', 'Registration', 'user/register');
$GLOBALS['app']->Map->Connect('Users', 'Registered', 'user/registered');
$GLOBALS['app']->Map->Connect('Users', 'Logout', 'user/logout');
$GLOBALS['app']->Map->Connect('Users', 'Account', 'user/account');
$GLOBALS['app']->Map->Connect('Users', 'Profile', 'user/profile');
$GLOBALS['app']->Map->Connect('Users', 'Preferences', 'user/preferences');
$GLOBALS['app']->Map->Connect('Users', 'ForgotPassword', 'user/forget');
$GLOBALS['app']->Map->Connect('Users', 'PasswordRecovery', 'user/recover');
$GLOBALS['app']->Map->Connect('Users', 'ChangePassword', 'user/recover/key/{key}');
$GLOBALS['app']->Map->Connect('Users', 'ActivateUser', 'user/activate/key/{key}');
$GLOBALS['app']->Map->Connect('Users', 
                              'AccountPublic', 
                              'user/{name}',
                              'index.php',
                              array(
                                    'name' =>  '[[:alnum:]]+$',
                                  )
                              );
