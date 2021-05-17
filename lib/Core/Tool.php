<?php

namespace Mmx\Core;
class Tool
{
    public static function log($module, $log, $dir, $tag = 'LOG')
    {
        $dir  = "{$dir}/{$module}";
        $name = date('Y-m-d') . '.log';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return false;
            }
        }
        $log = is_scalar($log) ? (string)$log : json_encode($log, JSON_UNESCAPED_UNICODE);

        if (file_exists($path = "{$dir}/{$name}")) {
            return file_put_contents($path, date('H:i:s') . " [{$tag}] {$log}\n", FILE_APPEND | LOCK_EX);
        } else {
            return file_put_contents($path, date('H:i:s') . " [{$tag}] {$log}\n", LOCK_EX);
        }
    }
}


