import { Modal } from "bootstrap";

function startActiveNotificationChannelEventSource(url) {
    let eventSource       = new EventSource(url, {
        withCredentials: true
    });

    console.log('Active notification channel event source started');

    eventSource.onopen = event => {
        setActivityStatus(0);
    }

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data['messagePreview']) {
            processMessagePreview(
                data['messagePreview'],
                '/chats/messagePreview'
            );
        }

        if (data['conversationId']) {
            processGroupConversationLabel(
                data['conversationId']
            );
        }

        if (data['conversationNameChangeData']) {
            processConversationNameChange(data['conversationNameChangeData']);
        }

        if (data['removedUserData']) {
            processConversationMemberRemoval(data['removedUserData']);
        }

        if (data['newConversationData']) {
            processGroupConversationLabel(
                data['newConversationData']['conversationId'],
                data['newConversationData']['isConversationUpdate']
            );
        }

        if (data['removedConversationId']) {
            processConversationRemove(data['removedConversationId']);
        }
    }

    eventSource.onerror = event => {
        setActivityStatus(2);
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

        if (data['messagePreview']) {
            processMessagePreview(
                data['messagePreview']
            );
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
    let response = await fetch('/chats/redirectRemovedUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: data})
    });

    let responseData = await response.json();

    if (document.getElementById(`conversation-${ data['conversationId'] }-name`)) {
        if (responseData['currentUserId'] == responseData['removedUserId']) {
            window.location.href = '/chats/groups/';
        } else {
            removeUserRemoveButton(responseData['removedUserId']);
        }
    } else if (responseData['currentUserId'] == responseData['removedUserId']) {
        removeConversationLabel(responseData['conversationId']);
    }
}

async function setActivityStatus(activityStatusCode) {
    fetch('/setActivityStatus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: activityStatusCode})
    });
}

async function processConversationRemove(removedConversationId) {
    let response = await fetch('/chats/processConversationRemove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: removedConversationId})
    });

    if (document.getElementById(`conversation-${ removedConversationId }-name`)) {
        window.location.href = '/chats/groups/';
    } else if (document.getElementsByName('group-conversations-list')) {
        removeConversationLabel(removedConversationId);
    }
}

async function processGroupConversationLabel(conversationId, isConversationUpdate = false) {
    let groupConversationsList = document.getElementsByName('group-conversations-list')[0];

    if (groupConversationsList) {
        try {
            const response = await fetch('/chats/processConversationLabel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({data: conversationId})
            });

            if (!response.ok) {
                throw new Error('Failed to process message preview');
            }

            let element = await response.text();

            groupConversationsList.innerHTML += element;

            sortConversationLabels(conversationId);

            if (isConversationUpdate == true) {
                updateConversationMembersList(conversationId);
            }

        } catch(error) {
            console.log('Error during processing message preview', error);
        }
    }
}

function processConversationNameChange(data) {
    let conversationId   = data['conversationId'];
    let conversationName = data['conversationName'];

    let conversationNameHeader = document.getElementById(`conversation-${ conversationId }-name`);
    let conversationNameLabel  = document.getElementById(`conversation-${ conversationId }-name-label`);

    if (conversationNameHeader) {
        conversationNameHeader.innerHTML = conversationName;
    }

    if (conversationNameLabel) {
        conversationNameLabel.innerHTML  = conversationName.slice(0, 20);
    }
}

async function processMessagePreview(data) {
    let messagePreviewElement = document.getElementById(`conversation-${data['conversationId']}-last-message`);

    if (messagePreviewElement) {
        try {
            const response = await fetch('/chats/messagePreview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({data: data})
            });

            if (!response.ok) {
                throw new Error('Failed to process message preview');
            }

            messagePreviewElement.innerHTML = await response.text();

            sortConversationLabels(data['conversationId']);

        } catch(error) {
            console.log('Error during processing message preview', error);
        }
    }
}

async function updateConversationMembersList(conversationId) {
    let conversationMembersList = document.getElementById('conversation-members-list');

    if (conversationMembersList) {
        try {
            let response = await fetch('/chats/group/updateMembersList', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({data: conversationId})
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
    let reloadWindowModal = new Modal(document.getElementById('reload-window-modal'));
    let reloadPageBtn     = document.getElementById('reload-page-btn');

    reloadWindowModal.show();

    reloadPageBtn.addEventListener('click', function () {
        location.reload(true);
    });
}

export {
    startMessagePreviewEventSource,
    startActiveNotificationChannelEventSource,
    startConversationHelperEventSource
};