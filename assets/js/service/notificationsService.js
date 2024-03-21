import { PHP_ROUTE_PATH, ACTIVITY_STATUS }          from "../constants";
import { reloadFriendCardDiv, processRequestsList } from "./friendService";
import { processFetchPOSTInit }                     from "./basicStuffService";

// start main notification event source its receives events and act by it
function startActiveNotificationChannelEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    console.log('Active notification channel event source started');

    // after logging in sets activity status
    eventSource.onopen = event => {
        setActivityStatus(ACTIVITY_STATUS.ACTIVE);
    }

    // on message check action type
    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data.messagePreview) {
            processMessagePreview(data.messagePreview.conversationId);
        }

        if (data.conversationId) {
            processGroupConversationLabel(data.conversationId);
        }

        if (data.conversationNameChangeData) {
            processConversationNameChange(data.conversationNameChangeData);
        }

        if (data.removedUserData) {
            processConversationMemberRemoval(data.removedUserData);
        }

        if (data.newConversationData) {
            processGroupConversationLabel(
                data.newConversationData.conversationId,
                data.newConversationData.isConversationUpdate
            );
        }

        if (data.removedConversationId) {
            processConversationRemove(data.removedConversationId);
        }

        if (data.friendRemoveData || data.acceptedFriendRequestId) {
            if (window.location.pathname == PHP_ROUTE_PATH.FRIENDS) {
                reloadFriendCardDiv();
            }
        }

        if (data.receivedFriendRequestId) {
            if (window.location.pathname == PHP_ROUTE_PATH.FRIENDS_REQUEST) {
                processRequestsList('received-requests-list', PHP_ROUTE_PATH.RECEIVED_FRIENDS_REQUESTS);
            }
        }

        if (data.deniedFriendRequestId || data.acceptedFriendRequestId) {
            if (window.location.pathname == PHP_ROUTE_PATH.FRIENDS_REQUEST) {
                processRequestsList('sent-requests-list', PHP_ROUTE_PATH.SENT_FRIENDS_REQUESTS);
            }
        }

        // updating notifications
        updateNotificationsNumber();
        updateNotificationsModal();
    }

    // on error set activity status and reloading page
    eventSource.onerror = event => {
        setActivityStatus(ACTIVITY_STATUS.INACTIVE);
        processPageReload();
    }

    return eventSource;
}

// event source responsible for upfating message preview
function startMessagePreviewEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    console.log('Message preview event source started');

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data.messagePreview) {
            processMessagePreview(data.messagePreview.conversationId);
        }
    }

    return eventSource;
}

// starts event source for added conversation members
function startConversationHelperEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    return eventSource;
}

// handles conversation members removal
async function processConversationMemberRemoval(data) {
    let response = await fetch(
        PHP_ROUTE_PATH.REDIRECT_REMOVED_USER,
        processFetchPOSTInit({data: data})
    );

    let responseData = await response.json();

    // checks if user currently on conversation page
    if (document.getElementById(`conversation-${ data.conversationId }-name`)) {
        // if current user is deleted from conversation it is redirect to groups page
        if (responseData.currentUserId == responseData.removedUserId) {
            window.location.href = PHP_ROUTE_PATH.GROUPS;

        } else {
            // if no deleted user then remove btn is removed
            removeUserRemoveButton(responseData.removedUserId);
        }

    // if user in another conversation lebel is removed
    } else if (responseData.currentUserId == responseData.removedUserId) {
        removeConversationLabel(responseData.conversationId);
    }
}

async function setActivityStatus(activityStatusCode) {
    await fetch(
        PHP_ROUTE_PATH.SET_ACTIVITY_STATUS,
        processFetchPOSTInit({userActivityStatusCode: activityStatusCode})
    );
}

// process removing conversation
async function processConversationRemove(conversationId) {
    await fetch(
        PHP_ROUTE_PATH.PROCESS_CONVERSATION_REMOVE,
        processFetchPOSTInit({removedConversationId: conversationId})
    );

    // if member on conversation then redirect to groups page
    if (document.getElementById(`conversation-${ conversationId }-name`)) {
        window.location.href = PHP_ROUTE_PATH.GROUPS;

    // if not then label remove
    } else if (document.getElementsByName('group-conversations-list')) {
        removeConversationLabel(conversationId);
    }
}

// updates conversations list if current user added to conversastion, else members list update
async function processGroupConversationLabel(convId, isConversationUpdate = false) {
    let groupConversationsList = document.getElementsByName('group-conversations-list')[0];

    if (groupConversationsList) {
        try {
            const response = await fetch(
                PHP_ROUTE_PATH.PROCESS_CONVERSATION_LABEL,
                processFetchPOSTInit({conversationId: convId})
            );

            if (!response.ok) {
                throw new Error('Failed to process message preview');
            }

            let element = await response.text();

            groupConversationsList.innerHTML += element;

            sortConversationLabels(convId);

            if (isConversationUpdate == true) {
                updateConversationMembersList(convId);
            }

        } catch(error) {
            console.log('Error during processing message preview', error);
        }
    }
}

