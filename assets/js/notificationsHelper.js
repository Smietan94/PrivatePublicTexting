function startActiveNotificationChannelEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data['messagePreview']) {
            processMessagePreview(
                data['messagePreview'],
                '/chats/messagePreview'
            );
        }

        if (data['anotherDataSet']) {
            console.log('anotherDataSet');
        }
    };

    // todo receiving message preview

    return eventSource;
}

function startMessagePreviewEventSource(url, msgPreviewUrl) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        processMessagePreview(
            data['messagePreview'],
            msgPreviewUrl
        );

    }

    return eventSource;
}

async function processMessagePreview(data, msgPreviewUrl) {
    let messagePreviewElement = document.getElementById(`conversation-${data['conversationId']}-last-message`);

    if (messagePreviewElement) {
        try {
            const response = await fetch(msgPreviewUrl, {
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

function sortConversationLabels(conversationId) {
    let conversationsListDiv    = document.getElementById('conversations-list');
    let conversationLabelToMove = document.getElementById(`conversation-${ conversationId }`);
    let conversationLabelsArray = conversationsListDiv.querySelectorAll('a');

    if (conversationLabelToMove != conversationLabelsArray[0]) {
        conversationsListDiv.innerHTML = "";
        conversationsListDiv.append(conversationLabelToMove);
        conversationsListDiv.innerHTML += '<hr class="hr w-100"/>';

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

export {
    startMessagePreviewEventSource,
    startActiveNotificationChannelEventSource,
};