import { manageEventSource } from "./chatService";

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
        console.log(friendRemoveEventSource);
        handleRemoveEventSource();
        let friendUsername = document.getElementById(`username-${ rmFriendBtn.value }`);
        var confirmation   = confirm(`Do You want to remove ${ friendUsername.innerHTML } from friends list?`);

        if (!confirmation) {
            console.log(friendRemoveEventSource);
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
        console.log(eventSource);
    };

    return eventSource;
};

function removeFriendCard(removingUserId) {
    const userCardDiv = document.getElementById(`friend-${ removingUserId }`);

    if (userCardDiv) {
        userCardDiv.remove();
        sortFriendsCards();
    }
}

function sortFriendsCards() {
    let friendsCardsListDiv = document.getElementById('friends-cards');
    let friendsCardsArray   = friendsCardsListDiv.querySelectorAll('.friend-card-row');

    friendsCardsListDiv.innerHTML = "";

    friendsCardsArray.forEach(element => {
        friendsCardsListDiv.append(element);
    })
}

export {
    removeFriend,
    removeFriendCard,
    sortFriendsCards
};