<?php

use Tempest\Router\Exceptions\HttpProductionErrorHandler;

/** @var HttpProductionErrorHandler $this */
?>

<html>
<head>
    <title>Nope</title>

    <style>
        body {
            font-size: 14px;
        }

        h1 {
            font-family: "JetBrains Mono", Monaco, monospace;
            padding: .3em .5rem;
            margin-bottom: .3em;
        }

        .trace {
            font-family: "JetBrains Mono", Monaco, monospace;
            display: grid;
            gap: .5em;
            margin-top: .5em;
        }

        .trace-item {
            font-family: "JetBrains Mono", Monaco, monospace;
            background-color: #f3f3f3;
            padding: .5em 1em;
        }

        pre, code {
            color: #000;
            background-color: #f3f3f3;
            padding: 1em 0;
            line-height: 1.8em;
            margin: 0;
            overflow-x: scroll;
        }

        .gutter {
            font-size: .8em;
            color: #222;
            padding: 0 1ch;
            display: inline-block;
            margin-right: 2ch;
        }

        .gutter.selected {
            font-weight: bold;
            background-color: red;
            color: #fff;
        }

        .error-line {
            background-color: #ff000022;
            display: inline-block;
        }

        .hl-keyword {
            color: #4285F4;
        }

        .hl-property {
            color: #34A853;
        }

        .hl-attribute {
            font-style: italic;
        }

        .hl-type {
            color: #EA4334;
        }

        .hl-generic {
            color: #9d3af6;
        }

        .hl-value {
            color: #000;
        }

        .hl-variable {
            color: #000;
        }

        .hl-comment {
            color: #888888;
        }

        .hl-blur {
            filter: blur(2px);
        }

        .hl-strong {
            font-weight: bold;
        }

        .hl-em {
            font-style: italic;
        }

        .hl-addition {
            display: inline-block;
            min-width: 100%;
            background-color: #00FF0033;
        }

        .hl-deletion {
            display: inline-block;
            min-width: 100%;
            background-color: #FF000022;
        }
    </style>
</head>
<body>

<h1><?= $this->throwable::class ?>: <?= $this->throwable->getMessage() ?></h1>

<div>
    <div class="trace-item">
        <?php
        $item = $this->throwable->getTrace()[0];
echo $this->highlighter->parse($item['class'] . '::' . $item['function'] . '()', 'php');
?>
    </div>
    <pre><?= $this->getCodeSample() ?></pre>
    <div class="trace-item">
        In
<!--        <a href="idea://--><?php //= $this->throwable->getFile()?><!--:--><?php //= $this->throwable->getLine()?><!--">-->
            <?= $this->throwable->getFile() ?>:<?= $this->throwable->getLine() ?>
<!--        </a>-->
    </div>
</div>

<div class="trace">
    <?php foreach ($this->throwable->getTrace() as $i => $item) { ?>
        <?php if ($i === 0) {
            continue;
        } ?>

        <div class="trace-item">
            <div>
                <?php
                    if (isset($item['class'])) {
                        echo $this->highlighter->parse($item['class'] . '::' . $item['function'] . '()', 'php');
                    }
        ?>
            </div>

            <?php
            if (isset($item['file'])) {
                $path = 'file:///' . $item['file'] . ':' . $item['line'];
                ?>
                <div>
                    in
<!--                    <a href="idea://--><?php //= $item['file']?><!--:--><?php //=$item['line']?><!--">-->
                        <?= $item['file']?>:<?= $item['line'] ?>
<!--                    </a>-->
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

</body>
</html>
