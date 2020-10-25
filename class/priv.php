<?php
/*
 * $Id: priv.php,v 1.3 2006/03/22 09:57:21 mikhail Exp $
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

if (!defined('XOOPS_ROOT_PATH')) {
    exit;
}

require_once XOOPS_ROOT_PATH . '/kernel/object.php';

if (!isset($mydirname)) {
    $mydirname = basename(dirname(__DIR__));
}

if (!class_exists('WeblogPrivBase')) {
    class WeblogPrivBase extends XoopsObject
    {
        public $mydirname = '';        // abstruct

        /**
         * Constructs an instance of this class
         */
        public function __construct()
        {
            $this->XoopsObject();

            $this->initVar('priv_id', XOBJ_DTYPE_INT, 0, false);

            $this->initVar('priv_gid', XOBJ_DTYPE_INT, 0, true);

            $this->initVar('name', XOBJ_DTYPE_TXTBOX, null, true, 100);
        }

        public function hasPermission($gid)
        {
            return ($this->getVar('priv_gid') == $gid) ? true : false;
        }
    }

    class WeblogPrivHandlerBase extends XoopsObjectHandler
    {
        public $mydirname = '';        // abstruct

        public function &create()
        {
            return new WeblogPrivBase();    // abstruct
        }

        public function get($id)
        {
            $id = (int)$id;

            if ($id > 0) {
                $sql = sprintf(
                    'SELECT p.priv_id, p.priv_gid, g.name FROM %s AS p, %s AS g WHERE p.priv_gid=%d AND p.priv_gid=g.groupid',
                    $this->db->prefix($this->mydirname . '_priv'),
                    $this->db->prefix('groups'),
                    $id
                );

                if ($result = $this->db->query($sql)) {
                    if (1 == $this->db->getRowsNum($result)) {
                        $entry = $this->create();

                        $entry->assignVars($this->db->fetchArray($result));

                        return $entry;
                    }
                }
            }

            return false;
        }

        public function insert(XoopsObject $entry)
        {
            if ('weblogprivbase' != mb_strtolower(get_parent_class($entry))) {  // must be lowercase only
                return false;
            }

            if (!$entry->isDirty()) {
                return true;
            }

            if (!$entry->cleanVars()) {
                return false;
            }

            foreach ($entry->cleanVars as $k => $v) {
                ${$k} = $v;
            }

            $count = $this->getCount(new Criteria('priv_id', $priv_id));

            if ($priv_id > 0 && $count > 0) {
                $sql = sprintf(
                    'UPDATE %s SET priv_gid=%d WHERE priv_id=%d',
                    $this->db->prefix($this->mydirname . '_priv'),
                    $priv_gid,
                    $priv_id
                );
            } else {
                $sql = sprintf(
                    'INSERT INTO %s (priv_gid) VALUES (%d)',
                    $this->db->prefix($this->mydirname . '_priv'),
                    $priv_gid
                );
            }

            $result = $this->db->queryF($sql) || die($this->db->error());

            if (!$result) {  // must be queryF()
                return false;
            }

            if (empty($priv_id)) {
                $entry->setVar('priv_id', $this->db->getInsertId());
            }

            return true;
        }

        public function delete(XoopsObject $entry)
        {
            if ('weblogprivbase' != mb_strtolower(get_parent_class($entry))) {
                return false;
            }

            $sql = sprintf(
                'DELETE FROM %s WHERE priv_id=%d LIMIT 1',
                $this->db->prefix($this->mydirname . '_priv'),
                $entry->getVar('priv_id')
            );

            if (!$result = $this->db->queryF($sql)) {  // must be queryF()
                return false;
            }

            return true;
        }

        public function getCount($criteria = null)
        {
            $sql = sprintf('SELECT count(*) AS count FROM %s', $this->db->prefix($this->mydirname . '_priv'));

            if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
                $sql .= sprintf(' %s', $criteria->renderWhere());
            }

            if (!$result = $this->db->query($sql)) {
                return 0;
            }

            $count = $this->db->fetchArray($result);

            return $count['count'];
        }

        public function &getObjects($criteria = null, $id_as_key = false)
        {
            $ret = [];

            $limit = $start = 0;

            $sql = sprintf(
                'SELECT p.priv_id, p.priv_gid, g.name FROM %s AS p, %s AS g',
                $this->db->prefix($this->mydirname . '_priv'),
                $this->db->prefix('groups')
            );

            if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
                $sql .= sprintf(' %s %s', $criteria->renderWhere(), 'AND p.priv_gid=g.groupid');

                //$groupby = trim(str_replace('GROUP BY', "", $criteria->getGroupby()));

                //$sql .= ($groupby=='')?'':sprintf(' %s', $criteria->getGroupby());

                $sort = ('' != $criteria->getSort()) ? $criteria->getSort() : 'priv_id';

                $sql .= sprintf(' ORDER BY %s %s', $sort, $criteria->getOrder());

                $limit = $criteria->getLimit();

                $start = $criteria->getStart();
            } else {
                $sql .= sprintf(' %s', 'WHERE p.priv_gid=g.groupid');
            }

            if (!$result = $this->db->query($sql, $limit, $start)) {
                return $ret;
            }

            while (false !== ($myrow = $this->db->fetchArray($result))) {
                $entry = $this->create();

                $entry->assignVars($myrow);

                if ($id_as_key) {
                    $ret[$myrow['priv_id']] = &$entry;
                } else {
                    $ret[] = &$entry;
                }

                unset($entry);
            }

            return $ret;
        }

        public function hasPrivilege($user)
        {
            $gids = &$user->getGroups();

            $criteria = new criteriaCompo();

            foreach ($gids as $gid) {
                $criteria->add(new criteria('priv_gid', $gid), 'OR');
            }

            $result = &$this->getObjects($criteria);

            if (count($result) > 0) {
                return true;
            }

            return false;
        }
    }
}

// for module duplicate
$entry_class = (string)(ucfirst($mydirname) . 'Priv');
if (!defined($entry_class)) {
    define($entry_class, 'DEFINED CLASS');

    eval(
        '
	class ' . ucfirst($GLOBALS['mydirname']) . 'Priv extends WeblogPrivBase{
		var $mydirname="' . $GLOBALS['mydirname'] . '" ;
	    function ' . ucfirst($GLOBALS['mydirname']) . 'Priv() {
				$this->WeblogPrivBase() ;
		}
	}
'
    );

    eval(
        '
	class ' . ucfirst($GLOBALS['mydirname']) . 'PrivHandler extends WeblogPrivHandlerBase{
		var $mydirname="' . $GLOBALS['mydirname'] . '" ;
	    function &create() {
	        return new ' . ucfirst($GLOBALS['mydirname']) . 'Priv();
	    }
	}
'
    );
}
?>
