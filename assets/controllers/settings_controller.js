import { Controller }     from '@hotwired/stimulus';
import { PHP_ROUTE_PATH } from '../js/constants';
import { Modal }          from 'bootstrap';

export default class extends Controller {
    static targets = ['settingsFormModalOutput'];

    async handleEmailChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_EMAIL);
        let modal    = new Modal(response);
        modal.show();
    }

    async handleUsernameChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_USERNAME);
        let modal    = new Modal(response);
        modal.show();
    }

    async handlePasswordChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_PASSWORD);
        let modal    = new Modal(response);
        modal.show();
    }

    async handleAccountDelete(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_DELETE_ACCOUNT);
        let modal    = new Modal(response);
        modal.show();
    }

    async processSettingModalRequest(url) {
        let response = await fetch(url);

        this.settingsFormModalOutputTarget.innerHTML = await response.text();

        return this.settingsFormModalOutputTarget.querySelector('.modal');
    }
}