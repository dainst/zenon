<?php
/**
 * Holdings (ILS) tab
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
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
namespace Zenon\RecordTab;

use VuFind\ILS\Connection;
use VuFind\RecordTab\AbstractBase;

/**
 * Holdings (ILS) tab
 *
 * @category Zenon
 * @package  RecordTabs
 * @author   Sebastian Cuy <sebastian.cuy@dainst.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_tabs Wiki
 */
class Access extends AbstractBase
{
    /**
     * ILS connection (or null if not applicable)
     *
     * @param Connection
     */
    protected $catalog;

    /**
     * Name of template to use for rendering holdings.
     *
     * @param string
     */
    protected $template;

    /**
     * Constructor
     *
     * @param \VuFind\ILS\Connection|bool $catalog ILS connection to use to check
     * for holdings before displaying the tab; set to false if no check is needed
     */
    public function __construct(Connection $catalog=null)
    {
        $this->catalog = $catalog;
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Access';
    }

    /**
     * Support method used by template -- extract all unique call numbers from
     * an array of items.
     *
     * @param array $items Items to search through.
     *
     * @return array
     */
    public function getUniqueCallNumbers($items)
    {
        $callNos = [];
        foreach ($items as $item) {
            if (isset($item['callnumber']) && strlen($item['callnumber']) > 0) {
                $callNos[] = $item['callnumber'];
            }
        }
        sort($callNos);
        return array_unique($callNos);
    }

    /**
     * Is this tab active?
     *
     * @return bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Get name of template for rendering holdings.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
