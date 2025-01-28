This directory contains all files which need to be added to a vanilla vufind 10
distribution to make it work for DAI. The layout in this directory is identical
to the directory layout used by vufind, but includes only those files and directories
which must be added or modified.

Whenever possible, the vufind sources
weren't changed. Only the core schema of biblio and authority are an exception
of this rule. They both replace the existing `schema.xml`

Just copy (or create symbolic links) the following directories and files
into a fresh vufind 10 installation

```
import/index_java/src/org/dainst
module/Zenon
themes/archaeostrap
local/config
local/import
local/languages
local/utils
local/harvest
local/iDAI.world
solr/vufind/biblio/conf/schema.xml
solr/vufind/authority/conf/schema.xml
```

The `Makefile` in this directory can also be placed into a vufind installation
after adding the above directories have been added. Call

```
make dist

```

to copy a complete vufind installation into a tar archive, after removing some log files
and the cache.
