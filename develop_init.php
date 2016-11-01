<?php

foreach (['pre-commit.sh', 'pre-push.sh'] as $hook) {
    chmod($hook, 0755);
    exec('ln -s -f ' . __DIR__ . '/' . $hook . ' ' . __DIR__ . '/.git/hooks/' . preg_replace('/\.sh$/', '', $hook));
}
