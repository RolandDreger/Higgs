import EpubExportField from "./components/fields/EpubExportField.vue";

/* Panel code */
panel.plugin("higgs/epub-export", {
  fields: {
    epubExport: EpubExportField
  }
});