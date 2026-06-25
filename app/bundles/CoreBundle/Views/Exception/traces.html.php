<?php

$formatArgs = function ($args) use (&$formatArgs) {
    $result = [];
    foreach ($args as $key => $item) {
        if (is_array($item) && isset($item[0]) && is_string($item[0]) && count($item) === 2) {
            if ($item[0] === 'object') {
                $parts          = explode('\\', $item[1]);
                $short          = array_pop($parts);
                $formattedValue = sprintf('<em>object</em>(<abbr title="%s">%s</abbr>)', $item[1], $short);
            } elseif ($item[0] === 'array') {
                $formattedValue = sprintf('<em>array</em>(%s)', is_array($item[1]) ? $formatArgs($item[1]) : $item[1]);
            } elseif ($item[0] === 'string') {
                $formattedValue = sprintf("'%s'", htmlspecialchars($item[1]));
            } elseif ($item[0] === 'null') {
                $formattedValue = '<em>null</em>';
            } elseif ($item[0] === 'boolean') {
                $formattedValue = '<em>'.strtolower(var_export($item[1], true)).'</em>';
            } elseif ($item[0] === 'resource') {
                $formattedValue = '<em>resource</em>';
            } else {
                $formattedValue = str_replace("\n", '', var_export(htmlspecialchars((string) $item[1]), true));
            }
        } elseif (is_object($item)) {
            $formattedValue = $item::class;
        } elseif (is_string($item)) {
            $formattedValue = '<em>'.htmlspecialchars($item).'</em>';
        } elseif (is_array($item)) {
            $formattedValue = sprintf('<em>array</em>(%s)', $formatArgs($item));
        } else {
            $formattedValue = '';
        }

        $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", $key, $formattedValue);
    }

    return implode(', ', $result);
};

echo "<ol>\n";
$root = realpath(__DIR__.'/../../../../../');

foreach ($traces as $trace) {
    echo '<li class="pt-3 break-word">';
    if (isset($trace['file'])) {
        $trace['file'] = str_replace($root, '', $trace['file']);
        echo "<strong>{$trace['file']}";
        if ($trace['line']) {
            echo ':'.$trace['line'];
        }
        echo '</strong> at ';
    }
    if (!empty($trace['function'])) {
        if (isset($trace['class'])) {
            echo "{$trace['class']} {$trace['type']} ";
        }
        echo " {$trace['function']} ( ";
        if (!empty($trace['args'])) {
            echo $formatArgs($trace['args']);
        }
        echo ' )';
    }
    echo '</li>';
}
echo "</ol>\n";
