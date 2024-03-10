import { Controller }           from '@hotwired/stimulus';
import { Modal }                from 'bootstrap';
import { processFetchPOSTInit } from '../js/service/basicStuffService';
import { PHP_ROUTE_PATH }       from '../js/constants';

export default class extends Controller {
    static values  = {imgCarouselModalUrl: String};
    static targets = ['imgCarouselModal', 'imgOutput'];

    async handleImgCarouselModal(event) {
        const attachmentId = event.currentTarget.value;

        let response = await fetch(
            this.imgCarouselModalUrlValue,
            processFetchPOSTInit({attachmentId: attachmentId})
        );

        this.imgCarouselModalTarget.innerHTML = await response.text();

        let imgCarouselModal = document.getElementById('img-carousel-modal');
        let modal = new Modal(imgCarouselModal);

        modal.show();
    }

    async getImg(event) {
        this.processActiveImgButton(event);

        const attachmentId = event.currentTarget.value;

        let response = await fetch(
            PHP_ROUTE_PATH.PROCESS_GET_IMG_TAG,
            processFetchPOSTInit({attachmentId: attachmentId})
        );

        this.imgOutputTarget.innerHTML = await response.text();
    }

    processActiveImgButton(event) {
        let btnList   = document.getElementById('mix-carousel-imgs-list');
        let activeBtn = btnList.querySelector('.nopadding.active');

        if (activeBtn) {
            activeBtn.classList.remove('active');
        }

        event.currentTarget.classList.add('active');
    }
}
