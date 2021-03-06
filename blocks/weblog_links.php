<?php
/*
 * $Id: weblog_links.php,v 1.1 2006/03/29 05:57:07 mikhail Exp $
 * Copyright (c) 2003 by Jeremy N. Cowgar <jc@cowgar.com>
 * Copyright (c) 2003 by wellwine <http://wellwine.zive.net>
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

if (!defined('WEBLOG_BLOCK_LINKS_INCLUDED')) {
    define('WEBLOG_BLOCK_LINKS_INCLUDED', 1);

    /*
     * $options[0] = links module
     * $options[1] = links number
     * $options[2] = show only post or not
     * $options[3] = show link description or not
     */

    function b_weblog_links_show($options)
    {
        global $xoopsDB, $xoopsUser;

        $mydirname = empty($options[0]) ? basename(dirname(__DIR__)) : $options[0];

        $link_module = $options[1];

        $link_num = $options[2];

        $only_post = $options[3];

        $showdsc = $options[4];

        if ('1' == $only_post) {
            if (!preg_match("|weblog\d*/post\.php$|", $_SERVER['SCRIPT_NAME'])) {
                return false;
            }
        }

        $currentuid = !empty($xoopsUser) ? $xoopsUser->getVar('uid', 'E') : 0;

        $user_id = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

        $submitter = (empty($user_id)) ? $currentuid : $user_id;

        $block = [];

        if (preg_match("/^mylinks\d*$/", $options[0])) {
            // in case of mylinks module

            $sql = sprintf(
                'SELECT c.title AS category, l.title AS title, url, description AS dsc FROM %s AS c, %s AS l, %s AS d WHERE c.cid=l.cid AND d.lid=l.lid AND status=1 ',
                $xoopsDB->prefix($options[0] . '_cat'),
                $xoopsDB->prefix($options[0] . '_links'),
                $xoopsDB->prefix($options[0] . '_text')
            );

            if ($submitter) {
                $sql = sprintf('%s and submitter=%d', $sql, $submitter);
            }

            $sql .= ' order by l.cid,l.date ';
        } elseif (preg_match("/^weblinks\d*$/", $options[0])) {
            // in case of weblink module

            $sql = sprintf(
                'SELECT link.title AS title, link.url AS url, link.description AS dsc, cat.title AS category FROM %s AS cat, %s AS link, %s AS clink WHERE link.lid=clink.lid AND clink.cid=cat.cid ',
                $xoopsDB->prefix($options[0] . '_category'),
                $xoopsDB->prefix($options[0] . '_link'),
                $xoopsDB->prefix($options[0] . '_catlink')
            );

            if ($submitter) {
                $sql = sprintf('%s and link.uid=%d', $sql, $submitter);
            }

            $sql .= ' order by clink.cid,clink.lid ';
        }

        if (!isset($sql)) {
            return [];
        }

        $result = $xoopsDB->query($sql, $link_num, 0);

        while (false !== ($myrow = $xoopsDB->fetchArray($result))) {
            $category = $myrow['category'];

            if (!isset($block['links'][$category])) {
                $block['links'][$category] = [];
            }

            $block['links'][$category][] = [
                'title' => $myrow['title'],
'url' => $myrow['url'],
'dsc' => $myrow['dsc'],
            ];
        }

        if ($submitter) {
            $blogOwner = new XoopsUser($submitter);

            $block['lang_whose'] = sprintf(_MB_WEBLOG_LANG_LINKS_FOR, $blogOwner->getVar('uname', 'E'));
        } else {
            $block['lang_whose'] = _MB_WEBLOG_LANG_LINKS_FOR_EVERYONE;
        }

        if ($showdsc) {
            $block['showdsc'] = 1;
        }

        return $block;
    }

    /*
     * $options[0] = links module
     * $options[1] = links number
     * $options[2] = show only post or not
     * $options[3] = show link description or not
     */

    function b_weblog_links_edit($options)
    {
        global $xoopsDB, $xoopsUser;

        $mydirname = empty($options[0]) ? basename(dirname(__DIR__)) : $options[0];

        $apply_linkmodules = ['mylinks', 'weblinks'];

        $linkmods = '';

        foreach ($apply_linkmodules as $modulename) {
            $linkmods .= "dirname like '" . $modulename . "%' or ";
        }

        $mod_sql = sprintf('SELECT dirname FROM %s WHERE isactive=1 AND (%s) ', $xoopsDB->prefix('modules'), rtrim($linkmods, ' or'));

        $mod_result = $xoopsDB->query($mod_sql);

        if (0 == $xoopsDB->getRowsNum($mod_result)) {
            return false;
        }

        require_once XOOPS_ROOT_PATH . '/class/xoopsform/formelement.php';

        require_once XOOPS_ROOT_PATH . '/class/xoopsform/formselect.php';

        $selectbox = new XoopsFormSelect('', 'options[]', $options[0]);

        $selectbox->addOption('', '---');

        while (false !== ($modinfo = $xoopsDB->fetchArray($mod_result))) {
            $selectbox->addOption($modinfo['dirname']);
        }

        $link_module_selectbox = $selectbox->render();

        $form = '<table>';

        $form .= "<input type='hidden' name='options[]' value='$mydirname'>\n";

        $form .= sprintf(
            '<tr><td><b>%s</b>:</td><td>%s</td></tr>',
            _MB_WEBLOG_EDIT_LINKS_MODULE,
            $link_module_selectbox
        );

        $form .= sprintf(
            '<tr><td><b>%s</b>:</td><td><input type="text" name="options[]" value="%d" size="2" maxlength="2"></td></tr>',
            _MB_WEBLOG_EDIT_LINKS_NUMBER,
            (int)$options[1]
        );

        $form .= sprintf(
            '<tr><td><b>%s</b>:</td><td><input type="text" name="options[]" value="%d" size="2" maxlength="2" ></td></tr>',
            _MB_WEBLOG_EDIT_LINKS_ONLYPOST,
            (int)$options[2]
        );

        $form .= sprintf(
            '<tr><td><b>%s</b>:</td><td><input type="text" name="options[]" value="%d" size="2" maxlength="2" ></td></tr>',
            _MB_WEBLOG_EDIT_LINKS_SHOWDSC,
            (int)$options[3]
        );

        $form .= '</table>';

        return $form;
    }
}
