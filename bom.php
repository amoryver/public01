#!/usr/bin/env php
<?php
namespace Amoryver\Utils;
date_default_timezone_set('Asia/Tokyo');
if (! is_dir('vendor')) {
    exit("FATAL: Incomplete installation. 'composer install' may be required.".PHP_EOL);
}
require 'vendor/autoload.php';

// 指定された数のuuidを生成する。

// コマンド説明
$cli_description = [
    'description' => <<<EOT
    Investigate, Strip, or prepend the Byte Order Mark of files.
    --------
    - BOM is U+FEFF or 0xEF 0xBB 0xBF in UTF-8.
    - files over 1MB are out of processing.
    EOT,
    'version'     => '1.0.0',
];

// コマンド引数
$cli_arguments = [
    'inputs' => [
        'description' => 'a directory or a file from which input is read.',
        'help_name'   => 'input',
        'optional'    => true,
        'multiple'    => true,
    ],
];

// コマンドオプション
$cli_options = [
    'verbose' => [
        'description' => "show messages; -vv shows more messages",
        'long_name'   => '--verbose',
        'short_name'  => '-v',
        'default'     => 0,
        'action'      => 'Counter',
    ],
    'force' => [
        'description' => "overwrite existing file; -ff makes a backup",
        'long_name'   => '--force',
        'short_name'  => '-f',
        'default'     => 0,
        'action'      => 'Counter',
    ],
    'strip' => [
        'description' => 'strip BOM',
        'long_name'  => '--strip',
        'short_name'  => '-s',
        'default'     => false,
        'action'      => 'StoreTrue',
    ],
    'prepend' => [
        'description' => 'prepend BOM',
        'long_name'  => '--prepend',
        'short_name'  => '-p',
        'default'     => false,
        'action'      => 'StoreTrue',
    ],
];

$cli_main = function ($args, $options, $tmp_dir)
{
    // コマンドオプションを検査する。
    if ($options['strip'] && $options['prepend']) {
        throw new \Exception("ERROR: strip and prepend are exclusive options.");
    }

    // コマンド引数を処理する。
    foreach ($args['inputs'] as $input) {
        foreach (Common::file_list($input) as $in_path) {
            if (pow(1024, 2) < filesize($in_path)) {
                // 1MBよりも大きいファイルは無視する。
                Common::colored_echo(0, 33, "WARNING: [${in_path}] is too large.");
                continue;
            }
            if ($options['strip']) {
                $contents = file_get_contents($in_path);
                Common::save_file($in_path, preg_replace('/^\xEF\xBB\xBF/', '', $contents), $options);
            } else if ($options['prepend']) {
                $contents = file_get_contents($in_path);
                if (! preg_match('/^\xEF\xBB\xBF/', $contents)) {
                    Common::save_file($in_path, pack('C*', 0xEF, 0xBB, 0xBF).$contents, $options);
                }
            } else {
                $detected = preg_match('/^\xEF\xBB\xBF/', file_get_contents($in_path));
                echo "[${in_path}]: ".(($detected)?"yes":"no").PHP_EOL;
            }
        }
    }
};

(new Cli())
->set_cli_description($cli_description)
->set_cli_arguments($cli_arguments)
->set_cli_options($cli_options)
->set_cli_main($cli_main)
->run();
