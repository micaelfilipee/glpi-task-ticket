<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

if (!function_exists('plugin_autoassigninternal_log')) {
    function plugin_autoassigninternal_log($message) {
        $prefix = '[AutoAssignInternal] ';
        $line   = $prefix . $message;

        $logDir = defined('GLPI_LOG_DIR') ? GLPI_LOG_DIR : GLPI_ROOT . '/files/_log';
        $logDir = rtrim($logDir, DIRECTORY_SEPARATOR);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'autoassigninternal.log';

        $sizeBefore = null;
        if (file_exists($logFile)) {
            clearstatcache(true, $logFile);
            $sizeBefore = filesize($logFile);
        }

        $wroteWithToolbox = false;
        if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
            Toolbox::logInFile('autoassigninternal', $line);

            clearstatcache(true, $logFile);
            if (file_exists($logFile)) {
                $sizeAfter = filesize($logFile);
                $wroteWithToolbox = ($sizeBefore === null && $sizeAfter > 0)
                    || ($sizeBefore !== null && $sizeAfter !== $sizeBefore);
            }
        }

        if ($wroteWithToolbox) {
            return;
        }

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        if (is_dir($logDir) && is_writable($logDir)) {
            $timestampedLine = sprintf('%s %s%s', date('Y-m-d H:i:s'), $line, PHP_EOL);
            file_put_contents($logFile, $timestampedLine, FILE_APPEND);
        } elseif (function_exists('error_log')) {
            error_log($line);
        }
    }
}
