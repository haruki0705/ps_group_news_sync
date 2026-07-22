<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'group-news/v1',
        '/items',
        [
            'methods'             => 'GET',
            'callback'            => 'group_news_endpoint',
            'permission_callback' => '__return_true',
        ]
    );
});

function group_news_endpoint()
{
    $config = group_news_get_site_config();

    $query = new WP_Query([
        'post_type'           => $config['post_types'],
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }

        $item = [
            'id'          => $post->ID,
            'external_id' => $config['site'] . '_' . $post->ID,
            'date'        => get_the_date(DATE_ATOM, $post),
            'title'       => get_the_title($post),
            'url'         => get_permalink($post),
            'site'        => $config['site'],
        ];

        if (
            isset($config['build_item']) &&
            is_callable($config['build_item'])
        ) {
            $item = $config['build_item']($item, $post);
        }

        $items[] = $item;
    }

    return rest_ensure_response($items);
}
