<?php

/**
 * Plugin Name: PLAZA SELECT GROUP NEWS SYNC
 * Plugin URI: https://github.com/haruki0705/ps_group_news_sync
 * Description: プラザセレクトグループの更新情報をREST APIとして公開します。
 * Version: 0.8.1
 * Author: hareweb*
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'site-config.php';
require_once plugin_dir_path(__FILE__) . 'rest-api.php';


require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/haruki0705/ps_group_news_sync',
    __FILE__,
    'group-news-sync'
);
if (defined('PS_GROUP_NEWS_SYNC_GITHUB_TOKEN')) {
    $updateChecker->setAuthentication(
        PS_GROUP_NEWS_SYNC_GITHUB_TOKEN
    );
}
