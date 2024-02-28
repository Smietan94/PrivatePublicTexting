import { Modal }                 from "bootstrap";
import { PHP_ROUTE_PATH }        from "../constants";
import { handleNotificationTag } from "./notificationsService";

function handleOffcanvasButtons(tooltipList) {
    const navbarOffcanvas  = document.getElementById('navbarSupportedContent');
    const navChats         = document.getElementById('nav-chats');
    const navGroupChats    = document.getElementById('nav-group-chats');
    const navFriends       = document.getElementById('nav-friends');
    const navSearchFriends = document.getElementById('nav-search-friends');


    const handleShowOffcanvas = () => navbarOffcanvas.addEventListener('show.bs.offcanvas', function () {
        navChats.innerHTML         = 'Chats';
        navGroupChats.innerHTML    = 'Group Chats';
        navFriends.innerHTML       = 'Friends';
        navSearchFriends.innerHTML = 'Search Friends';

        tooltipList.forEach(tooltip => {
            tooltip.disable();
        });
    });

    const handleHiddenOffcanvas = () => navbarOffcanvas.addEventListener('hidden.bs.offcanvas', function () {
        navChats.innerHTML         = '';
        navGroupChats.innerHTML    = '';
        navFriends.innerHTML       = '';
        navSearchFriends.innerHTML = '';

        tooltipList.forEach(tooltip => {
            tooltip.enable();
        });
    });

    if (navbarOffcanvas) {
        handleShowOffcanvas();
        handleHiddenOffcanvas();
    }
};

async function handleNotificationsModal() {
    let notificationsModalContainer = document.getElementById('notifications-modal-container');

    if (notificationsModalContainer) {
        let response = await fetch(
            PHP_ROUTE_PATH.RENDER_NOTIFICATIONS_MODAL,
            processFetchPOSTInit({data: true})
        );

        notificationsModalContainer.innerHTML = await response.text();

        const notificationsModal           = notificationsModalContainer.querySelector('.modal');
        const toogleNotificationsModalATag = document.getElementById('toogle-notifications-modal');

        let modal = new Modal(notificationsModal);

        handleNotificationTag();

        toogleNotificationsModalATag.addEventListener('click', function () {
            modal.show();
        });
    }
}

function processFetchPOSTInit(data) {
    return {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    };
}

export {
    handleOffcanvasButtons,
    handleNotificationsModal,
    processFetchPOSTInit
};