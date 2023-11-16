require('./app.js');

var eventSource = null;

document.addEventListener('turbo:load', function  () {
    const scriptTag    = document.getElementById('mercure-url');
    const msgScriptTag = document.getElementById('message-url');

    if (eventSource != null) {
        eventSource.close();
    }

    if (scriptTag != null) {
        const url = JSON.parse(scriptTag.textContent);
        console.log(url);

        eventSource = new EventSource(url, {
            withCredentials: true
        });

        eventSource.onmessage = event => {
            const data = JSON.parse(event.data);
            processMessage(
                data['message'],
                msgScriptTag.textContent
            );
        }
    }
});

async function processMessage(data, msgUrl) {
    const resultTarget = document.getElementById('messages');

    try {
        const response = await fetch(msgUrl, {
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
