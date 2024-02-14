/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../styles/app.scss';
// start the Stimulus application
import '../bootstrap';

import { Tooltip, Modal } from "bootstrap";
import { startActiveNotificationChannelEventSource, startMessagePreviewEventSource, startConversationHelperEventSource, getNewMemberPreviewScriptTag } from './service/notificationsService';
import { startEventSource, manageEventSource, confirmMemberRemove, processEnterSendMessage } from './service/chatService';
import { removeFriend } from './service/friendService';
import { handleOffcanvasButtons } from './service/basicStuffService';

require('bootstrap');

let activeNotificationChannel   = null;
let activeMsgPreviewEventSource = null;
let newGroupPreviewEventSource  = null;
let newGroupMemberPreview       = null;
let activeChatEventSource       = null;

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('turbo:load', function () {
        const scriptTag                    = document.getElementById('mercure-url');
        const selectNewMembers             = document.getElementById('add_users_to_conversation_users');
        const notificationChannelTag       = document.getElementById('mercure-notifications-url');
        const mercureScriptTag             = document.getElementById('mercure-message-preview-url');
        const selectNewConversationMembers = document.getElementById('create_group_conversation_friends');
        const leaveGroupBtn                = document.querySelector('.leave-group-btn');
        const removeConversationBtn        = document.querySelector('.remove-conversation-btn');
        const removeFriendsButtons         = document.querySelectorAll('.btn-remove-friend');
        const rmConversationUserBtns       = document.querySelectorAll('.rm-user-btn');
        const tooltipTriggerList           = document.querySelectorAll('[data-bs-toggle="tooltip"]');

        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl));

        handleOffcanvasButtons(tooltipList);

        const toogleNotificationsModalATag = document.getElementById('toogle-notifications-modal');
        const notificationsModal           = document.getElementById('notifications-modal');
        let modal = new Modal(notificationsModal);
        toogleNotificationsModalATag.addEventListener('click', function () {
            modal.show();
        });

        removeFriendsButtons.forEach(function(rmFriendBtn) {
            removeFriend(rmFriendBtn);
        });

        if (rmConversationUserBtns) {
            rmConversationUserBtns.forEach(button => {
                button.addEventListener('click', function(event) {
                    confirmMemberRemove(button, event);
                }) 
            });
        }

        if (removeConversationBtn) {
            removeConversationBtn.addEventListener('click', function(event) {
                var confirmation = confirm('Do you want to remove this conversation?');

                if (!confirmation) {
                    event.preventDefault();
                }
            });
        }

        if (leaveGroupBtn) {
            leaveGroupBtn.addEventListener('click', function(event) {
                var confirmation = confirm('Do You want to leave this group?');

                if (!confirmation) {
                    event.preventDefault();
                }
            })
        }

        if (scriptTag) {
            const url   = JSON.parse(scriptTag.textContent);
            const topic = url.split("?")[1];

            activeChatEventSource = manageEventSource(
                startEventSource,
                activeChatEventSource,
                topic,
                url
            );
        }

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
            selectNewConversationMembers.addEventListener('change', handleNewGroupMembersSelect);
        }

        const handleNewGroupMembersSelect = async () => {
            let selectedMembersScriptTag = await getNewMemberPreviewScriptTag();

            if (selectedMembersScriptTag) {
                let groupPreviewUrl   = JSON.parse(selectedMembersScriptTag.textContent);
                let groupPreviewTopic = groupPreviewUrl.split("?")[1];

                newGroupPreviewEventSource = manageEventSource(
                    startConversationHelperEventSource,
                    newGroupPreviewEventSource,
                    groupPreviewTopic,
                    groupPreviewUrl
                );
            }
        };

        if (selectNewMembers) {
            selectNewMembers.addEventListener('change', handleNewMembersSelect);
        }

        const handleNewMembersSelect = async () => {
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
        };

        processEnterSendMessage();
    });

    document.addEventListener('turbo:frame-render', function () {
        const removeFriendsButtons = document.querySelectorAll('.btn-remove-friend');

        removeFriendsButtons.forEach(function(rmFriendBtn, index) {
            removeFriend(rmFriendBtn);
        });

        processEnterSendMessage(); 
    });
});