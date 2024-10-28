This directory contains the shell scripts which can be run in a cron job to
update the vufind Solr index.

`update.sh` use `harvest_oai.php` shipped with vufind.

`update-koha.sh` downloads the exported marc files from the koha download directory

Before running each of the scripts set the environment variable `VUFIND_HOME` and
`VUFIND_LOCAL_DIR`
For `update.sh` also set `HOST_URL` to the url of vufind (do not add a trailing `/`)

For `update-koha.sh` also set `KOHA_BASE_URL` to the url of the koha export directory.

## `update.sh`

`update.sh` calls `$VUFIND_HOME/harvest/harvest_oai.php` which reads the configuration
from `$VUFIND_LOCAL_DIR/harvest/oai.ini`. `oai.ini` contains a section `dai-katalog`
which is harvested. The harvested marc files are placed into
`$VUFIND_LOCAL_DIR/harvest/dai-katalog`. Those files are combined into a single
file by calling `combine-marc.py`. It also places the combined file into `$VUFIND_LOCAL_DIR/harvest/dai-katalog/preprocess`, because that is where `preprocess.py` expects it. `preprocess.py` adds missing
Marc fields and puts the result back into `$VUFIND_LOCAL_DIR/harvest/dai-katalog`.
`$VUFIND_HOME/harvest/batch-import-marc.sh` finally imports the records into Solr.

## `update-koha.sh`

This script does not do OAI-PMH harvesting, but just downloads the export MarcXML
files from `KOHA_BASE_URL`. The files are placed in `$VUFIND_LOCAL_DIR/harvest/dai-katalog/notpreprocessed`
where `preprocess.py` picks them up and stores the result into `$VUFIND_LOCAL_DIR/harvest/dai-katalog`.
`$VUFIND_HOME/harvest/batch-import-marc.sh` finally imports the records into Solr.

## `update-local.sh`

This scripts doesn't even download the MarcXML files. It reads from a local directory.
