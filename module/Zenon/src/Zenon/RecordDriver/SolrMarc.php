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
use VuFindCode\ISBN;
use VuFindSearch\Command\RetrieveCommand;


/**
 * Custom record handling for Zenon MARC records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Sebastian Cuy <sebastian.cuy@uni-koeln.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{

    const COVERS_DIR = "/usr/local/vufind/local/cache/covers";

    use \VuFind\RecordDriver\Feature\MarcReaderTrait;
    use \VuFind\RecordDriver\Feature\MarcAdvancedTrait {
        \VuFind\RecordDriver\Feature\MarcAdvancedTrait::getURLs as getMarcURLs;
    }

    /**
     * Zenon configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $zenonConfig;
    public function attachZenonConfig($zenonConfig)
    {
        $this->zenonConfig = $zenonConfig;
    }

    /**
     * Get the title of the record.
     * Overridden to adapt GBV solr schema divergency and to remove trailing slashes.
     *
     * @return string
     */
    public function getTitle()
    {
        $title = parent::getTitle();
        if(is_array($title)) {
            $title = $title[0];
        }
        return $this->removeTrailingSlash($title);
    }

    /**
     * Get the short (pre-subtitle) title of the record.
     * Overridden to adapt GBV solr schema divergency and to remove trailing slashes.
     *
     * @return string
     */
    public function getShortTitle()
    {
        $shortTitle = parent::getShortTitle();
        if(is_array($shortTitle)) {
            $shortTitle = $shortTitle[0];
        }
        return $this->removeTrailingSlash($shortTitle);
    }

    /**
     * Get a highlighted title string, if available.
     * Overridden to adapt GBV solr schema divergency and to remove trailing slashes.
     *
     * @return string
     */
    public function getHighlightedTitle()
    {
        $highlightedTitle = parent::getHighlightedTitle();
        if(is_array($highlightedTitle)) {
            $highlightedTitle = $highlightedTitle[0];
        }
        return $this->removeTrailingSlash($highlightedTitle);
    }

    /**
     * Get the text of the part/section portion of the title.
     * Overridden to adapt GBV solr schema divergency and to remove trailing slashes.
     *
     * @return string
     */
    public function getTitleSection()
    {
        $titleSection = parent::getTitleSection();
        if(is_array($titleSection)) {
            $titleSection = $titleSection[0];
        }
        return $this->removeTrailingSlash($titleSection);
    }

    /**
     * Get the subtitle of the record.
     * Overridden to adapt GBV solr schema divergency and to remove trailing slashes.
     *
     * @return string
     */
    public function getSubtitle()
    {
        $subTitle = parent::getSubtitle();
        if(is_array($subTitle)) {
            $subTitle = $subTitle[0];
        }
        return $this->removeTrailingSlash($subTitle);
    }

	/**
     * Get the thesaurus entries of the record
     *
     * @return array
     */
    public function getThsEntries()
    {

    	$result = array();
    	$fields = $this->getMarcReader()->getFields('999');

    	foreach ($fields as $currentField) {

    		$entry = [];

    		// ND201132015 = Non-descriptor flag
            $ignore = $this->getSubfieldArray($currentField, ['g'], false);
            if (!empty($ignore) && $ignore[0] == 'ND201132015') continue;

            $label = $this->getSubfieldArray($currentField, ['a','r','m','e'], true);

            if (count($label) > 0) $entry['label'] = $label[0];
            else continue;

            $language = $this->getSubfieldArray($currentField, ['9']);
            if (count($language) > 0) $entry['language'] = $language[0];
            else $entry['language'] = 'ger';

            $notation = $this->getSubfieldArray($currentField, ['1']);
            if (count($notation) > 0) $entry['notation'] = $notation[0];
            else continue;

            // return $m as additional search term for Gazetteer
            $searchterm = $this->getSubfieldArray($currentField, ['m']);
            if (!empty($searchterm)) $entry['searchterm'] = $searchterm[0];

            // return $r as additional search term for Gazetteer
            $searchterm2 = $this->getSubfieldArray($currentField, ['r']);
            if (!empty($searchterm2)) $entry['searchterm2'] = $searchterm2[0];

            // yes, ugly.
            $specialLabel = $this->getSubfieldArray($currentField, ['r']);
            if(!empty($specialLabel))
                $entry['specialLabel'] = $specialLabel[0];

            // TODO: multi language support, until then only show german entries
            if ($entry['language'] == 'ger') $result[] = $entry;

        }

        // only return distinct values
        return array_map('unserialize', array_unique(array_map('serialize', $result)));

    }

    /**
     * Returns one of three things: a full URL to a thumbnail preview of the record
     * if an image is available in an external system; an array of parameters to
     * send to VuFind's internal cover generator if no fixed URL exists; or false
     * if no thumbnail can be generated.
     *
     * Overriden to be able to test if thumbs are available in cache directory.
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        $arr = parent::getThumbnail($size);
        if (!array_key_exists('isbn', $arr)) return false;
        $isbnObj = new ISBN($arr['isbn']);
        $isbn10 = $isbnObj->get10();
        $isbn13 = $isbnObj->get13();

        if ( file_exists(self::COVERS_DIR . '/medium/' . $isbn10 . '.jpg')
            || file_exists(self::COVERS_DIR . '/medium/' . $isbn13 . '.jpg') )
            return $arr;
        else
            return false;
    }

    /**
     * Get the location (MARC 21 field 852)
     *
     * @return array
     */
    public function getLocation()
    {
    	return $this->getFieldArray('852', array('a','b','e','h'));
    }

    /**
     * Get the basic bibliographic unit (MARC 21 field 866)
     *
     * @return array
     */
    public function getBasicBibliographicUnit()
    {
        return $this->getFieldArray('866');
    }


    /**
     * Get an array of newer based on subfield $w, in contrast to VuFind's standard behaviour of using a title search.
     *
     * @return array
     */
    public function getNewerTitles()
    {
        return $this->createLinkingEntries($this->getMarcReader()->getFields('785'));
    }

    /**
     * Get an array of previus titles based on subfield $w, in contrast to VuFind's standard behaviour of using a title search.
     *
     * @return array
     */
    public function getPreviousTitles()
    {
        return $this->createLinkingEntries($this->getMarcReader()->getFields('780'));
    }

    /**
     * Get the host item information (MARC 21 field 773).
     *
     * @return object
     */
    public function getHostItemInformation()
    {
        $result =  $this->createLinkingEntries($this->getMarcReader()->getFields('773'));
        // There should only be one host item.
        if(count($result) > 0)
        {
            return $result[0];
        }
    }

    /**
     * Get the supplement host item information (MARC 21 field 772).
     *
     * @return array
     */
    public function getSupplementedItemsInformation()
    {
        return $this->createLinkingEntries($this->getMarcReader()->getFields('772'));
    }

    /**
     * Get the records (Koha) biblio number
     * 
     * @return string 
     */
    public function getBiblioNumber()
    {
        return $this->getFirstFieldValue('999', ['c']);
    }

    /**
     * Get supplement entries.
     * @return array
     */
    public function getSupplementEntries(){
        return $this->createLinkingEntries($this->getMarcReader()->getFields('770'));
    }

    /**
     * Get the host item information (MARC 21 field 787).
     *
     * @return array
     */
    public function getOtherRelationships(){
        return $this->createLinkingEntries($this->getMarcReader()->getFields('787'));
    }

    private function createLinkingEntries($fields)
    {
        $result = [];
        foreach($fields as $currentField) {
            $result[] = array(
                'id' => $this->getLinkedEntryID($currentField), 
                'label' => $this->getLinkedEntryLabel($currentField)
            );
        }
        return $result;
    }

    private function getLinkedEntryID($field) {
        $subfield = $this->getSubfield($field, 'w');
        $recordControlNumber = null;
        if($subfield) {
            $subfieldData = $subfield;

            preg_match("/^\d{9}$/", $subfieldData, $matches);
            if (sizeof($matches) == 1){
                $recordControlNumber = $subfieldData;
            }

            # Handle link that contains our organization code
            preg_match("/^\(DE-2553\)(\d{9})$/", $subfieldData, $matches);
            if (sizeof($matches) == 2) {
                $recordControlNumber = $matches[1];
            }
        }
        return $recordControlNumber;
    }

    private function getLinkedEntryLabel($field) {
        $combinedGeneralSubfields = $this->getSubfieldArray($field, ['a', 'b', 't', 'g', 'n', 'x', 't'], true, ', ');
        $relationshipInformation = $this->getSubfield($field, 'i');
        $issn = $this->getSubfield($field, 'z');

        $label = null;

        if(sizeOf($combinedGeneralSubfields) == 1) {
            $label = $combinedGeneralSubfields[0];
            if($relationshipInformation) {
                $label = $label . " (" . $relationshipInformation . ")";
            }
        } else if($relationshipInformation) {
            $label = $relationshipInformation;
        }

        if($issn){
            if($label){
                $label = $label . " | ";
            }
            $label = $label . "ISSN: " . $issn; 
        }

        return $label;
    }

    /**
     * Get the item's publication information, if no date is found in 260c/264c, get manufacturing date out of 260g/264g
     * if there is also no date information found in 260g/264g, parse control field 008 for a date match
     *
     * @param string $subfield The subfield to retrieve ('a' = location, 'c' = publish date)
     *
     * @return array
     */
    protected function getPublicationInfo($subfield = 'a')
    {
        $results = parent::getPublicationInfo($subfield);

        if (empty($results) && $subfield == 'c') {
            $results = parent::getPublicationInfo('g');

            if (empty($results)) {
                $generalInformation = $this->getMarcReader()->getField('008');

                if ($generalInformation) {
                    preg_match('/^.{7}(\d{4}).*$/', $generalInformation, $match);
                    if ($match && sizeof($match) == 2) {
                        array_push($results, $match[1]);
                    }
                }
            }
        }

        return $results;
    }

    public function getOrcId($author) {
        if(!array_key_exists('name_and_authid_combined_str_mv', $this->fields)) return '';

        $combinedFields = $this->fields['name_and_authid_combined_str_mv'];

        foreach ($combinedFields as $combinedField ) {
            // See marc_local.properties for 'name_and_authid_combined_str_mv'
            $splitField = preg_split('/###orc###/', $combinedField);

            if(count($splitField) != 2) continue;

            $part1 = $splitField[0];
            $part2 = $splitField[1];

            // Handle $a before $9 in original marc data
            if (stripos($author, $part1) !== false) {
                return $this->getOrcIdFromAuthority($part2); 
            }

            // Handle $9 before $a in original marc data
            if (stripos($author, $part2) !== false) {
                return $this->getOrcIdFromAuthority($part1);
            }
        }

        return '';
    }


    private function getOrcIdFromAuthority($authorityId) {

        //$authoritySearchResults = $this->searchService->retrieve('SolrAuth', $authorityId);
        $command = new RetrieveCommand(
            'SolrAuth',
            $authorityId
        );
        $authoritySearchResults = $this->searchService->invoke(
            $command
        )->getResult();

        if($authoritySearchResults->count() == 0){
            return '';
        };

        $authorityRecord = $authoritySearchResults->first()->getRawData();

        if(!array_key_exists('orc_id_str', $authorityRecord)) {
            return '';
        }

        return $authorityRecord['orc_id_str'];
    }

    public function getPrimaryAuthorsNames()
    {
        /**
         * Get the main authors' names of the record.
         *
         * @return array
         */
        $primary = $this->getFirstFieldValue('100', ['a']);
        return empty($primary) ? [] : [$primary];
    }

    public function getSecondaryAuthorsNames()
    {
        /**
         * Get the secondary authors' names of the record.
         *
         * @return array
         */

        $secondary = [];
        $secondaryFields = $this->getMarcReader()->getFields('700');
        foreach($secondaryFields as $field) {
            if ($field->getSubfield('a')) {
                array_push($secondary, $field->getSubfield('a')->getData());
            }
        }

        return $secondary;
    }

    public function getCorporateAuthorsNames()
    {
        /**
         * Get the corporate authors' names of the record.
         *
         * @return array
         */
        $corporate = $this->getFirstFieldValue('110', ['a']);
        return empty($corporate) ? [] : [$corporate];
    }

    /**
     * Get parallel records for the record (different editions etc.)
     *
     * @return array
     */
    public function getSeeAlso()
    {
        $linkType = 'UP';

        return $this->createCustomFieldLinkArray($linkType);
	}

    /**
     * Get parallel records for the record (different editions etc.)
     *
     * @return array
     */
    public function getParallelEditions()
    {
        return $this->createLinkingEntries($this->getMarcReader()->getFields('776'));
    }


    /**
     * Get links to iDAI.gazetteer
     *
     * @return array
     */
    public function getGazetteerLinks()
    {
        $result = array();
        $locationFields = $this->getMarcReader()->getFields('651');

        $encounteredGazIds = [];

        foreach($locationFields as $locationField) {
            if(!$this->getSubfield($locationField, '9') || !$this->getSubfield($locationField, 'a')) continue;

 //           $authorityID = $locationField->getSubfield('9')->getData();
            $authorityID = $this->getSubfield($locationField, '9');
//            $label = $locationField->getSubfield('a')->getData();
            $label = $this->getSubfield($locationField, 'a');

            //$authoritySearchResults = $this->searchService->retrieve('SolrAuth', $authorityID);
            $command = new RetrieveCommand(
                'SolrAuth',
//                $this->getSourceIdentifier(),
                $authorityID
            );
            $authoritySearchResults = $this->searchService->invoke(
                $command
            )->getResult();
//            var_dump($driver);exit;

            if($authoritySearchResults->count() == 0) continue;
            $authorityRecord = $authoritySearchResults->first()->getRawData();
            $gazId = $authorityRecord['iDAI_gazetteer_id'];

            if(empty($gazId)) continue;
            if(in_array($gazId, $encounteredGazIds)) continue;

            $result[] = array(
                'label' => $label,
                'uri' => 'https://gazetteer.dainst.org/place/' . $gazId
            );

            $encounteredGazIds[] = $gazId;
        }
        return $result;
    }

    /**
     * Get links to iDAI.thesauri
     *
     * @return array
     */
    public function getThesauriLinks()
    {
        $result = array();
        $topicFields = $this->getMarcReader()->getFields('650');

        $encounteredThesauriIds = [];
        foreach($topicFields as $topicField) {
            if(!$this->getSubfield($topicField, '9') || !$this->getSubfield($topicField, 'a')) continue;

 //           $authorityID = $topicField->getSubfield('9')->getData();
            $authorityID = $this->getSubfield($topicField, '9');
//            $label = $topicField->getSubfield('a')->getData();
            $label = $this->getSubfield($topicField, 'a');

            //$authoritySearchResults = $this->searchService->retrieve('SolrAuth', $authorityID);
            $command = new RetrieveCommand(
                'SolrAuth',
//                $this->getSourceIdentifier(),
                $authorityID
            );
            $authoritySearchResults = $this->searchService->invoke(
                $command
            )->getResult();
            if($authoritySearchResults->count() == 0) continue;
            $authorityRecord = $authoritySearchResults->first()->getRawData();
            $thesauriId = $authorityRecord['iDAI_thesauri_id'];

            if(empty($thesauriId)) continue;
            if(in_array($thesauriId, $encounteredThesauriIds)) continue;

            $result[] = array(
                'label' => $label,
                'uri' => 'http://thesauri.dainst.org/' . $thesauriId
            );

            $encounteredGazIds[] = $thesauriId;
        }
        return $result;
    }

    public function getDAILinks()
    {
        $gazetteer = $this->getGazetteerLinks();
        $thesauri = $this->getThesauriLinks();

        return array("gazetteer" => $gazetteer, "thesauri" => $thesauri);
    }

    /**
     * Get Marc control number.
     */
    public function getControlNumber() {
        if(!$this->getMarcReader()->getField('001'))//->toRaw())
            return null;

        return trim($this->getMarcReader()->getField('001'));//->toRaw(), "\x00..\x1F");
    }

    /**
     * Get Link (if exists) to iDAI.publications.
     */
    public function getPublicationsLink() {

        $result = array();

        $content_serials = null;
        $serials_path = '../local/iDAI.world/publications_serials_mapping.json';
        if(file_exists($serials_path))
            $content_serials = file_get_contents($serials_path);
        
        if($content_serials != null){
            $data = json_decode($content_serials, true);
            if(is_null($data))
                trigger_error("$serials_path malformed", E_USER_WARNING);
            else if (isset($data['publications'])) {
                $controlNumber = $this->getControlNumber();
                if (array_key_exists($controlNumber, $data['publications']))
                    array_push($result, $data['publications'][$controlNumber]);
            } else {
                trigger_error("Missing field key publications in $serials_path", E_USER_WARNING);
            }
        }

        $content_books = null;
        $books_path = '../local/iDAI.world/publications_books_mapping.json';
        if(file_exists($books_path))
            $content_books = file_get_contents($books_path);

        if($content_books != null){
            $data = json_decode($content_books, true);

            if(is_null($data))
                trigger_error("$books_path malformed", E_USER_WARNING);
            else if(isset($data['publications'])){
                $controlNumber = $this->getControlNumber();
                if (array_key_exists($controlNumber, $data['publications']))
                    array_push($result, $data['publications'][$controlNumber]);
            } else {
                trigger_error("Missing field key publications in $books_path", E_USER_WARNING);
            }
        }

        return $result;
    }

    /**
     * Get Link (if exists) to CHRE.
     */
    public function getCHRELink() {
        $fileContent = file('../local/iDAI.world/chre_mapping.csv');
        if($fileContent == null){
            return false;
        }

        $csvData = array_map('str_getcsv', $fileContent);
        $controlNumber = $this->getControlNumber();
        foreach ($csvData as $csvLines => $csvLine) {
            if ($csvLine[0] == $controlNumber) {
                return "http://chre.ashmus.ox.ac.uk/reference/" . $csvLine[1];
            }
        }

        return false;
    }

    /**
     * Get Varying Form of Title (MARC field 246)
     *
     * @return array
     */
    public function getVaryingFormOfTitles()
    {
        return $this->getFieldArray('246', ['a', 'b', 'i']);
    }

    /**
     * Get additional Title (MARC field 740)
     *
     * @return array
     */
    public function getAdditionalTitles()
    {
        return $this->getFieldArray('740');
    }

    /**
    * Get additional Information (MARC fields 540, 546 & 561)
     *
     * @return array
    */
    public function getAdditionalInformation()
    {
        $fields = ['546', '561'];
        $result = [];
        foreach ($fields as $field) {
            $value = $this->getFieldArray($field);
            if (!empty($value)) $result[] = $value;
        }
        return $result;
    }

    /**
     * Get an array of all series names containing the record.  Array entries may
     * be either the name string, or an associative array with 'name' and 'number'
     * keys.
     *
     * SD-1749: Added 830$n to name.
     * @return array
     */
    public function getSeries()
    {
        $matches = [];

        // First check the 440, 800 and 830 fields for series information:
        $primaryFields = [
            '440' => ['a', 'p'],
            '800' => ['a', 'b', 'c', 'd', 'f', 'p', 'q', 't'],
            '830' => ['a', 'p', 'n']
        ];
        $matches = $this->getSeriesFromMARC($primaryFields);
        if (!empty($matches)) {
            return $matches;
        }

        // Now check 490 and display it only if 440/800/830 were empty:
        $secondaryFields = ['490' => ['a']];
        $matches = $this->getSeriesFromMARC($secondaryFields);
        if (!empty($matches)) {
            return $matches;
        }

        // Still no results found?  Resort to the Solr-based method just in case!
        return parent::getSeries();
    }

     /**
     * Get Additional Physical Form available Note (MARC field 530)
      *
      * @return array
     */
    public function getAdditionalPhysicalFormAvailableNote()
    {
        $result = array();
        $fields = $this->getMarcReader()->getFields('530');

        foreach ($fields as $currentField) {
            $subfieldaarray = $this->getSubfieldArray($currentField, ['a'], false);
            $subfielduarray = $this->getSubfieldArray($currentField, ['u'], false);
            $field = $this->getSubfieldArray($currentField, ['a', 'u'], false);
            if ((count($subfieldaarray) > 0) and (count($subfielduarray) > 0)) {
                $result[] = array(
                    'label' => $subfieldaarray[0],
                    'uri' => $subfielduarray[0]
                );
            }
        }
        return $result;
    }

    /**
     * Return the ID of this record's parent
     * @return string
     */
    public function getHierarchyParentId() {
        return empty($this->fields['hierarchy_parent_id']) ? '' : $this->fields['hierarchy_parent_id'][0];
    }

    /**
     * Try parsing the page range for an article from the physical description filed (300a).
     *
     * @return string
     */
    public function getPageRangeFromPhysicalDescription() {

        $value = $this->getFieldArray('300',['a']);
        if(!empty($value)){
            preg_match('/((\d+–\d+)|(\d+-\d+))/', $value[0], $matches);
            if(!empty($matches)){
                return $matches[1];
            }
            return '';
        } else {
            return '';
        }
    }


    /**
    * Get Terms Governing Use and Reproduction Note (MARC field 540)
     *
     * @return array
    */
    public function getUsageTerms()
    {
        return $this->getFieldArray('540');
    }

    /**
    * Get Copyright Status (MARC field 542)
     *
     * @return array
    */
    public function getCopyrightStatus()
    {
        return $this->getFieldArray('542',['d']);
    }

    /**
     * Checks wether this record is a dfg national licenced book or not
     * 
     * @return bool
     */
    public function isNationalLicenced()
    {
        $fields = $this->getFieldArray('590',['a']);
        if (!empty($fields)) {
            $filtered = array_filter($fields, function($val) {
                return preg_match('#^(dbnl|ejournalnl|nlpaket|ebooknl)#', $val) === 1;
            });
            return !empty($filtered);
        }
        return false;
    }

    public function getPartOrSectionInfo()
    {
        return $this->getFirstFieldValue('245', ['n']);
    }

    private function removeTrailingSlash($s)
    {
        if (strrpos($s, '/') == strlen($s)-1) {
            return substr($s, 0, strrpos($s, '/'));
        } else {
            return $s;
        }
    }

    /**
     * Creates an array of link information from custom field 995 and subfields 'a', 'b', and 'n'
     *
     * @param $linkType
     * @return array
     */
    private function createCustomFieldLinkArray($linkType)
    {
        $result = [];
        $fields = $this->getMarcReader()->getFields('995');

        foreach ($fields as $currentField) {
            $currentLinkType = $currentField->getSubfield('a')->getData();

            if($linkType == $currentLinkType) {

                $zenonId = $this->zenonConfig->Records->localRecordPrefix . $currentField->getSubfield('b')->getData();
                $label = $currentField->getSubfield('n')->getData();
                $link = [
                    'id' => $zenonId,
                    'label' => $label,
                ];
                array_push($result, $link);
            }
        }

        return $result;
    }

    /**
     * Get an array of strings representing citation formats supported
     * by this record's data (empty if none).  For possible legal values,
     * see /application/themes/root/helpers/Citation.php, getCitation()
     * method.
     *
     * @return array Strings representing citation formats.
     */
    protected function getSupportedCitationFormats()
    {
        return ['DAI', 'APA', 'Chicago', 'MLA'];
    }
}

?>
