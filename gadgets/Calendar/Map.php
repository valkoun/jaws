<?php
/**
 * Calendar URL maps
 *
 * @category   GadgetMaps
 * @package    Calendar
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Calendar', 'DefaultAction', 'calendar/default');
$GLOBALS['app']->Map->Connect('Calendar', 'Index', 'calendar/index');
$GLOBALS['app']->Map->Connect('Calendar', 
                              'UpcomingEventsByCalendar', 
                              'calendar/events/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Calendar', 'UpcomingEvents', 'calendar/events');
$GLOBALS['app']->Map->Connect('Calendar', 
                              'Detail', 
                              'calendar/event/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Calendar', 
								'Day', 
								'calendar/{tdate}/{id}',
								'index.php',
								array(
                                    'tdate' =>  '[[:alnum:][:space:][:punct:]]+$',
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
									)
								);
$GLOBALS['app']->Map->Connect('Calendar', 
                              'Month', 
                              'calendar/month/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Calendar', 
                              'Week', 
                              'calendar/week/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Calendar', 
                              'Year', 
                              'calendar/year/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Calendar', 
								'CalendarXML', 
								'calendarxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
$GLOBALS['app']->Map->Connect('Calendar', 
                              'Calendar', 
                              'calendar/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
								