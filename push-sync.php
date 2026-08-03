<?php

if (!defined('ABSPATH')) {
    exit;
}

const PS_GROUP_NEWS_SYNC_WEEKLY_SUMMARY_HOOK = 'ps_group_news_sync_weekly_summary';

/**
 * はじめて不動産のように、個別記事ではなく週次サマリーを送信するサイトか判定します。
 */
function group_news_uses_weekly_summary(array $config): bool
{
    return !empty($config['weekly_summary'])
        && is_array($config['weekly_summary']);
}

/**
 * 更新情報をコーポレートサイトへ送信します。
 */
function group_news_send_item(array $item): bool
{
    if (
        !defined('PS_GROUP_NEWS_SYNC_CORPORATE_ENDPOINT') ||
        !defined('PS_GROUP_NEWS_SYNC_CORPORATE_TOKEN')
    ) {
        return false;
    }

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
        error_log(sprintf(
            '[Group News Sync] Push failed for %s: %s',
            $item['external_id'] ?? 'unknown',
            $response->get_error_message()
        ));
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300) {
        error_log(sprintf(
            '[Group News Sync] Push failed for %s: HTTP %d',
            $item['external_id'] ?? 'unknown',
            $status_code
        ));
        return false;
    }

    return true;
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
        !in_array($post->post_type, $config['post_types'], true) ||
        group_news_uses_weekly_summary($config)
    ) {
        return;
    }

    // 新規下書きなど、まだ公開されたことのない記事は同期しません。
    if ($new_status !== 'publish' && $old_status !== 'publish') {
        return;
    }

    $item = group_news_build_item($post, $config);
    $item['status'] = $new_status;

    group_news_send_item($item);
}
add_action('transition_post_status', 'group_news_push_post', 10, 3);

/**
 * 次回の日曜正午（サイト設定タイムゾーン）を返します。
 */
function group_news_get_next_weekly_summary_timestamp(): int
{
    $now = new DateTimeImmutable('now', wp_timezone());

    if ((int) $now->format('w') === 0 && $now < $now->setTime(12, 0, 0)) {
        return $now->setTime(12, 0, 0)->getTimestamp();
    }

    $next = $now->modify('next sunday')->setTime(12, 0, 0);

    return $next->getTimestamp();
}

function group_news_schedule_weekly_summary(): void
{
    $config = group_news_get_site_config();

    if (!group_news_uses_weekly_summary($config)) {
        return;
    }

    if (!wp_next_scheduled(PS_GROUP_NEWS_SYNC_WEEKLY_SUMMARY_HOOK)) {
        wp_schedule_event(
            group_news_get_next_weekly_summary_timestamp(),
            'weekly',
            PS_GROUP_NEWS_SYNC_WEEKLY_SUMMARY_HOOK
        );
    }
}
add_action('init', 'group_news_schedule_weekly_summary');

function group_news_activate_weekly_summary(): void
{
    group_news_schedule_weekly_summary();
}
register_activation_hook(PS_GROUP_NEWS_SYNC_FILE, 'group_news_activate_weekly_summary');

function group_news_deactivate_weekly_summary(): void
{
    wp_clear_scheduled_hook(PS_GROUP_NEWS_SYNC_WEEKLY_SUMMARY_HOOK);
}
register_deactivation_hook(PS_GROUP_NEWS_SYNC_FILE, 'group_news_deactivate_weekly_summary');

/**
 * 実行日時に対応する、直近の完了した1週間（日曜〜土曜）の終了日曜正午を返します。
 */
function group_news_get_weekly_summary_date(DateTimeImmutable $now): DateTimeImmutable
{
    $today_noon = $now->setTime(12, 0, 0);

    if ((int) $now->format('w') === 0 && $now >= $today_noon) {
        return $today_noon;
    }

    return $now->modify('last sunday')->setTime(12, 0, 0);
}

/**
 * 前週に更新された対象記事を数え、1件以上あれば週次サマリーを送信します。
 */
function group_news_send_weekly_summary(): void
{
    $config = group_news_get_site_config();

    if (!group_news_uses_weekly_summary($config)) {
        return;
    }

    $summary_config = $config['weekly_summary'];
    $post_types = $summary_config['post_types'] ?? $config['post_types'];
    $url = esc_url_raw($summary_config['url'] ?? home_url('/news/'));

    if (!is_array($post_types) || empty($post_types) || $url === '') {
        return;
    }

    $summary_date = group_news_get_weekly_summary_date(
        new DateTimeImmutable('now', wp_timezone())
    );
    $period_start = $summary_date->modify('-7 days')->setTime(0, 0, 0);
    $period_end = $summary_date->modify('-1 day')->setTime(23, 59, 59);

    $query = new WP_Query([
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'date_query'     => [
            [
                'column'    => 'post_modified',
                'after'     => $period_start->format('Y-m-d H:i:s'),
                'before'    => $period_end->format('Y-m-d H:i:s'),
                'inclusive' => true,
            ],
        ],
    ]);

    $count = (int) $query->found_posts;

    if ($count === 0) {
        return;
    }

    $item = [
        'external_id' => sprintf(
            '%s_weekly_%s',
            $config['site'],
            $summary_date->format('Y-m-d')
        ),
        'date'        => $summary_date->format(DATE_ATOM),
        'title'       => sprintf('%dエリアの不動産情報を更新しました', $count),
        'url'         => $url,
        'site'        => $config['site'],
        'status'      => 'publish',
    ];

    group_news_send_item($item);
}
add_action(PS_GROUP_NEWS_SYNC_WEEKLY_SUMMARY_HOOK, 'group_news_send_weekly_summary');
