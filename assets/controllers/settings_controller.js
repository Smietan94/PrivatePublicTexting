import { Controller }     from '@hotwired/stimulus';
import { PHP_ROUTE_PATH } from '../js/constants';
import { Modal }          from 'bootstrap';

export default class extends Controller {
    static targets = ['settingsFormModalOutput'];

    // opens email change modal
    async handleEmailChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_EMAIL);
        let modal    = new Modal(response);
        modal.show();
    }

    // opens username change modal
    async handleUsernameChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_USERNAME);
        let modal    = new Modal(response);
        modal.show();
    }

    // opens password change modal
    async handlePasswordChange(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_CHANGE_PASSWORD);
        let modal    = new Modal(response);
        modal.show();
    }

    // opens account delete modal
    async handleAccountDelete(event) {
        let response = await this.processSettingModalRequest(PHP_ROUTE_PATH.SETTINGS_DELETE_ACCOUNT);
        let modal    = new Modal(response);
        modal.show();
    }

    // processing settings modal request
    async processSettingModalRequest(url) {
        let response = await fetch(url);

        this.settingsFormModalOutputTarget.innerHTML = await response.text();

        return this.settingsFormModalOutputTarget.querySelector('.modal');
    }
}