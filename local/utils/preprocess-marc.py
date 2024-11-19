#! /usr/bin/env python3
# -*- coding: utf-8 -*-
#

import pymarc
import argparse
import logging
import os
import urllib
import re
import json
import ssl

logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
sh = logging.StreamHandler()
sh.setFormatter(formatter)
logger.addHandler(sh)

# This script is custom written for Zenon:
# * Strip internal notes from holding data (952).
# * Strip summary field (520) because of copyright concerns.
# * Automatically add helper holding fields (953) for holding data (952) in parent records.
# * In the process of generating 953 there are some sanity checks on links between records. Irregularities get reported.
# * In our initial Koha setup 001 fields were not generated automatically but with a click in Koha's cataloguing interface. This required some sanity checks:
# ** Is a 001 present? If false the record gets rejected. A librarian has to click a new 001.
# ** Is 001 valid (meaning: 9 digits)? If false the record gets rejected. Could happen with external records imported into Koha without generating a new 001.
# ** Is a record with the same biblionumber (Koha's internal ID) present in VuFind, but with a different 001? If true the record gets rejected.
# * Since early 2022 we changed the 001 generation process: 
# ** New records have a biblionumber >= 3000000 and will always have biblionumber == 001 automatically.
# ** Changes to 001 by users have been disabled in Koha's interface.
# ** This makes 001 the sanity checks obsolete but their assumptions are still valid, so they were kept in regardless.


def is_writable_directory(path: str):
    if os.path.exists(path) and (not os.path.isdir(path) or not os.access(path, os.W_OK)):
        msg = "Please provide writable directory."
        raise argparse.ArgumentTypeError(msg)
    elif not os.path.exists(path):
        os.makedirs(path)
        return path
    else:
        return path

MARCXML_OPENING_ELEMENTS = bytes(
    '<?xml version="1.0" encoding="UTF-8" ?><collection xmlns="http://www.loc.gov/MARC21/slim" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd">',
    'utf-8'
)

MARCXML_CLOSING_ELEMENTS = bytes(
    '</collection>', 'utf-8'
)

parser = argparse.ArgumentParser(description='Preprocess MARCXML data to be imported into Vufind.')
parser.add_argument('input_file', type=str, help="The MARCXML file to be processed.")
parser.add_argument('output_directory', type=is_writable_directory, help="Output directory for the updated MARC file.")
parser.add_argument('--url', dest='server_url', type=str, default="https://zenon.dainst.org", help="Optional server URL for creating additional holding information.")
parser.add_argument('--check_biblio', dest='check_biblio', action='store_true', help="Check if datasets with given biblionumber already exist on server. If the system number differs, the new record is logged as an error." )
holdings_mapping = {}
invalid_zenon_ids = []

valid_zenon_id = re.compile(r"\d{9}")
contains_only_numbers = re.compile(r"\d+")

