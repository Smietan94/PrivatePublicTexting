const PHP_ROUTE_PATH = {
    // AUTH CONTROLLER
    LOGIN    : '/login',
    REGISTER : '/register',
    LOGOUT   : '/logout',

    // CHAT CONTROLLER
    HOME                    : ['/', '/home', '/chats/solo'],
    SOLO                    : '/chats/solo/', // {conversationId}
    START_SOLO_CONVERSATION : '/chats/solo/startConversation',

    // CHAT GROUPS CONTROLLER
    GROUPS                   : '/chats/groups/',
    GROUP                    : '/chats/groups/', // {conversationId}
    START_GROUP_CONVERSATION : '/chats/groups/startGroupConversation',
    CHANGE_CONVERSATION_NAME : '/chats/groups/changeConversationName',
    REMOVE_CONVERSATION      : '/chats/groups/removeConversation',

    // CHAT MEMBERS CONTROLLER
    REMOVE_FROM_CONVERSATION : '/chats/groups/removeFromConversation',
    LEAVE_CONVERSATION       : '/chats/groups/leaveConversation',
    ADD_MEMBERS              : '/chats/groups/addMembers',

    // CHAT COMPONENT CONTROLLER
    CHAT_SEARCH    : '/chats/search',
    HANDLE_MESSAGE : '/chats/handleMessage',

    // FILE CONTROLLER
    GET_IMG : '/getImg/', // {attachmentId}

    // FRIEND CONTROLLER
    FRIENDS       : '/friends',
    REMOVE_FRIEND : '/friends/remove',

    // FRIEND REQUESTS CONTROLLER
    FRIENDS_REQUEST        : '/friendsRequests',
    SEND_FRIENDS_REQUEST   : '/friendRequests/sendFriendRequest',
    ACCEPT_FRIEND_REQUEST  : '/friendRequests/accept',
    DECLINE_FRIEND_REQUEST : '/friendRequests/decline',
    CANCEL_FRIEND_REQUEST  : '/friendRequests/cancel',

    // NOTIFICATION CONTROLLER
    MESSAGE_PREVIEW                 : '/chats/messagePreview',
    PROCESS_CONVERSATION_LABEL      : '/chats/processConversationLabel',
    PROCESS_EVENT_SOURCE_SCRIPT_TAG : '/chats/groups/processEventSourceScriptTag',
    REDIRECT_REMOVED_USER           : '/chats/redirectRemovedUser',
    PROCESS_CONVERSATION_REMOVE     : '/chats/processConversationRemove',
    UPDATE_MEMBERS_LIST             : '/chats/groups/updateMembersList',
    SET_ACTIVITY_STATUS             : '/setActivityStatus',
    GET_UNSEEN_NOTIFICATIONS_NUMBER : '/notifications/getUnseenNotificationsNumber',

    // SEARCH CONTROLLER
    FRIENDS_SEARCH : '/search',
};

const PHP_ROUTE_NAME = {
    // AUTH CONTROLLER
    APP_LOGIN    : 'app_login',
    APP_REGISTER : 'app_register',
    APP_LOGOUT   : 'app_logout',

    // CHAT CONTROLLER
    APP_HOME                       : 'app_home',
    APP_CHAT                       : 'app_chat',
    APP_START_PRIVATE_CONVERSATION : 'app_start_private_conversation',

    // CHAT GROUPS CONTROLLER
    APP_CHAT_GROUPS              : 'app_chat_groups',
    APP_CHAT_GROUP               : 'app_chat_group',
    APP_CHAT_GROUP_CREATE        : 'app_chat_group_create',
    APP_CHAT_GROUP_CHANGE_NAME   : 'app_chat_group_change_name',
    APP_CHAT_REMOVE_CONVERSATION : 'app_chat_remove_conversation',

    // CHAT MEMBERS CONTROLLER
    APP_CHAT_GROUP_REMOVE_FROM_CONVERSATION : 'app_chat_group_remove_from_conversation',
    APP_CHAT_GROUP_LEAVE_CONVERSATION       : 'app_chat_group_leave_conversation',
    APP_CHAT_GROUP_ADD_MEMBERS              : 'app_chat_group_add_members',

    // CHAT COMPONENT CONTROLLER
    APP_CHAT_SEARCH    : 'app_chat_search',
    APP_HANDLE_MESSAGE : 'app_handle_message',

    // FILE CONTROLLER
    APP_GET_SENT_IMG : 'get_sent_img_app',

    // FRIEND CONTROLLER
    APP_FRIENDS_LIST   : 'app_friends_list',
    APP_FRIENDS_REMOVE : 'app_friends_remove',

    // FRIEND REQUEST CONTROLLER
    APP_FRIENDS_REQUESTS       : 'app_friends_requests',
    APP_SEND_FRIEND_REQUEST    : 'app_send_friend_request',
    APP_ACCEPT_FRIEND_REQUEST  : 'app_accept_friend_request',
    APP_DECLINE_FRIEND_REQUEST : 'app_decline_friend_request',
    APP_CANCEL_FRIEND_REQUEST  : 'app_cancel_friend_request',

    // NOTIFICATIONS CONTROLLER
    APP_CHAT_MESSAGE_PREVIEW             : 'app_chat_message_preview',
    APP_CHAT_GROUP_CONVERSATION_LABEL    : 'app_chat_group_conversation_label',
    APP_CHAT_PROCESS_EVENT_SOURCE_TAG    : 'app_chat_process_event_source_tag',
    APP_CHAT_REDEIRECT_REMOVED_USER      : 'app_chat_redeirect_removed_user',
    APP_CHAT_PEOCESS_CONVERSATION_REMOVE : 'app_chat_peocess_conversation_remove',
    APP_CHAT_UPDATE_MEMBERS_LIST         : 'app_chat_update_members_list',
    APP_SET_ACTIVITY_STATUS              : 'app_set_activity_status',
    APP_GET_UNSEEN_NOTIFICATIONS_NUMBER  : 'app_get_unseen_notifications_number',

    // SEARCH CONTROLLER
    APP_SEARCH_USERS : 'app_search_users',

    // ADDITIONAL PATHS
    EMPTY_PATH : '#',
};

const ACTIVITY_STATUS = {
    ACTIVE   : 0,
    INACTIVE : 2,
};

export {
    PHP_ROUTE_PATH,
    PHP_ROUTE_NAME,
    ACTIVITY_STATUS
};