<?php

/**
 * Custom record handling for Zenon MARC records.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Sebastian Cuy <sebastian.cuy@uni-koeln.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Zenon\RecordDriver;
use VuFind\RecordDriver\SolrAuthMarc as VufindSolrAuthMarc;

/**
 * Custom record handling for Zenon MARC records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Sebastian Cuy <sebastian.cuy@uni-koeln.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrAuthMarc extends \VuFind\RecordDriver\SolrAuthMarc
{
    /**
     * Get all fields as JSON
     * Skip raw marc data
     *
     * @return string
     */
    public function getJSON()
    {
        $data = $this->getRawData();
        unset($data['fullrecord']);
        return $data;
    }

}
