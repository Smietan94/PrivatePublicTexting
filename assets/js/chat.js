require('./app.js');

import { startEventSource, manageEventSource, confirmMemberRemove, processEnterSendMessage } from './chatHelper';

let activeChatEventSource = null;

document.addEventListener('turbo:load', function () {
    const scriptTag              = document.getElementById('mercure-url');
    const rmConversationUserBtns = document.querySelectorAll('.rm-user-btn');
    const leaveGroupBtn          = document.querySelector('.leave-group-btn');
    const removeConversationBtn  = document.querySelector('.remove-conversation-btn');

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

    processEnterSendMessage();
});

document.addEventListener('turbo:frame-render', function () {
    processEnterSendMessage();
});



