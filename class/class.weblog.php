<?php
/*
 * $Id: class.weblog.php,v 1.3 2006/03/22 09:57:21 mikhail Exp $
 * Copyright (c) 2003 by Hiro SAKAI (http://wellwine.zive.net/)
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
 *
 */

require_once sprintf('%s/class/xoopstree.php', XOOPS_ROOT_PATH);

class Weblog
{
    public $handler;

    public function __construct()
    {
        $this->handler = xoops_getModuleHandler('entry');
    }

    public function &getInstance()
    {
        static $instance;

        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    public function newInstance()
    {
        return $this->handler->create();
    }

    public function saveEntry($entry)
    {
        return $this->handler->insert($entry);
    }

    public function removeEntry($blog_id)
    {
        $entry = $this->handler->create();

        $entry->setVar('blog_id', (int)$blog_id);

        return $this->handler->delete($entry);
    }

    public function isOwner($blog_id, $user_id)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $user_id));

        $criteria->add(new criteria('blog_id', $blog_id));

        $count = $this->handler->getCount($criteria);

        if (1 == $count) {
            return true;
        }

        return false;
    }

    public function getCountByUser($currentuid, $user_id = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        if ($user_id > 0) {
            $criteria = new criteriaCompo($criteria);

            $criteria->add(new criteria('user_id', $user_id));
        }

        return $this->handler->getCount($criteria);
    }

    public function getCountByCategory($currentuid, $cat_id, $user_id = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        if ($cat_id > 0) {
            $criteria->add(new criteria('cat_id', $cat_id));
        }

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        return $this->handler->getCount($criteria);
    }

    public function getCountByDate($currentuid, $cat_id, $user_id, $date, $useroffset)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        if ($date > 0) {
            $date_num = mb_strlen((string)$date);

            $criteria->add(new criteria(sprintf('left(from_unixtime(created+%d)+0,%d)', $useroffset * 3600, $date_num), $date));
        }

        if ($cat_id > 0) {
            $criteria->add(new criteria('cat_id', $cat_id));
        }

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        return $this->handler->getCount($criteria);
    }

    public function getEntries($currentuid, $user_id = 0, $start = 0, $perPage = 0, $order = 'DESC', $useroffset = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        if ($user_id > 0) {
            $criteria = new criteriaCompo($criteria);

            $criteria->add(new criteria('user_id', $user_id));
        }

        $criteria->setSort('created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria, false, 'details', $useroffset);

        return $result;
    }

    public function getEntriesByCreated($currentuid, $from, $to, $user_id = 0, $start = 0, $perPage = 0, $order = 'DESC')
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        $criteria->add(new criteria('created', $from, '>'));

        $criteria->add(new criteria('created', $to, '<'));

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        $criteria->setSort('created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria);

        return $result;
    }

    public function getEntriesByCategory($currentuid, $cat_id = 0, $user_id = 0, $start = 0, $perPage = 0, $order = 'DESC', $useroffset = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        if ($cat_id > 0) {
            $criteria->add(new criteria('cat_id', $cat_id));
        }

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        $criteria->setSort('created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria, false, 'details', $useroffset);

        return $result;
    }

    public function getEntry($currentuid, $blog_id = 0, $user_id = 0, $useroffset = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        $criteria->add(new criteria('blog_id', $blog_id));

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        $result = &$this->handler->getObjects($criteria, true, 'details', $useroffset);

        return $result[$blog_id] ?? $result;
    }

    public function incrementReads($blog_id)
    {
        return $this->handler->incrementReads($blog_id);
    }

    public function updateComments($blog_id, $total_num)
    {
        return $this->handler->updateComments($blog_id, $total_num);
    }

    public function getPrevNext($blog_id, $created, $currentuid = 0, $isAdmin = 0, $cat_id = 0, $user_id = 0)
    {
        if (empty($isAdmin)) {
            $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

            $criteria->add(new criteria('private', 'N'), 'OR');
        } else {
            $criteria = null;
        }

        $criteria = new criteriaCompo($criteria);

        if ($cat_id > 0) {
            $criteria->add(new criteria('cat_id', $cat_id));
        }

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        return $this->handler->getPrevNextBlog_id($blog_id, $created, $criteria);
    }

    /**
     * get count of categories specified in array
     * @param mixed $currentuid
     * @param mixed $cid_array
     * @param mixed $user_id
     * @return
     * @return
     * @author hodaka <hodaka@hodaka.net>
     */
    public function getCountByCategoryArray($currentuid, $cid_array = [], $user_id = 0)
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        $criteria->add(new Criteria('cat_id', '(' . implode(',', $cid_array) . ')', 'IN'));

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        return $this->handler->getCount($criteria);
    }

    public function getEntriesForArchives($currentuid, $blogger_id, $date, $cat_id, $start = 0, $perPage = 0, $order = 'DESC', $useroffset = 0)
    {
        $date = (int)$date;

        // basic

        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        // by user_id

        if ($blogger_id > 0) {
            $criteria->add(new criteria('user_id', $blogger_id));
        }

        // by category

        if ($cat_id > 0) {
            $criteria->add(new criteria('cat_id', $cat_id));
        }

        // by date

        if ($date > 0) {
            $date_num = mb_strlen((string)$date);

            $criteria->add(new criteria(sprintf('left(from_unixtime(created+%d)+0,%d)', $useroffset * 3600, $date_num), $date));
        }

        // order , start , Limit

        $criteria->setSort('created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria, false, 'details', $useroffset);

        return $result;
    }

    /**
     * get entries of categories specified in array sorted order by created for archive.php
     * @param mixed $currentuid
     * @param mixed $cid_array
     * @param mixed $user_id
     * @param mixed $start
     * @param mixed $perPage
     * @param mixed $order
     * @return
     * @return
     * @author hodaka <hodaka@hodaka.org>
     */
    public function getLatestEntriesByCategoryArray($currentuid, $cid_array = [], $user_id = 0, $start = 0, $perPage = 0, $order = 'DESC')
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        $criteria->add(new Criteria('cat_id', '(' . implode(',', $cid_array) . ')', 'IN'));

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        $criteria->setSort('created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria);

        return $result;
    }

    /**
     * get entries of categories specified in array sorted order by cat_id, created for index.php
     * @param mixed $currentuid
     * @param mixed $cid_array
     * @param mixed $user_id
     * @param mixed $start
     * @param mixed $perPage
     * @param mixed $order
     * @return
     * @return
     * @author hodaka <hodaka@hodaka.org>
     */
    public function getEntriesByCategoryArray($currentuid, $cid_array = [], $user_id = 0, $start = 0, $perPage = 0, $order = 'DESC')
    {
        $criteria = new criteriaCompo(new criteria('user_id', $currentuid));

        $criteria->add(new criteria('private', 'N'), 'OR');

        $criteria = new criteriaCompo($criteria);

        $criteria->add(new Criteria('cat_id', '(' . implode(',', $cid_array) . ')', 'IN'));

        if ($user_id > 0) {
            $criteria->add(new criteria('user_id', $user_id));
        }

        $criteria->setSort('cat_id, created');

        $criteria->setOrder($order);

        if ($start > 0) {
            $criteria->setStart($start);
        }

        if ($perPage > 0) {
            $criteria->setLimit($perPage);
        }

        $result = &$this->handler->getObjects($criteria);

        return $result;
    }
}
