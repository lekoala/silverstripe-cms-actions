/* global FilePond, FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginImageValidateSize */
document.addEventListener("DOMContentLoaded", function () {
    FilePond.registerPlugin(FilePondPluginFileValidateSize);
    FilePond.registerPlugin(FilePondPluginFileValidateType);
    FilePond.registerPlugin(FilePondPluginImageValidateSize);
    // FilePond.registerPlugin(FilePondPluginFileMetadata);
    // FilePond.registerPlugin(FilePondPluginFilePoster);
    // FilePond.registerPlugin(FilePondPluginImageExifOrientation);
    // FilePond.registerPlugin(FilePondPluginImagePreview);

    FilePond.setOptions({
        credits: false
    });

    // Attach filepond to all related inputs
    var anchors = document.querySelectorAll('input[type="file"].filepond');
    for (var i = 0; i < anchors.length; i++) {
        var el = anchors[i];
        var pond = FilePond.create(el);
        var config = JSON.parse(el.dataset.config);
        for (var key in config) {
            pond[key] = config[key];
        }
    }
});
