<?php
/**
 * AuthorityRecommend Recommendations Module
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2012.
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
 * @package  Recommendations
 * @author   Lutz Biedinger <vufind-tech@lists.sourceforge.net>
 * @author   Ronan McHugh <vufind-tech@lists.sourceforge.net>
 * @author   Simon Hohl <simon.hohl@dainst.org>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Zenon\Recommend;

use VuFindSearch\Backend\Exception\RequestErrorException;
use VuFind\Recommend\AuthorityRecommend as Base;
use Laminas\StdLib\Parameters;

/**
 * AuthorityRecommend Module
 *
 * This class provides recommendations based on Authority records.
 * i.e. searches for a pseudonym will provide the user with a link
 * to the official name (according to the Authority index)
 *
 * Originally developed at the National Library of Ireland by Lutz
 * Biedinger and Ronan McHugh. 
 * 
 * The Zenon variant searches authority `allfields` instead  of just `Headings`. 
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Lutz Biedinger <vufind-tech@lists.sourceforge.net>
 * @author   Ronan McHugh <vufind-tech@lists.sourceforge.net>
 * @author   Simon Hohl <simon.hohl@dainst.org>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class AuthorityRecommend extends Base
{
    protected function addUseForHeadings()
    {
        // Build an advanced search request that prevents Solr from retrieving
        // records that would already have been retrieved by a search of the biblio
        // core, i.e. it only returns results where $lookfor IS found in in the
        // "allfields" search and IS NOT found in the "MainHeading" search defined
        // in authsearchspecs.yaml.
        $params = [
            'join' => 'AND',
            'bool0' => ['AND'],
            'lookfor0' => [$this->lookfor],
            'type0' => ['allfields'], # see marc_auth.properties
            'bool1' => ['NOT'],
            'lookfor1' => [$this->lookfor],
            'type1' => ['MainHeading']
        ];

        // loop through records and assign id and headings to separate arrays defined
        // above
        foreach ($this->performSearch($params) as $result) {
            $this->recommendations[] = $result->getBreadcrumb();
        }
    }
}
