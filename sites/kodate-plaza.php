<?php

return [
    'site'       => 'kodate-plaza',
    'post_types' => [
        'post',
        'events',
        'estate',
        'relax_lp',
        'news',
    ],

    'build_item' => function (array $item, WP_Post $post): array {
        $item['member_only'] = false;

        if ($post->post_type === 'estate') {
            $blocks = parse_blocks($post->post_content);
            $blocks_data = $blocks[0]['attrs']['data'] ?? [];

            $item['member_only'] = !empty($blocks_data['member_only']);
        }

        if ($post->post_type === 'relax_lp') {
            $blocks = parse_blocks($post->post_content);
            $blocks_data = $blocks[0]['attrs']['data'] ?? [];
            $concept = $blocks_data['concept'] ?? '';

            if ($concept !== '') {
                $item['title'] .= ' -' . $concept . '- をご紹介！';
            }
        }
        return $item;
    },
];
