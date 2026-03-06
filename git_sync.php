<?php

$repo_path = '/home/bersama4/ppob.bersamakita.my.id';

chdir($repo_path);

$output = shell_exec('git pull origin main 2>&1');

echo "<pre>";
echo $output;
echo "</pre>";
