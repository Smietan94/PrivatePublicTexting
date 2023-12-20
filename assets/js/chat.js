require('./app.js');

let activeEventSource = null;

document.addEventListener('turbo:load', function  () {
    const scriptTag              = document.getElementById('mercure-url');
    const msgScriptTag           = document.getElementById('message-url');
    const rmConversationUserBtns = document.querySelectorAll('.rm-user-btn');
    const leaveGroupBtn          = document.querySelector('.leave-group-btn');

    if (rmConversationUserBtns) {
        rmConversationUserBtns.forEach(button => {
            button.addEventListener('click', function(event) {
                confirmMemberRemove(button, event);
            }) 
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

        if (!activeEventSource) {
            activeEventSource = startEventSource(url, msgScriptTag);
            console.log('connection established');
        } else if (!checkLastEventSource(topic, activeEventSource) && activeEventSource) {
            activeEventSource.close();
            console.log('last event source closed')
            activeEventSource = startEventSource(url, msgScriptTag);
            console.log('new connection established')
        } else {
            console.log('connection remains unchanged');
        }
    }
});

function startEventSource(url, msgScriptTag) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        const data = JSON.parse(event.data);

        processMessage(
            data['message'],
            msgScriptTag.textContent
        );
    }

    return eventSource;
}

function checkLastEventSource(topic, activeEventSource) {
    let activeTopic = activeEventSource.url.split("?")[1];

    return activeTopic == topic;
}

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

function confirmMemberRemove(button, event) {
    var memberId     = button.getAttribute('data-member-id');
    var username     = document.querySelector(`.username_${ memberId }`).innerHTML;
    var confirmation = confirm(`Are You sure you want to remove ${ username }?`);

    if (!confirmation) {
        console.log(username);
        event.preventDefault();
    }
}

