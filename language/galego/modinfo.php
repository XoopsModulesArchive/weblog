<?php
/**
 * $Id: modinfo.php,v 1.1 2006/03/18 03:23:15 mikhail Exp $
 * Copyright (c) 2003 by Jeremy N. Cowgar <jc@cowgar.com>
 * Copyright (c) 2003 by Hiro SAKAI (http://wellwine.net/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting
 * source code which is considered copyrighted (c) material of the
 * original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
 */
define('_MI_WEBLOG_NAME', 'weBLog');
define('_MI_WEBLOG_DESC', 'weBLogging/Journal system');
define('_MI_WEBLOG_SMNAME1', 'My weBLog');
define('_MI_WEBLOG_SMNAME2', 'Post');
define('_MI_WEBLOG_SMNAME3', 'Archives');

// submenu name
define('_MI_WEBLOG_DBMANAGER', 'Database');
define('_MI_WEBLOG_CATMANAGER', 'Categories');
define('_MI_WEBLOG_PRIVMANAGER', 'Privileges');

define('_MI_WEBLOG_NOTIFY', 'This weBLog');
define('_MI_WEBLOG_NOTIFYDSC', 'When something happens to this weBLog');
define('_MI_WEBLOG_ENTRY_NOTIFY', 'This weBLog entry');
define('_MI_WEBLOG_ENTRY_NOTIFYDSC', 'When something happens to this weBLog entry');

define('_MI_WEBLOG_ADD_NOTIFY', 'New Post');
define('_MI_WEBLOG_ADD_NOTIFYCAP', 'Notify me when a new post occurs');
define('_MI_WEBLOG_ADD_NOTIFYDSC', 'When a new post is made');
define('_MI_WEBLOG_ADD_NOTIFYSBJ', 'New weBLog Post');

define('_MI_WEBLOG_ENTRY_COMMENT', 'Comment Added');
define('_MI_WEBLOG_ENTRY_COMMENTDSC', 'Notify me when a new comment is posted for this item.');

define('_MI_WEBLOG_RECENT_BNAME1', 'Recent weBLogs');
define('_MI_WEBLOG_RECENT_BNAME1_DESC', 'Recent weBLog Entries');
define('_MI_WEBLOG_TOP_WEBLOGS', 'Top weBLogs');
define('_MI_WEBLOG_TOP_WEBLOGS_DESC', 'Top weBLogs');

// Config Settings
define('_MI_WEBLOG_NUMPERPAGE', 'Number of entries per page');
define('_MI_WEBLOG_NUMPERPAGEDSC', '');
define('_MI_WEBLOG_DATEFORMAT', 'Date format');
define('_MI_WEBLOG_DATEFORMATDSC', '');
define('_MI_WEBLOG_TIMEFORMAT', 'Time format');
define('_MI_WEBLOG_TIMEFORMATDSC', '');
define('_MI_WEBLOG_RECENT_DATEFORMAT', 'Date format in Recent weBLog\'s');
define('_MI_WEBLOG_RECENT_DATEFORMATDSC', '');
define('_MI_WEBLOG_SHOWAVATAR', 'Show users avatar on each entry');
define('_MI_WEBLOG_SHOWAVATARDSC', '');
define('_MI_WEBLOG_ALIGNAVATAR', 'Align avatar');
define('_MI_WEBLOG_ALIGNAVATARDSC', '');
define('_MI_WEBLOG_MINENTRYSIZE', 'Minimum size of entry (0=size checking disabled)');
define('_MI_WEBLOG_MINENTRYSIZEDSC', '');
define('_MI_WEBLOG_IMGURL', 'Image URL');
define('_MI_WEBLOG_IMGURLDSC', 'URL of image that is shown or indicated in printer-friendly page and RSS');

define('_MI_WEBLOG_UPDATE_READS_WHEN', 'Update read counter when');
define('_MI_WEBLOG_UPDATE_READS_WHENDSC', '');
define('_MI_WEBLOG_UPDATE_READS_WHEN1', 'When viewing details');
define('_MI_WEBLOG_UPDATE_READS_WHEN2', 'When viewing users weBLog');
define('_MI_WEBLOG_UPDATE_READS_WHEN3', 'When viewing entry in any list');

define('_MI_WEBLOG_TEMPLATE_ENTRIESDSC', 'Display entries for the given weBLog');
define('_MI_WEBLOG_TEMPLATE_POSTDSC', 'Post a new weBLog entry');
define('_MI_WEBLOG_TEMPLATE_DETAILSDSC', 'Display details about a weBLog entry');
define('_MI_WEBLOG_TEMPLATE_RSSFEEDDSC', 'RSS feed of weBLog entries');
define('_MI_WEBLOG_TEMPLATE_PRINTDSC', 'Printer friendly page');
define('_MI_WEBLOG_TEMPLATE_ARCHIVEDSC', 'Monthly archives');

define('_MI_WEBLOG_EDITORHEIGHT', 'Height of editor box (lines)');
define('_MI_WEBLOG_EDITORHEIGHTDSC', '');
define('_MI_WEBLOG_EDITORWIDTH', 'Width of editor box (characters)');
define('_MI_WEBLOG_EDITORWIDTHDSC', '');
define('_MI_WEBLOG_ONLYADMIN', "Allow only module admin's to post?");
define('_MI_WEBLOG_ONLYADMINDSC', 'Setting to no will allow all registered users to post, while yes would mean only module administrators can post.');

// wellwine for read cookie
define('_MI_WEBLOG_EXPIRATION', 'Expiration of read count (second)');
define('_MI_WEBLOG_EXPIRATIONDSC', 'Define the time expiration of each blog read count. The count will be incremented if it has passed this period since last viewing.');
define('_MI_WEBLOG_RSSSHOW', 'Show an icon linked to RSS feed');
define('_MI_WEBLOG_RSSSHOWDSC', '');
define('_MI_WEBLOG_RSSMAX', 'The number of entries to be fed in RSS');
define('_MI_WEBLOG_RSSMAXDSC', '');
