<?php

function group_news_get_site_config(): array
{
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $host = preg_replace('/^www\./', '', strtolower($host));

    $config_files = [
        'hajimete-fudosan.jp' => __DIR__ . '/sites/hajimetefudousan.php',
        'relax-plaza.jp'      => __DIR__ . '/sites/relax.php',
        'kodate-plaza.jp'     => __DIR__ . '/sites/kodate-plaza.php',
    ];

    if (!isset($config_files[$host])) {
        return [
            'site'       => 'unknown',
            'post_types' => ['post'],
        ];
    }

    return require $config_files[$host];
}
