import EpubExportField from "./components/EpubExportField.vue";

/* Panel code */
panel.plugin("higgs/epub-export", {
  fields: {
    epubExportButton: EpubExportField
  }
});