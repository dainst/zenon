package org.dainst.index;

/**
 * Custom script for mapping localized field values to separate solr fields.
 *
 * @author Simon Hohl <simon.hohl@dainst.org>
 */

import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.DataField;


import org.solrmarc.index.SolrIndexer;


import java.util.List;


public class OtherStandardIdentifier
{
  public String getGazetteerId(Record record, String tag, String valSubField, String externalResourceKeySubField) {
    return extractId(record, tag, valSubField, externalResourceKeySubField, "iDAI.gazetteer");
  }

  public String getThesauriId(Record record, String tag, String valSubField, String externalResourceKeySubField) {
    return extractId(record, tag, valSubField, externalResourceKeySubField, "iDAI.thesauri");
  }

  public String getOrcId(Record record, String tag, String valSubField, String externalResourceKeySubField) {
    return extractId(record, tag, valSubField, externalResourceKeySubField, "orcid");
  }

  private String extractId(Record record, String tag, String valSubField, String subfieldKey, String requiredSubfieldValue){

    List<VariableField> fields = record.getVariableFields(tag);
    for (VariableField vf : fields) {
      DataField field = (DataField) vf;

      if(field.getSubfield(subfieldKey.charAt(0)) == null) return "";
      if(field.getSubfield(valSubField.charAt(0)) == null) return "";

      if (field.getSubfield(subfieldKey.charAt(0)).getData().equals(requiredSubfieldValue)) {
        return field.getSubfield(valSubField.charAt(0)).getData();
      }
    }
    return "";
  }
}
