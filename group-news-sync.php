<?php

/**
 * Plugin Name: PLAZA SELECT GROUP NEWS SYNC
 * Description: プラザセレクトグループの更新情報をREST APIとして公開します。
 * Version: 1.0.0
 * Author: hareweb*
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/rest-api.php';
