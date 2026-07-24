<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 公開済みの対象記事が公開・更新・非公開になったときだけ、コーポレートサイトへ送信します。
 */
function group_news_push_post(
    string $new_status,
    string $old_status,
    WP_Post $post
): void {
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
        return;
    }

    $config = group_news_get_site_config();

    if (
        empty($config['site']) ||
        $config['site'] === 'unknown' ||
        !in_array($post->post_type, $config['post_types'], true)
    ) {
        return;
    }

    // 新規下書きなど、まだ公開されたことのない記事は同期しません。
    if ($new_status !== 'publish' && $old_status !== 'publish') {
        return;
    }

    if (
        !defined('PS_GROUP_NEWS_SYNC_CORPORATE_ENDPOINT') ||
        !defined('PS_GROUP_NEWS_SYNC_CORPORATE_TOKEN')
    ) {
        return;
    }

    $item = group_news_build_item($post, $config);
    $item['status'] = $new_status;

    $response = wp_remote_post(
        PS_GROUP_NEWS_SYNC_CORPORATE_ENDPOINT,
        [
            'timeout' => 8,
            'headers' => [
                'Content-Type'          => 'application/json; charset=utf-8',
                'Accept'                => 'application/json',
                'X-PS-Group-News-Token' => PS_GROUP_NEWS_SYNC_CORPORATE_TOKEN,
            ],
            'body'    => wp_json_encode($item),
        ]
    );

    if (is_wp_error($response)) {
        error_log(
            sprintf(
                '[Group News Sync] Push failed for %s: %s',
                $item['external_id'],
                $response->get_error_message()
            )
        );
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300) {
        error_log(
            sprintf(
                '[Group News Sync] Push failed for %s: HTTP %d',
                $item['external_id'],
                $status_code
            )
        );
    }
}
add_action('transition_post_status', 'group_news_push_post', 10, 3);
