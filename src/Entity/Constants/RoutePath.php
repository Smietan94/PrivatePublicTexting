<?php

declare(strict_types=1);

namespace App\Entity\Constants;

class RoutePath
{
    // AUTH CONTROLLER
    public const LOGIN    = '/login';
    public const REGISTER = '/register';
    public const LOGOUT   = '/logout';

    // CHAT CONTROLLER
    public const HOME                    = ['/', '/home', '/chats/solo'];
    public const SOLO                    = '/chats/solo/{conversationId}';
    public const START_SOLO_CONVERSATION = '/chats/solo/startConversation';

    // CHAT GROUPS CONTROLLER
    public const GROUPS                   = '/chats/groups/';
    public const GROUP                    = '/chats/groups/{conversationId}';
    public const START_GROUP_CONVERSATION = '/chats/groups/startGroupConversation';
    public const CHANGE_CONVERSATION_NAME = '/chats/groups/changeConversationName';
    public const REMOVE_CONVERSATION      = '/chats/groups/removeConversation';

    // CHAT MEMBERS CONTROLLER
    public const REMOVE_FROM_CONVERSATION = '/chats/groups/removeFromConversation';
    public const LEAVE_CONVERSATION       = '/chats/groups/leaveConversation';
    public const ADD_MEMBERS              = '/chats/groups/addMembers';

    // CHAT COMPONENT CONTROLLER
    public const CHAT_SEARCH    = '/chats/search';
    public const HANDLE_MESSAGE = '/chats/handleMessage/{conversationId}';

    // FILE CONTROLLER
    public const GET_IMG = '/getImg/{attachmentId}';

    // FRIEND CONTROLLER
    public const FRIENDS             = '/friends';
    public const RELOAD_FRIENDS_LIST = '/friends/reloadList';
    public const REMOVE_FRIEND       = '/friends/remove';

    // FRIEND REQUESTS CONTROLLER
    public const FRIENDS_REQUEST           = '/friendsRequests';
    public const RECEIVED_FRIENDS_REQUESTS = '/friendsRequests/received';
    public const SENT_FRIENDS_REQUESTS     = '/friendsRequests/sent';
    public const SEND_FRIENDS_REQUEST      = '/friendRequests/sendFriendRequest';
    public const ACCEPT_FRIEND_REQUEST     = '/friendRequests/accept';
    public const DENY_FRIEND_REQUEST       = '/friendRequests/deny';
    public const CANCEL_FRIEND_REQUEST     = '/friendRequests/cancel';

    // NOTIFICATION CONTROLLER
    public const MESSAGE_PREVIEW                 = '/chats/messagePreview';
    public const PROCESS_CONVERSATION_LABEL      = '/chats/processConversationLabel';
    public const PROCESS_EVENT_SOURCE_SCRIPT_TAG = '/chats/groups/processEventSourceScriptTag';
    public const REDIRECT_REMOVED_USER           = '/chats/redirectRemovedUser';
    public const PROCESS_CONVERSATION_REMOVE     = '/chats/processConversationRemove';
    public const UPDATE_MEMBERS_LIST             = '/chats/groups/updateMembersList';
    public const SET_ACTIVITY_STATUS             = '/setActivityStatus';
    public const GET_UNSEEN_NOTIFICATIONS_NUMBER = '/notifications/getUnseenNotificationsNumber';
    public const RELOAD_NOTIFICATIONS_MODAL      = '/notifications/reloadNotificationsModal';

    // SEARCH CONTROLLER
    public const FRIENDS_SEARCH = '/search';

    // ADDITIONAL PATHS
    public const EMPTY_PATH = '#';
}