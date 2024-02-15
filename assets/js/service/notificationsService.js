import { Modal } from "bootstrap";
import { PHP_ROUTE_PATH, ACTIVITY_STATUS } from "../constants";
import { removeFriendCard } from "./friendService";



function startActiveNotificationChannelEventSource(url) {
    let eventSource       = new EventSource(url, {
        withCredentials: true
    });

    console.log('Active notification channel event source started');

    eventSource.onopen = event => {
        setActivityStatus(ACTIVITY_STATUS.ACTIVE);
    }

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data.messagePreview) {
            processMessagePreview(data.messagePreview);
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

        if (data.friendRemoveData) {
            removeFriendCard(data.friendRemoveData.removingUserId);
        }

        updateNotificationsNumber();
        // odswiezanie modala z powiadomieniami
    }

    eventSource.onerror = event => {
        setActivityStatus(ACTIVITY_STATUS.INACTIVE);
        processPageReload();
    }

    return eventSource;
}

function startMessagePreviewEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    console.log('Message preview event source started');

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data.messagePreview) {
            processMessagePreview(data.messagePreview);
        }
    }

    return eventSource;
}

function startConversationHelperEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    return eventSource;
}

async function processConversationMemberRemoval(data) {
    let response = await fetch(PHP_ROUTE_PATH.REDIRECT_REMOVED_USER, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: data})
    });

    let responseData = await response.json();

    if (document.getElementById(`conversation-${ data.conversationId }-name`)) {
        if (responseData.currentUserId == responseData.removedUserId) {
            window.location.href = PHP_ROUTE_PATH.GROUPS;

        } else {
            removeUserRemoveButton(responseData.removedUserId);
        }

    } else if (responseData.currentUserId == responseData.removedUserId) {
        removeConversationLabel(responseData.conversationId);
    }
}

async function setActivityStatus(activityStatusCode) {
    await fetch(PHP_ROUTE_PATH.SET_ACTIVITY_STATUS, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({userActivityStatusCode: activityStatusCode})
    });
}

async function processConversationRemove(conversationId) {
    let response = await fetch(PHP_ROUTE_PATH.PROCESS_CONVERSATION_REMOVE, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({removedConversationId: conversationId})
    });

    if (document.getElementById(`conversation-${ conversationId }-name`)) {
        window.location.href = PHP_ROUTE_PATH.GROUPS;

    } else if (document.getElementsByName('group-conversations-list')) {
        removeConversationLabel(conversationId);
    }
}

async function processGroupConversationLabel(convId, isConversationUpdate = false) {
    let groupConversationsList = document.getElementsByName('group-conversations-list')[0];

    if (groupConversationsList) {
        try {
            const response = await fetch(PHP_ROUTE_PATH.PROCESS_CONVERSATION_LABEL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({conversationId: convId})
            });

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

async function processMessagePreview(data) {
    let messagePreviewElement = document.getElementById(`conversation-${ data.conversationId }-last-message`);

    if (messagePreviewElement) {
        try {
            const response = await fetch(PHP_ROUTE_PATH.MESSAGE_PREVIEW, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({conversationId: data.conversationId})
            });

            if (!response.ok) {
                throw new Error('Failed to process message preview');
            }

            messagePreviewElement.innerHTML = await response.text();

            sortConversationLabels(data.conversationId);

        } catch(error) {
            console.log('Error during processing message preview', error);
        }
    }
}

async function updateNotificationsNumber() {
    let navDropDown = document.getElementById('nav-drop-down');
    let response    = await fetch(PHP_ROUTE_PATH.GET_UNSEEN_NOTIFICATIONS_NUMBER, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: true})
    });

    navDropDown.innerHTML = await response.text();
}

async function updateConversationMembersList(convId) {
    let conversationMembersList = document.getElementById('conversation-members-list');

    if (conversationMembersList) {
        try {
            let response = await fetch(PHP_ROUTE_PATH.UPDATE_MEMBERS_LIST, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({conversationId: convId})
            });

            if (!response.ok) {
                throw new Error('Failed to update conversation members list');
            }

            conversationMembersList.innerHTML = await response.text();

        } catch (error) {
            console.log('Error during updating conversation members list', error);
        }
    }
}

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
    getNewMemberPreviewScriptTag
};