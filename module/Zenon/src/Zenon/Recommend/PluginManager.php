<?php
/**
 * Recommendation module plugin manager
 *
 * PHP version 7
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
 * @category Zenon
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Zenon\Recommend;

/**
 * Recommendation module plugin manager
 *
 * @category Zenon
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Simon Hohl <simon.hohl@dainst.org>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class PluginManager extends \VuFind\Recommend\PluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        'authorityrecommend' => 'Zenon\Recommend\AuthorityRecommend'
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'Zenon\Recommend\AuthorityRecommend' => 'Zenon\Recommend\Factory::getAuthorityRecommend'
    ];
}
