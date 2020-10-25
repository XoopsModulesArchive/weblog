<?php
/*
 * $Id: header.php,v 1.3 2006/03/22 09:57:17 mikhail Exp $
 *
 * Copyright (c) 2003 by Jeremy N. Cowgar <jc@cowgar.com>
 *
 */

if (!defined('XOOPS_ROOT_PATH')) {
    require_once '../../mainfile.php';
}

// $mydirname / $mydirnumber are critical GLOBALS.
if (!isset($mydirname)) {
    $mydirname = basename(__DIR__);
}
if (!isset($mydirnumber)) {
    if (preg_match("/^(\D+)(\d+)$/", $mydirname, $match)) {
        $mydirnumber = (string)$match[2];
    } else {
        $mydirnumber = '';
    }
}
require_once XOOPS_ROOT_PATH . '/modules/' . $mydirname . '/config.php';

// load language_main file
if (!defined('WEBLOG_BL_LOADED')) {
    if (file_exists(sprintf('%s/modules/%s/language/%s/main.php', XOOPS_ROOT_PATH, $mydirname, $xoopsConfig->language))) {
        require_once sprintf('%s/modules/%s/language/%s/main.php', XOOPS_ROOT_PATH, $mydirname, $xoopsConfig->language);
    } else {
        require_once sprintf('%s/modules/%s/language/english/main.php', XOOPS_ROOT_PATH, $mydirname);
    }
}
