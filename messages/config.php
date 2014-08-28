<?php

return [
    'sourcePath' => dirname(__DIR__),
    'languages' => ['en', 'ru'],
    'translator' => 'Widget::t',
    'sort' => false,
    'removeUnused' => true,
    'only' => ['*.php'],
    'except' => [
        '.svn',
        '.git',
        '.gitignore',
        '.gitkeep',
        '.hgignore',
        '.hgkeep',
        '/messages',
    ],
    'format' => 'php',
    'messagePath' => __DIR__,
    'overwrite' => true
];