def is_record_valid(record):
    global server_url
    global check_biblio_no

    if not '001' in record:
        return (False, "No system number 001 in https://koha.dainst.de:8443/cgi-bin/koha/catalogue/detail.pl?biblionumber={0}. Returning None record.".format(record['999']['c']))

    sys_number = record['001'].data

    matcher = re.fullmatch(valid_zenon_id, sys_number)
    if not matcher:
        return (False, "Unusual system number 001 {1} in https://koha.dainst.de:8443/cgi-bin/koha/catalogue/detail.pl?biblionumber={0}.".format(record['999']['c'], sys_number))

    if check_biblio_no:
        url =  "{0}/api/v1/search?lookfor=biblio_no:{1}&type=AllFields".format(server_url, record['999']['c'])
        #logger.info(url)
        req = urllib.request.Request(url)

        try:
            with urllib.request.urlopen(req) as response:
                result = json.loads(response.read().decode("utf-8"))
                if "records" in result:
                    records = result["records"]
                    if len(records) > 1:
                        return (False, "There are multiple records with biblio number {0}, see {1}. https://koha.dainst.de:8443/cgi-bin/koha/catalogue/detail.pl?biblionumber={0}".format(record['999']['c'], url))
                    if records[0]['id'] != sys_number:
                        return (False, "There is already a record with biblio number {0}, but the system number differs: {1} (old) : {2} (new). https://koha.dainst.de:8443/cgi-bin/koha/catalogue/detail.pl?biblionumber={0}".format(record['999']['c'], records[0]['id'], sys_number))
        except Exception as e:
            logger.error(e)
            return (False, "Failed to load {0}.".format(url))

        url = "{0}/api/v1/search?lookfor=id:{1}&type=AllFields&field[]=biblioNumber".format(server_url, sys_number)
        #logger.info(url)
        req = urllib.request.Request(url)

        try:
            with urllib.request.urlopen(req) as response:
                result = json.loads(response.read().decode("utf-8"))

                if "records" in result and len(result["records"]) != 0 and result["records"][0]['biblioNumber'] != record['999']['c']:
                    return (False, "There is already a record with system number {0}, but the new biblio number differs: {1} (old) : {2} (new). https://koha.dainst.de:8443/cgi-bin/koha/catalogue/detail.pl?biblionumber={2}".format(sys_number, result["records"][0]['biblioNumber'], record['999']['c']))
        except Exception as e:
            logger.error(e)
            return (False, "Failed to load {0}.".format(url))

    return (True, "")

def extract_parent_ids(sys_number, parents):
    global invalid_zenon_ids
    parent_ids = []
    for parent in parents:
        if 'w' not in parent:
            continue
        parent_sys_number = parent['w']
        matcher = re.fullmatch(valid_zenon_id, parent_sys_number)
        if not matcher:

            fixed_value = None

            number_match = re.fullmatch(contains_only_numbers, parent_sys_number)
            if number_match:
                if(len(parent_sys_number) > 9):
                    parent['w'] = parent_sys_number[(len(parent_sys_number)-9):]
                else:
                    pad = '0' * (9 - len(parent_sys_number))
                    parent['w'] = pad + parent_sys_number
                parent_ids += [parent['w']]

                fixed_value = parent['w']

            invalid_zenon_ids += [(sys_number, parent_sys_number, fixed_value)]

        else:
            parent_ids += [parent['w']]

    return list(set(parent_ids))


def extract_holding_branch_codes(holding_fields):
    holding_branches = []
    for holding in holding_fields:
        holding_branches.append(holding.get('b'))
    return holding_branches


def accumulate_ancestor_holdings(sys_number_first, ids, current_depths = 0):
    global server_url
    global holdings_mapping

    if current_depths > 10:
        logger.error("Unusually deeply nested hierarchy for {0}. Aborting recursion.".format(ids))
        return []

    parent_ids = []
    holding_branches = []
    for id in ids:
        if id in holdings_mapping:
            (parent_ids, holding_branches) = holdings_mapping[id]
        else:
            url = "{1}/Record/{0}/Export?style=MARCXML".format(id, server_url)
            logger.info(url)
            req = urllib.request.Request(url)
            try:
                with urllib.request.urlopen(req) as response:
                    record = pymarc.parse_xml_to_array(response)[0]
                    holdings = record.get_fields('952')
                    holding_branches = extract_holding_branch_codes(holdings)
                    parents = record.get_fields('773')
                    parent_ids = extract_parent_ids(id, parents)

                    holdings_mapping[id] = (parent_ids, holding_branches)
            except urllib.error.HTTPError as e:
                logger.error("{1}, {3}/Record/{0}, initial record: {3}/Record/{2}.".format(id, e, sys_number_first, server_url))
            except Exception as e:
                logger.error("{1}, {3}/Record/{0}, initial record: {3}/Record/{2}.".format(id, e, sys_number_first, server_url))

    if parent_ids:
        return list(set(holding_branches + accumulate_ancestor_holdings(sys_number_first, parent_ids, current_depths=current_depths+1)))
    return holding_branches


