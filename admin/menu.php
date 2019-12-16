<?php

$menu = [
    [
        'parent_menu' => 'global_menu_services',
        'sort'        => 1000,
        'text'        => 'Yandex.Turbo',
        'title'       => 'Yandex.Turbo',
        'items_id'    => 'menu_references',
        'icon'        => 'fileman_menu_icon',
        'items'       => [
            [
                'text'     => 'Яндекс страницы',
                'url'      => 'yandex_turbo_list.php',
                'more_url' => ['yandex_turbo_item.php'],
                'title'    => 'Яндекс страницы',
            ],
            [
                'text'  => 'Настройки',
                'url'   => 'yandex_turbo_settings.php',
                'title' => 'Настройки',
            ],
        ],
    ],
];

return $menu;