// real time conversation label update
async function processMessagePreview(conversationId) {
    let messagePreviewElement = document.getElementById(`conversation-${ conversationId }-last-message`);

    if (messagePreviewElement) {
        try {
            const response = await fetch(
                PHP_ROUTE_PATH.MESSAGE_PREVIEW,
                processFetchPOSTInit({conversationId: conversationId})
            );

            if (!response.ok) {
                throw new Error('Failed to process message preview');
            }

            messagePreviewElement.innerHTML = await response.text();

            sortConversationLabels(conversationId);

        } catch(error) {
            console.log('Error during processing message preview', error);
        }
    }
}

// reloading notifications number in nav dropdown
async function updateNotificationsNumber() {
    let navDropDown = document.getElementById('nav-drop-down');

    let response = await fetch(
        PHP_ROUTE_PATH.GET_UNSEEN_NOTIFICATIONS_NUMBER,
        processFetchPOSTInit({data: true})
    );

    navDropDown.innerHTML = await response.text();
}

// reloading notifications modal
async function updateNotificationsModal() {
    let notificationsModalContainer = document.getElementById('notifications-modal-container');
    let notificationsList           = notificationsModalContainer.querySelector('.list-group');

    let response = await fetch(
        PHP_ROUTE_PATH.RELOAD_NOTIFICATIONS_MODAL,
        processFetchPOSTInit({data: true})
    );

    notificationsList.innerHTML = await response.text();
    setTimeout(() => handleNotificationTag(), 500);
}

// updating conversation members list
async function updateConversationMembersList(convId) {
    let conversationMembersList = document.getElementById('conversation-members-list');

    if (conversationMembersList) {
        try {
            let response = await fetch(
                PHP_ROUTE_PATH.UPDATE_MEMBERS_LIST,
                processFetchPOSTInit({conversationId: convId})
            );

            if (!response.ok) {
                throw new Error('Failed to update conversation members list');
            }

            conversationMembersList.innerHTML = await response.text();

        } catch (error) {
            console.log('Error during updating conversation members list', error);
        }
    }
}

// setting notification display if its seen
function setNotificationDisplayStatus(notificationTag) {
    if (notificationTag.dataset.listener === 'true' || notificationTag.getAttribute('data-set-displayed') === 1) {
        return;
    }

    const updateModal = notificationTag => {
        setTimeout(() => {
            updateNotificationsModal();
            updateNotificationsNumber();
        }, 500);
    }

    notificationTag.addEventListener('click', async function() {
        await fetch(
            PHP_ROUTE_PATH.SET_NOTIFICATION_DISPLAY_STATUS,
            processFetchPOSTInit({notificationId: notificationTag.getAttribute('value')})
        );

        updateModal(notificationTag);
    });

    notificationTag.dataset.listener = 'true';
}

// handles all notifications
function handleNotificationTag() {
    let notifications = document.querySelectorAll('.notifications-list-item');
    notifications.forEach(notificationTag => {
        setNotificationDisplayStatus(notificationTag);
    });
}

// real time conversation name update
function processConversationNameChange(data) {
    let conversationId   = data.conversationId;
    let conversationName = data.conversationName;

    let conversationNameHeader = document.getElementById(`conversation-${ conversationId }-name`);
    let conversationNameLabel  = document.getElementById(`conversation-${ conversationId }-name-label`);

    if (conversationNameHeader) {
        conversationNameHeader.innerHTML = conversationName;
    }

    if (conversationNameLabel) {
        conversationNameLabel.innerHTML  = conversationName.slice(0, 20);
    }
}

// sort conversation labels from newest (order from newest message)
function sortConversationLabels(conversationId) {
    let conversationsListDiv    = document.getElementById('conversations-list');
    let conversationLabelToMove = document.getElementById(`conversation-${ conversationId }`);
    let conversationLabelsArray = conversationsListDiv.querySelectorAll('a');

    if (conversationLabelToMove != conversationLabelsArray[0]) {
        conversationsListDiv.innerHTML = "";
        conversationsListDiv.append(conversationLabelToMove);
        conversationsListDiv.innerHTML += '<hr class="hr w-100"/>'

        conversationLabelsArray.forEach(element => {
            if (element != conversationLabelToMove) {
                conversationsListDiv.append(element);

                if (element != conversationLabelsArray[conversationLabelsArray.length - 1]) {
                    conversationsListDiv.innerHTML += '<hr class="hr w-100"/>'
                }
            }
        });
    }
}

function removeConversationLabel(conversationId) {
    let converastionLabel = document.getElementById(`conversation-${ conversationId }`);
    let hrLineToDelete    = converastionLabel.nextElementSibling;

    converastionLabel.remove();

    if (hrLineToDelete) {
        hrLineToDelete.remove();
    }
}

function removeUserRemoveButton(removedUserId) {
    let userRemoveLiElement = document.getElementById(`member-${ removedUserId }`);

    if (userRemoveLiElement) {
        userRemoveLiElement.remove();
    }
}

function processPageReload() {
    location.reload(true);
}

function getNewMemberPreviewScriptTag() {
    let scriptTagId = document.getElementById('mercureScriptTagId').value;

    return new Promise(function (resolve) {
        setTimeout(function () {
            resolve(document.getElementById(scriptTagId));
        }, 1000);
    });
};

export {
    startMessagePreviewEventSource,
    startActiveNotificationChannelEventSource,
    startConversationHelperEventSource,
    getNewMemberPreviewScriptTag,
    handleNotificationTag,
    updateNotificationsNumber,
    updateNotificationsModal,
};