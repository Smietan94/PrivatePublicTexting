import { startActiveNotificationChannelEventSource, startMessagePreviewEventSource } from './notificationsHelper';
import { checkLastEventSource } from "./chatHelper";

let activeNotificationChannel   = null;
let activeMsgPreviewEventSource = null;

document.addEventListener('turbo:load', function () {
    const notificationChannelTag = document.getElementById('mercure-notifications-url');
    const mercureScriptTag       = document.getElementById('mercure-message-preview-url');
    const msgPreviewScriptTag    = document.getElementById('message-preview-url');

    if (notificationChannelTag) {
        const notificationsUrl   = JSON.parse(notificationChannelTag.textContent);
        const notificationsTopic = notificationsUrl.split("?")[1];

        if (!activeNotificationChannel) {
            activeNotificationChannel = startActiveNotificationChannelEventSource(notificationsUrl);
            console.log('notification channel open');
        } else if (!checkLastEventSource(notificationsTopic, activeNotificationChannel) && activeNotificationChannel) {
            activeNotificationChannel.close();
            activeNotificationChannel = startActiveNotificationChannelEventSource(notificationsUrl);
            console.log('new notification channel established');
        } else {
            console.log('notification channel remains unchanged');
        }
    }

    if (mercureScriptTag) {
        const msgPreviewUrl          = msgPreviewScriptTag.textContent;
        const msgPreviewMercureUrl   = JSON.parse(mercureScriptTag.textContent);
        const msgPreviewMercureTopic = msgPreviewMercureUrl.split("?")[1];

        if (!activeMsgPreviewEventSource) {
            activeMsgPreviewEventSource = startMessagePreviewEventSource(msgPreviewMercureUrl, msgPreviewUrl);
            console.log('connection to receiver channel established');
        } else if (!checkLastEventSource(msgPreviewMercureTopic, activeMsgPreviewEventSource) && activeMsgPreviewEventSource) {
            activeMsgPreviewEventSource.close();
            activeMsgPreviewEventSource = startMessagePreviewEventSource(msgPreviewMercureUrl, msgPreviewUrl);
            console.log('new connection to receiver channel established');
        } else {
            console.log('connection to receiver channel remains unchanged')
        }
    }

    // subscribe to receiver channel and send message preview and figure out how to do it with groups
    // add methods to controller and service, new twig template with message preview
});

//friend request notification

//friend response notification



