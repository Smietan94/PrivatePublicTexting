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
        console.log(data['messagePreview']);
        processMessagePreview(
            data['messagePreview'],
            msgPreviewUrl
        );
    }

    return eventSource;
}

async function processMessagePreview(data, msgPreviewUrl) {
    let messagePreviewElement = document.getElementById(`conversation-${data['conversationId']}-last-message`);

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

    } catch(error) {
        console.log('Error during processing message preview', error);
    }
}

export {
    startMessagePreviewEventSource,
    startActiveNotificationChannelEventSource,
};