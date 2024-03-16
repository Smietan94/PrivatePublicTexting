import { Controller }  from '@hotwired/stimulus';
import { FILE_UPLOAD } from '../js/constants';

export default class extends Controller {
    static targets = ['output'];

    // updloaded imgs previews handling
    handleFilePreview(event) {
        let messageFormTextarea = document.querySelector('textarea[name="message[message]"]');
        messageFormTextarea.setCustomValidity('');
        let files = event.currentTarget.files;
        if (files) {
            if (files.length > FILE_UPLOAD.MAX_FILE_UPLOADS) {
                messageFormTextarea.setCustomValidity(`Too much files, max file uploads = ${FILE_UPLOAD.MAX_FILE_UPLOADS}`);
                messageFormTextarea.reportValidity();
            } else {
                this.processFilesPreview(files);
            }
        }
    }

    // renders uploaded files previews
    processFilesPreview(files) {
        let previewsContainer = document.getElementById('updated-files-previews-container');

        previewsContainer.classList.add('img-preview-height');

        Array.from(files).forEach((element, i) => {
            this.outputTarget.innerHTML = '';
            var reader = new FileReader();

            reader.onload = () => {
                let result = `
                <div class="col-1 nopadding position-relative text-center" id="upload-preview-${i}">
                    <img src="${reader.result}"
                    alt="preview" class="img-thumbnail img-fluid img-preview"
                    height="100px">
                    <button type="button" value="${i}"
                    data-action="fileUpload#deleteUnwantedAttachment"
                    class="bi bi-x-circle bi-resized btn btn-dark btn-lg btn-noborder btn-badge nopadding opacity-75 position-absolute top-15 start-85 translate-middle rounded-circle">
                    </button>
                </div>
                `;
                this.outputTarget.innerHTML += result;
            }

            reader.readAsDataURL(element);
        });
    }

    // removing unwanted attachment
    deleteUnwantedAttachment(event) {
        let previewIndex = event.currentTarget.value;
        let fileInput    = document.getElementById('message_attachment');
        let filesArray   = Array.from(fileInput.files);

        if (filesArray) {
            let dataTranser = new DataTransfer();

            filesArray.forEach((element, i) => {
                if (i != previewIndex) {
                    dataTranser.items.add(element);
                }
            });

            fileInput.files = dataTranser.files;

            this.processFilesPreview(fileInput.files);

            if (fileInput.files.length == 0) {
                let previewToRm       = document.getElementById(`upload-preview-${previewIndex}`);
                let previewsContainer = document.getElementById('updated-files-previews-container');

                previewsContainer.classList.remove('img-preview-height');
                previewToRm.remove();
            }
        }
    }
}
