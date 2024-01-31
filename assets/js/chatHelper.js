function startEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        processMessage(
            data['message']
        );
    }

    return eventSource;
}

function checkLastEventSource(topic, activeEventSource) {
    let activeTopic = activeEventSource.url.split("?")[1];

    return activeTopic == topic;
}

async function processMessage(data) {
    const resultTarget = document.getElementById('messages');

    try {
        const response = await fetch('/chats/handleMessage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({data: data})
        });

        if (!response.ok) {
            throw new Error('Failed to send message to the server');
        }

        resultTarget.innerHTML += await response.text();

    } catch (error) {
        console.log('Error sending message to server: ', error);
    }
}

function confirmMemberRemove(button, event) {
    var memberId     = button.getAttribute('data-member-id');
    var username     = document.querySelector(`.username_${ memberId }`).innerHTML;
    var confirmation = confirm(`Are You sure you want to remove ${ username }?`);

    if (!confirmation) {
        console.log(username);
        event.preventDefault();
    }
}

function manageEventSource(eventSourceFunction, eventSource, topic, url) {
    if (!eventSource) {
        eventSource = eventSourceFunction(url);
    } else if (!checkLastEventSource(topic, eventSource) && eventSource) {
        eventSource.close();
        eventSource = eventSourceFunction(url);
    }

    return eventSource;
}

export {
    startEventSource,
    checkLastEventSource,
    processMessage,
    confirmMemberRemove,
    manageEventSource
}