<?php
// $Id: myformdatetime.php,v 1.1 2006/03/29 05:57:12 mikhail Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       < http://xoops.eti.br >                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://xoops.eti.br/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/**
 * @author        Kazumi Ono    <onokazu@xoops.org>
 * @copyright     copyright (c) 2000-2003 XOOPS.org
 */

/**
 * Date and time selection field
 *
 * @author       Kazumi Ono    <onokazu@xoops.org>
 * @copyright    copyright (c) 2000-2003 XOOPS.org
 */
// tohokuaiki change
class myXoopsFormDateTime extends XoopsFormElementTray
{
    public function __construct($caption, $name, $size = 15, $value = 0)
    {
        $this->XoopsFormElementTray($caption, '&nbsp;');

        $value = (int)$value;

        $value = ($value > 0) ? $value : time();

        $datetime = getdate($value);

        $this->addElement(new myXoopsFormTextDateSelect('', $name . '[date]', $size, $value));

        $timearray = [];

        for ($i = 0; $i < 24; $i++) {
            for ($j = 0; $j < 60; $j += 10) {
                $key = ($i * 3600) + ($j * 60);

                $timearray[$key] = (0 != $j) ? $i . ':' . $j : $i . ':0' . $j;
            }
        }

        ksort($timearray);

        $timeselect = new XoopsFormSelect('', $name . '[time]', $datetime['hours'] * 3600 + 600 * ceil($datetime['minutes'] / 10));

        $timeselect->addOptionArray($timearray);

        $this->addElement($timeselect);
    }
}
