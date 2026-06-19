<?php

declare(strict_types=1);

use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<FlagPresenter> $flags
 * @var bool $isWritable
 * @var string $createUrl
 * @var string $gridHtml
 */

$this->setTitle('Feature flags');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Feature flags</h1>
        <?php if ($isWritable): ?>
            <a href="<?= Html::encode($createUrl) ?>" class="btn btn-primary btn-sm">New flag</a>
        <?php else: ?>
            <span class="badge text-bg-warning">Read-only provider</span>
        <?php endif ?>
    </div>
    <?= $gridHtml ?>
</div>
