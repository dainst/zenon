<?php

namespace Zenon\Controller;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\Query\Query;

class AuthoritySearchHelper
{
    public static function searchAuthorities($searchService, string $q, $limit = 100, $offset = 0) {
        $query = new Query(self::escapeForSolr($q));
        $command = new SearchCommand(
            'SolrAuth',
            $query,
            $offset,
            $limit
        );
        return $searchService->invoke($command)->getResult();
    }

    public static function getAuthorityId($searchService, string $system, string $id){
        $query = new Query($system . ":" . self::escapeForSolr($id));
        $command = new SearchCommand(
            'SolrAuth',
            $query
        );
        $authoritySearchResults = $searchService->invoke($command)->getResult()->first();
        if (is_null($authoritySearchResults)) {
            return null;
        }
       return $authoritySearchResults->getRawData()['id'];
    }

    public static function getBibliosForAuthorityId($searchService, $authorityId){
			  $query = new Query('authority_id_str_mv:' . self::escapeForSolr($authorityId));
        $command = new SearchCommand(
            'SolrAuth',
            $query
        );
        return $searchService->invoke($command)->getResult();
    }

     /**
     * Escape a string for inclusion in a Solr query.
     *
     * @param string $str String to escape
     *
     * @return string
     */
    public static function escapeForSolr($str)
    {
        return '"' . addcslashes($str, '"') . '"';
    }
}
