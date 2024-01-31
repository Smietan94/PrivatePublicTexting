require('./app.js');

import { startEventSource, manageEventSource, confirmMemberRemove } from './chatHelper';

let activeChatEventSource = null;

document.addEventListener('turbo:load', function  () {
    const scriptTag              = document.getElementById('mercure-url');
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

        activeChatEventSource = manageEventSource(
            startEventSource,
            activeChatEventSource,
            topic,
            url
        );
    }
});



