<?php

declare(strict_types=1);

namespace App\Entity\Constants;

class RouteName
{
    // AUTH CONTROLLER
    public const APP_LOGIN    = 'app_login';
    public const APP_REGISTER = 'app_register';
    public const APP_LOGOUT   = 'app_logout';

    // CHAT CONTROLLER
    public const APP_HOME                       = 'app_home';
    public const APP_CHAT                       = 'app_chat';
    public const APP_START_PRIVATE_CONVERSATION = 'app_start_private_conversation';

    // CHAT GROUPS CONTROLLER
    public const APP_CHAT_GROUPS              = 'app_chat_groups';
    public const APP_CHAT_GROUP               = 'app_chat_group';
    public const APP_CHAT_GROUP_CREATE        = 'app_chat_group_create';
    public const APP_CHAT_GROUP_CHANGE_NAME   = 'app_chat_group_change_name';
    public const APP_CHAT_REMOVE_CONVERSATION = 'app_chat_remove_conversation';

    // CHAT MEMBERS CONTROLLER
    public const APP_CHAT_GROUP_REMOVE_FROM_CONVERSATION = 'app_chat_group_remove_from_conversation';
    public const APP_CHAT_GROUP_LEAVE_CONVERSATION       = 'app_chat_group_leave_conversation';
    public const APP_CHAT_GROUP_ADD_MEMBERS              = 'app_chat_group_add_members';

    // CHAT COMPONENT CONTROLLER
    public const APP_CHAT_SEARCH    = 'app_chat_search';
    public const APP_HANDLE_MESSAGE = 'app_handle_message';

    // FILE CONTROLLER
    public const APP_GET_SENT_IMG = 'get_sent_img_app';

    // FRIEND CONTROLLER
    public const APP_FRIENDS_LIST        = 'app_friends_list';
    public const APP_RELOAD_FRIENDS_LIST = 'app_friends_list_reload';
    public const APP_FRIENDS_REMOVE      = 'app_friends_remove';

    // FRIEND REQUEST CONTROLLER
    public const APP_FRIENDS_REQUESTS          = 'app_friends_requests';
    public const APP_RECEIVED_FRIENDS_REQUESTS = 'app_received_friends_requests';
    public const APP_SENT_FRIENDS_REQUESTS     = 'app_sent_friends_requests';
    public const APP_SEND_FRIEND_REQUEST       = 'app_send_friend_request';
    public const APP_ACCEPT_FRIEND_REQUEST     = 'app_accept_friend_request';
    public const APP_DENY_FRIEND_REQUEST       = 'app_deny_friend_request';
    public const APP_CANCEL_FRIEND_REQUEST     = 'app_cancel_friend_request';

    // NOTIFICATIONS CONTROLLER
    public const APP_CHAT_MESSAGE_PREVIEW             = 'app_chat_message_preview';
    public const APP_CHAT_GROUP_CONVERSATION_LABEL    = 'app_chat_group_conversation_label';
    public const APP_CHAT_PROCESS_EVENT_SOURCE_TAG    = 'app_chat_process_event_source_tag';
    public const APP_CHAT_REDEIRECT_REMOVED_USER      = 'app_chat_redeirect_removed_user';
    public const APP_CHAT_PEOCESS_CONVERSATION_REMOVE = 'app_chat_peocess_conversation_remove';
    public const APP_CHAT_UPDATE_MEMBERS_LIST         = 'app_chat_update_members_list';
    public const APP_SET_ACTIVITY_STATUS              = 'app_set_activity_status';
    public const APP_GET_UNSEEN_NOTIFICATIONS_NUMBER  = 'app_get_unseen_notifications_number';
    public const APP_RELOAD_NOTIFICATIONS_MODAL       = 'app_reload_notifications_modal';
    public const APP_SET_NOTIFICATION_DISPLAY_STATUS  = 'app_set_notification_display_status';
    public const APP_RENDER_NOTIFICATIONS_MODAL       = 'app_render_notifications_modal';

    // SEARCH CONTROLLER
    public const APP_SEARCH_USERS = 'app_search_users';
}