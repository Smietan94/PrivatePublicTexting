function handleOffcanvasButtons(tooltipList) {
    const navbarOffcanvas  = document.getElementById('navbarSupportedContent');
    const navChats         = document.getElementById('nav-chats');
    const navGroupChats    = document.getElementById('nav-group-chats');
    const navFriends       = document.getElementById('nav-friends');
    const navSearchFriends = document.getElementById('nav-search-friends');


    const handleShowOffcanvas = () => navbarOffcanvas.addEventListener('show.bs.offcanvas', function () {
        navChats.innerHTML         = 'Chats';
        navGroupChats.innerHTML    = 'Group Chats';
        navFriends.innerHTML       = 'Friends';
        navSearchFriends.innerHTML = 'Search Friends';

        tooltipList.forEach(tooltip => {
            tooltip.disable();
        });
    });

    const handleHiddenOffcanvas = () => navbarOffcanvas.addEventListener('hidden.bs.offcanvas', function () {
        navChats.innerHTML         = '';
        navGroupChats.innerHTML    = '';
        navFriends.innerHTML       = '';
        navSearchFriends.innerHTML = '';

        tooltipList.forEach(tooltip => {
            tooltip.enable();
        });
    });

    if (navbarOffcanvas) {
        handleShowOffcanvas();
        handleHiddenOffcanvas();
    }
};

export {
    handleOffcanvasButtons
};