from sickle import Sickle
import pymarc
import argparse
from io import StringIO
from io import BytesIO
from lxml import etree
import os
import shutil
import logging
import xml

logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
sh = logging.StreamHandler()
sh.setFormatter(formatter)
logger.addHandler(sh)

def is_writable_directory(file_path: str):
    directory = os.path.dirname(file_path)

    if os.path.exists(directory) and (not os.path.isdir(directory) or not os.access(directory, os.W_OK)):
        msg = "Please provide writable directory."
        raise argparse.ArgumentTypeError(msg)
    elif not os.path.exists(directory):
        os.makedirs(directory)
        return file_path
    else:
        return file_path

parser = argparse.ArgumentParser(description='Export data using our OAI endpoint for download.')
parser.add_argument('server_url', type=str, help="Host URL")
parser.add_argument('output_file', type=is_writable_directory, help="Output file for the export.")
parser.add_argument('--set', dest='oai_set', type=str, default="", help="Optional OAI set retriction.")


if __name__ == '__main__':
    options = vars(parser.parse_args())

    sickle = Sickle('{0}/oai/Server'.format(options['server_url']))
    oai_records = sickle.ListRecords(metadataPrefix='marc21', ignore_deleted=True, set=options['oai_set'])

    overall = oai_records.resumption_token.complete_list_size

    tmp_file = "/tmp/{}".format(os.path.basename(options['output_file']))

    counter = 1
    with open(tmp_file, 'wb') as out:
      for oai_record in oai_records:
        marc_record_tree = oai_record.xml.xpath(
          './oai:metadata/marc:record', 
          namespaces={'oai': 'http://www.openarchives.org/OAI/2.0/', 'marc': 'http://www.loc.gov/MARC21/slim'}
        )[0]
        marc_record_tree_as_string = etree.tostring(marc_record_tree, encoding='UTF-8').decode()

        try: 
          marc_record_parsed = pymarc.marcxml.parse_xml_to_array(StringIO(marc_record_tree_as_string))[0]
          out.write(marc_record_parsed.as_marc())
        except xml.sax._exceptions.SAXParseException as e:
          print(e)
          print(marc_record_tree_as_string)

        if counter % 1000 == 0:
          logger.info("Processed {} of {} records.".format(counter, overall))

        counter += 1

    logger.info("Processed {} records".format(overall))
    shutil.move(tmp_file, options['output_file'])