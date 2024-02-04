function startEventSource(url) {

    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        if (data['messageId']) {
            processMessage(
                data['messageId']
            );
        }
    }

    return eventSource;
}

function checkLastEventSource(topic, activeEventSource) {
    let activeTopic = activeEventSource.url.split("?")[1];

    return activeTopic == topic;
}

async function processMessage(messageId) {
    const resultTarget = document.getElementById('messages');

    try {
        const response = await fetch('/chats/handleMessage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({data: messageId})
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

function processEnterSendMessage() {
    let messageFormTextarea = document.querySelector('textarea[name="message[message]"]');

    if (messageFormTextarea) {
        messageFormTextarea.addEventListener(
            'keypress',
            msgFormTextareaEventListenerFunction
        );
    }
}

function msgFormTextareaEventListenerFunction(event) {
    let messageFormTextarea = document.querySelector('textarea[name="message[message]"]');
    messageFormTextarea.setCustomValidity('');
    if (event.keyCode == 13 && !event.shiftKey) {
        event.preventDefault();
        let messageForm = document.querySelector('form[name="message"]');
        if (messageFormTextarea.value == '') {
            messageFormTextarea.setCustomValidity('fill in this field.');
        } else {
            messageFormTextarea.setCustomValidity('');
            messageForm.requestSubmit();
            messageFormTextarea.removeEventListener(
                'keypress',
                msgFormTextareaEventListenerFunction
            );
        }
        messageFormTextarea.reportValidity();
    }
}

export {
    startEventSource,
    checkLastEventSource,
    processMessage,
    confirmMemberRemove,
    manageEventSource,
    processEnterSendMessage
}