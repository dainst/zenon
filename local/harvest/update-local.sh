#!/bin/bash
#
# Bash script to start the import of MARC-XML data downloaded from the koha server
#
# VUFIND_HOME
#   Path to the vufind installation
#
# usage:
#	update-koha.sh
#
# arguments:
#	DATE in YYYY-MM-DD format sets the from argument for OAI-PMH
#

##################################################
# Set VUFIND_HOME
##################################################
if [[ -z "$VUFIND_HOME" ]]
then
  VUFIND_HOME="/usr/local/vufind"
fi

if [ -z "$HOST_URL" ]
then
  HOST_URL="https://$(hostname -i)"
fi

today=$(date +"%Y-%m-%d")

echo "Loading updated bibliographic data from $KOHA_BIBLIO_URL:"

if [[ -s "$1" ]]
then
    echo "Running VuFind's batch import scripts."
    python3 "$VUFIND_HOME"/local/utils/preprocess-marc.py "$1" "$VUFIND_HOME/local/harvest/dai-katalog/" --url "$HOST_URL"
    "$VUFIND_HOME"/harvest/batch-import-marc.sh dai-katalog | tee $VUFIND_HOME/local/harvest/dai-katalog/log/import_$today.log
    echo "Done."
else
    echo "$1 is an empty file, nothing is getting updated."
fi

if [[ -z "$KOHA_BASE_URL" ]]
then
  KOHA_AUTH_URL="https://koha.dainst.de/download/exports/$today/authority_data.mrc"
else
  KOHA_AUTH_URL="$KOHA_BASE_URL/$today/authority_data.mrc"
fi

echo "Loading updated authority data from KOHA_AUTH_URL:"

mkdir -p $VUFIND_HOME/local/harvest/dai-katalog-auth/log
wget "$KOHA_AUTH_URL" -P "$VUFIND_HOME/local/harvest/dai-katalog-auth/" --no-verbose

if [[ -s "$VUFIND_HOME/local/harvest/dai-katalog-auth/authority_data.mrc" ]]
then
    echo "Running VuFind's batch import scripts."
    "$VUFIND_HOME"/harvest/batch-import-marc-auth.sh dai-katalog-auth marc_auth.properties | tee $VUFIND_HOME/local/harvest/dai-katalog-auth/log/import_$today.log
    echo "Done."
else
    echo "$VUFIND_HOME/local/harvest/dai-katalog-auth/authority_data.mrc is an empty file, nothing is getting updated."
    rm $VUFIND_HOME/local/harvest/dai-katalog-auth/authority_data.mrc
fi
