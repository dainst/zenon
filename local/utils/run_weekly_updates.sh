#!/usr/bin/env bash

if [ -z "$VUFIND_HOME" ]
then
  VUFIND_HOME="/usr/local/vufind"
fi

if [ -z "$MACHINE_NAME" ]
then
    MACHINE_NAME="DevKoha"
fi

if [ -z "$KOHA_BASE_URL" ]
then
    KOHA_BASE_URL="https://koha.dainst.de/download/exports"
fi

if [ -z "$VUFIND_LOCAL_DIR" ]
then
    VUFIND_LOCAL_DIR=$VUFIND_HOME/local
fi

if [ -z "$MAILTO" ]
then
    RECIPIENT=zenondai@dainst.org
else
    RECIPIENT="$MAILTO@dainst.org"
fi

if [[ -z ${MACHINE_NAME:+x} ]] ;
then
    MACHINE_NAME="Unnamed machine"
fi

URBIS_EXPORT_LOG="$VUFIND_HOME/local/iDAI.world/log/urbis_export_`date +\%Y-\%m-\%d`.log"

python3 $VUFIND_HOME/local/utils/oai_export.py http://localhost $VUFIND_HOME/exports/dai_rom_urbis.mrc --set rom &> $URBIS_EXPORT_LOG
if grep --ignore-case -q error "$URBIS_EXPORT_LOG";
then
    cat "$URBIS_EXPORT_LOG" | mail -s "VuFind ($MACHINE_NAME) urbis export -- ERROR" -a "From: vufindmailer@dainst.de" "$RECIPIENT"
else
    cat "$URBIS_EXPORT_LOG" | mail -s "VuFind ($MACHINE_NAME) urbis export -- SUCCESS" -a "From: vufindmailer@dainst.de" "$RECIPIENT"
fi

BIBLIOPERA_EXPORT_LOG="$VUFIND_HOME/local/iDAI.world/log/bibliopera_export_`date +\%Y-\%m-\%d`.log"

python3 $VUFIND_HOME/local/utils/oai_export.py http://localhost $VUFIND_HOME/exports/dai_istanbul_bibliopera.mrc --set istanbul &> $BIBLIOPERA_EXPORT_LOG
if grep --ignore-case -q error "$BIBLIOPERA_EXPORT_LOG";
then
    cat "$BIBLIOPERA_EXPORT_LOG" | mail -s "VuFind ($MACHINE_NAME) bibliopera export -- ERROR" -a "From: vufindmailer@dainst.de" "$RECIPIENT"
else
    cat "$BIBLIOPERA_EXPORT_LOG" | mail -s "VuFind ($MACHINE_NAME) bibliopera export -- SUCCESS" -a "From: vufindmailer@dainst.de" "$RECIPIENT"
fi
