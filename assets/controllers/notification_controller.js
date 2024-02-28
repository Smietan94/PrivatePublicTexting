import { Controller }            from '@hotwired/stimulus';
import { PHP_ROUTE_PATH }        from '../js/constants';
import { handleNotificationTag } from '../js/service/notificationsService';
import { processFetchPOSTInit } from '../js/service/basicStuffService';

export default class extends Controller {
    static values = {
        url: String,
    };

    static targets = [ 'notificationsList' ];

    async dateSortOrder(event) {
        const order = event.currentTarget.value;
        let   icon  = event.currentTarget.querySelector('i');

        switch (true) {
            case order == 'ASC':
                event.currentTarget.value = 'DESC';
                icon.classList.remove('bi-sort-up-alt');
                icon.classList.add('bi-sort-down-alt');
                break;

            case order == 'DESC':
                event.currentTarget.value = 'ASC';
                icon.classList.remove('bi-sort-down-alt');
                icon.classList.add('bi-sort-up-alt');
                break;
        }

        const newOrder = event.currentTarget.value;
        const params   = new URLSearchParams({
            order:       newOrder,
            orderByDate: 1,
        });

        let response = await fetch(`${PHP_ROUTE_PATH.RELOAD_NOTIFICATIONS_MODAL}?${params.toString()}`);

        this.notificationsListTarget.innerHTML = await response.text();

        handleNotificationTag();
    }

    async filterNotifications(event) {
        const notificationType = event.currentTarget.value;
        const params           = new URLSearchParams({
            notificationType:       notificationType,
            notificationTypeFilter: 1
        });

        let response = await fetch(`${PHP_ROUTE_PATH.RELOAD_NOTIFICATIONS_MODAL}?${params.toString()}`);

        this.notificationsListTarget.innerHTML = await response.text();

        handleNotificationTag();
    }

    async resetNotificationsFilters(event) {
        const params = new URLSearchParams({
            resetNotificationsFilters: 1
        });

        let response = await fetch(`${PHP_ROUTE_PATH.RELOAD_NOTIFICATIONS_MODAL}?${params.toString()}`);

        this.notificationsListTarget.innerHTML = await response.text();

        this.reloadNotificationsFiltersList();
    }

    async reloadNotificationsFiltersList() {
        let notificationsFiltersList = document.getElementById('notifications-filters-list');

        let response = await fetch(
            PHP_ROUTE_PATH.RELOAD_NOTIFICATIONS_FILTERS_LIST,
            processFetchPOSTInit({data: true})
        );

        notificationsFiltersList.innerHTML = await response.text();
        handleNotificationTag();
    }
}
