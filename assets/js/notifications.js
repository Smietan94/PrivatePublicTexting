import { startActiveNotificationChannelEventSource, startMessagePreviewEventSource, startConversationHelperEventSource } from './notificationsHelper';
import { manageEventSource } from "./chatHelper";

let activeNotificationChannel   = null;
let activeMsgPreviewEventSource = null;
let newGroupPreviewEventSource  = null;
let newGroupMemberPreview       = null;

document.addEventListener('turbo:load', function () {
    const selectNewConversationMembers = document.getElementById('create_group_conversation_friends');
    const selectNewMembers             = document.getElementById('add_users_to_conversation_users');
    const notificationChannelTag       = document.getElementById('mercure-notifications-url');
    const mercureScriptTag             = document.getElementById('mercure-message-preview-url');

    if (notificationChannelTag) {
        const notificationsUrl   = JSON.parse(notificationChannelTag.textContent);
        const notificationsTopic = notificationsUrl.split("?")[1];

        activeNotificationChannel = manageEventSource(
            startActiveNotificationChannelEventSource,
            activeNotificationChannel,
            notificationsTopic,
            notificationsUrl
        );
    }

    if (mercureScriptTag) {
        const msgPreviewMercureUrl   = JSON.parse(mercureScriptTag.textContent);
        const msgPreviewMercureTopic = msgPreviewMercureUrl.split("?")[1];

        activeMsgPreviewEventSource = manageEventSource(
            startMessagePreviewEventSource,
            activeMsgPreviewEventSource,
            msgPreviewMercureTopic,
            msgPreviewMercureUrl
        );
    }

    if (selectNewConversationMembers) {
        selectNewConversationMembers.addEventListener('change', async function () {
            let newGroupPreviewMercureScriptTag = await getNewMemberPreviewScriptTag();

            if (newGroupPreviewMercureScriptTag) {
                let newGroupPreviewUrl   = JSON.parse(newGroupPreviewMercureScriptTag.textContent);
                let newGroupPreviewTopic = newGroupPreviewUrl.split("?")[1];

                newGroupPreviewEventSource = manageEventSource(
                    startConversationHelperEventSource,
                    newGroupPreviewEventSource,
                    newGroupPreviewTopic,
                    newGroupPreviewUrl
                );
            }
        });
    }

    if (selectNewMembers) {
        selectNewMembers.addEventListener('change', async function () {
            let selectedMembersScriptTag = await getNewMemberPreviewScriptTag();

            if (selectedMembersScriptTag) {
                let groupPreviewUrl   = JSON.parse(selectedMembersScriptTag.textContent);
                let groupPreviewTopic = groupPreviewUrl.split("?")[1];

                newGroupMemberPreview = manageEventSource(
                    startConversationHelperEventSource,
                    newGroupMemberPreview,
                    groupPreviewTopic,
                    groupPreviewUrl
                );
            }
        });
    }
});

function getNewMemberPreviewScriptTag() {
    let scriptTagId = document.getElementById('mercureScriptTagId').value;

    return new Promise(function (resolve) {
        setTimeout(function () {
            resolve(document.getElementById(scriptTagId));
        }, 1000);
    });
};
