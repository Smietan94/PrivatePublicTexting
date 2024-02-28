import { manageEventSource } from "./chatService";
import { PHP_ROUTE_PATH }    from "../constants";

let friendRemoveEventSource = null;

function removeFriend(rmFriendBtn) {
    if (rmFriendBtn.dataset.listener === 'true') {
        return;
    }

    const handleRemoveEventSource = function () {
        const friendRemoveScriptTag = document.getElementById(`mercure-friend-${ rmFriendBtn.value }-remove`);

        if (friendRemoveScriptTag) {
            const friendRemoveNotificationUrl = JSON.parse(friendRemoveScriptTag.textContent);
            const friendRemoveTopic           = friendRemoveNotificationUrl.split("?")[1];

            friendRemoveEventSource = manageEventSource(
                startFriendRemoveEventSource,
                friendRemoveEventSource,
                friendRemoveTopic,
                friendRemoveNotificationUrl
            );
        }
    }

    rmFriendBtn.addEventListener('click', function(event) {
        handleRemoveEventSource();
        let friendUsername = document.getElementById(`username-${ rmFriendBtn.value }`);
        var confirmation   = confirm(`Do You want to remove ${ friendUsername.innerHTML } from friends list?`);

        if (!confirmation) {
            friendRemoveEventSource.close();
            event.preventDefault();
        }
    });

    rmFriendBtn.dataset.listener = 'true';
};

function startFriendRemoveEventSource(url) {
    let eventSource = new EventSource(url, {
        withCredentials: true
    });

    eventSource.onmessage = event => {
        eventSource.close();
    };

    return eventSource;
};

async function reloadFriendCardDiv() {
    let friendsListDiv = document.getElementById('friends-list');

    let response = await fetch(PHP_ROUTE_PATH.RELOAD_FRIENDS_LIST, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: true})
    });

    friendsListDiv.innerHTML = await response.text();
}

async function processRequestsList(elementId, url) {
    let friendsRequestsList = document.getElementById(elementId);

    let response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({data: true})
    });

    friendsRequestsList.innerHTML = await response.text();
}

function sortFriendsCards() {
    let friendsCardsListDiv = document.getElementById('friends-cards');
    let friendsCardsArray   = friendsCardsListDiv.querySelectorAll('.friend-card-row');

    friendsCardsListDiv.innerHTML = "";

    friendsCardsArray.forEach(element => {
        friendsCardsListDiv.append(element);
    });
}

export {
    removeFriend,
    reloadFriendCardDiv,
    sortFriendsCards,
    processRequestsList
};