<?php

/**
 * Records Controller
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Zenon\Controller;
use VuFind\Controller\RecordsController as VuFindRecordsController;

use Laminas\Form\Element;

/**
 * Records Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Simon Hohl <simon.hohl@dainst.org>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class RecordsController extends VuFindRecordsController
{

    protected $citationFormats;

    /**
     * Cite action -- show results in available citation styles
     *
     * @return mixed
     */
    public function citeAction()
    {
        // If there is exactly one record, send the user directly there:
        $ids = $this->params()->fromQuery('id', []);
        if (count($ids) == 1) {
            $details = $this->getRecordRouter()->getTabRouteDetails($ids[0]);
            $target = $this->url()->fromRoute($details['route'], $details['params']);
            // forward print param, if necessary:
            $print = $this->params()->fromQuery('print');
            $params = empty($print) ? '' : '?print=' . urlencode($print);
            return $this->redirect()->toUrl($target . $params);
        }

        $config = $this->getConfig();
        if ($config->Record->citation_formats === false
            || $config->Record->citation_formats === 'false'
        ) {
            $this->citationFormats = [];
        } else {
            $this->citationFormats = array_map(
                'trim', explode(',', $config->Record->citation_formats)
            );
        }

        // Not exactly one record -- show search results:
        return $this->resultsAction();
    }


    /**
     * Send search results to results view
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function resultsAction()
    {
        $view = parent::resultsAction();
        $view->citationFormats = $this->citationFormats;
        return $view;
    }
}