def add_to_holding_mapping(record):
    global holdings_mapping

    (valid, _message ) = is_record_valid(record)
    if not valid:
        logger.error(_message)
        return

    sys_number = record['001'].data.strip()

    holdings = record.get_fields('952')
    holding_branches = extract_holding_branch_codes(holdings)

    parent_ids = []
    parents = record.get_fields('773')
    parent_ids = extract_parent_ids(sys_number, parents)

    holdings_mapping[sys_number] = (parent_ids, holding_branches)

def preprocess_record(record):
    global holdings_mapping

    (valid, message) = is_record_valid(record)
    if not valid:
        logger.error(message)
        return None

    sys_number = record['001'].data

    (parent_ids, holding_branches) = holdings_mapping[sys_number]
    ancestor_holding_branches = accumulate_ancestor_holdings(sys_number, parent_ids)
    ancestor_holding_branches = [x for x in ancestor_holding_branches if x not in holding_branches]

    holdings = record.get_fields('952')
    internal_subfields = ['d', 'e', 'f', 'g', 'w', 'x', 'A', 'C', 'P', 'T', 'U']
    for holding in holdings:
        for subfield in internal_subfields:
            holding.delete_subfield(subfield)
        '''
        keys = holding.subfields[0::2]
        vals = holding.subfields[1::2]
        subs = dict()
        for key, val in zip(keys, vals):
            if key in subs:
                subs[key].append(val)
            else:
                subs[key] = [val]

        for key, values in subs.items():
            values = list(filter(lambda val: val.strip() != '', values))
            if len(values) == 0:
                # print(f"Deleting subfield {key}.")
                # print(holding)
                # It seems VuFind can not handle empty elements like `<subfield code="D" />` correctly, which will result in broken XML data.
                holding.delete_subfield(key)
        '''

    # Remove summaries, see SD-1798
    for field in record.get_fields('520'):
        record.remove_field(field)

    if ancestor_holding_branches:
        branches_added = []
        for branch in ancestor_holding_branches:
            if branch in branches_added:
                continue

            record.add_field(
                pymarc.Field(
                    tag='953',
                    indicators=[' ', ' '],
                    subfields=[
                        pymarc.Subfield(code='b', value=branch),
                        pymarc.Subfield(code='z', value="Automatically added holding branch key.")
                    ]
                )
            )

            branches_added.append(branch)

    return record

def run(file_paths, output_directory):
    global invalid_zenon_ids
    
    logger.info("Creating holding mappings.")
    for file_path in file_paths:
        with open(file_path, 'rb') as input_file:
            logger.info(file_path)
            reader = pymarc.parse_xml_to_array(input_file)

            for record in reader:
                add_to_holding_mapping(record)
    
    logger.info("Preprocessing files.")
    for file_path in file_paths:
        with open(file_path, 'rb') as input_file, open("{0}/{1}".format(output_directory, os.path.basename(file_path)), 'wb') as output_file:
            logger.info(file_path)
            reader = pymarc.parse_xml_to_array(input_file)

            output_file.write(MARCXML_OPENING_ELEMENTS)

            for record in reader:
                record = preprocess_record(record)
                if record is None:
                    logger.error("Received None record after processing, skipping.")
                else:
                    output_file.write(pymarc.record_to_xml(record))

            output_file.write(MARCXML_CLOSING_ELEMENTS)

    logger.warning("Encountered {0} invalid zenon IDs:".format(len(invalid_zenon_ids)))
    invalid_zenon_ids = list(set(invalid_zenon_ids))
    for entry in invalid_zenon_ids:
        logger.warning("{0} contained {1} as parent, fixed: {2}.".format(entry[0], entry[1], entry[2]))

if __name__ == '__main__':
    global server_url
    global check_biblio_no

    options = vars(parser.parse_args())

    server_url = options['server_url']
    check_biblio_no = options['check_biblio']
    ssl._create_default_https_context = ssl._create_unverified_context

    try:
        files = [ os.path.join(options['input_file'], file) for file in os.listdir(options['input_file']) if os.path.splitext(file)[1] == '.xml' ]
    except NotADirectoryError:
        files = [options['input_file']]
    if not files:
        logger.error("Found no xml files at {0}".format(options['input_file']))
    if files:
        run(files, options['output_directory'])
