import EpubExportField from "./components/fields/EpubExportField.vue";
import EpubFileNameField from "./components/fields/EpubFileNameField.vue";

/* Panel code */
panel.plugin("higgs/epub-export", {
  fields: {
    epubExport: EpubExportField,
		epubFileName: EpubFileNameField
  }
});