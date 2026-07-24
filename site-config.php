<?php

function group_news_get_site_config(): array
{
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $host = preg_replace('/^www\./', '', strtolower($host));

    $site_config_files = [
        'hajimetefudousan' => __DIR__ . '/sites/hajimetefudousan.php',
        'relax'            => __DIR__ . '/sites/relax.php',
        'kodate-plaza'     => __DIR__ . '/sites/kodate-plaza.php',
    ];

    $config_files = [
        'hajimete-fudosan.jp' => $site_config_files['hajimetefudousan'],
        'relax-plaza.jp'      => $site_config_files['relax'],
        'kodate-plaza.jp'     => $site_config_files['kodate-plaza'],
    ];

    $site_id = defined('PS_GROUP_NEWS_SYNC_SITE_ID')
        ? sanitize_key(PS_GROUP_NEWS_SYNC_SITE_ID)
        : '';

    if ($site_id !== '' && isset($site_config_files[$site_id])) {
        return require $site_config_files[$site_id];
    }

    if (!isset($config_files[$host])) {
        return [
            'site'       => 'unknown',
            'post_types' => ['post'],
        ];
    }

    return require $config_files[$host];
}
