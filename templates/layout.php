<?php
/**
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather by Cities</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; max-width: 1100px; margin: 0 auto; padding: 1rem; background: #f5f7fa; color: #2c3e50; }
        form { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); margin-bottom: 1.5rem; }
        label { font-weight: bold; display: block; margin-bottom: .3rem; }
        input { width: 180px; padding: .4rem .6rem; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #3498db; color: #fff; border: none; padding: .5rem 1.2rem; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2980b9; }
        .errors { background: #ffe0e0; border: 1px solid #e74c3c; border-radius: 6px; padding: .8rem 1rem; margin-bottom: 1rem; color: #c0392b; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th { background: #2c3e50; color: #fff; padding: .7rem 1rem; text-align: left; }
        td { padding: .6rem 1rem; border-bottom: 1px solid #eee; vertical-align: middle; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:last-child td { border-bottom: none; }
        tr.missing td { color: #999; font-style: italic; }
        .icon { width: 40px; height: 40px; vertical-align: middle; }
        .zip { color: #999; }
        .form-row { display: flex; gap: 1.5rem; align-items: flex-end; flex-wrap: wrap; }
    </style>
</head>
<body>
<?= $content ?>
</body>
</html>
