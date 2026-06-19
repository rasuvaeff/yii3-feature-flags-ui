<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * Renders the flag list as a Bootstrap-styled GridView.
 *
 * Constructed with the application's DI container (which resolves the GridView
 * column renderers) and called from {@see ListFlagsResponder}. Rendering the
 * grid here — instead of via `GridView::widget()` inside the view template —
 * keeps the host application from having to bootstrap `WidgetFactory`.
 *
 * @internal
 */
final readonly class FlagsGridFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param list<FlagPresenter> $flags
     */
    public function render(array $flags, bool $isWritable): string
    {
        return (new GridView($this->container))
            ->dataReader(new IterableDataReader($flags))
            ->containerClass('mt-2')
            ->tableClass('table', 'table-striped', 'table-hover', 'align-middle')
            ->columns(...$this->columns($isWritable))
            ->render();
    }

    /**
     * @return list<DataColumn>
     */
    private function columns(bool $isWritable): array
    {
        $columns = [
            new DataColumn(
                header: 'Name',
                content: static function (FlagPresenter $f): string {
                    $badge = $f->killSwitch
                        ? ' <span class="badge text-bg-danger">KILLED</span>'
                        : ($f->enabled ? '' : ' <span class="badge text-bg-secondary">OFF</span>');

                    return '<strong>' . Html::encode($f->name) . '</strong>' . $badge;
                },
                encodeContent: false,
            ),
            new DataColumn(
                header: 'Rollout',
                content: static fn(FlagPresenter $f): string => Html::encode($f->rollout . '%'),
            ),
            new DataColumn(
                header: 'Environments',
                content: static fn(FlagPresenter $f): string => '<span class="badge text-bg-info">' . Html::encode($f->environmentsLabel()) . '</span>',
                encodeContent: false,
            ),
        ];

        if ($isWritable) {
            $columns[] = new DataColumn(
                header: '',
                content: static fn(FlagPresenter $f): string => Html::a('Edit', $f->editUrl, ['class' => 'btn btn-outline-primary btn-sm'])->render(),
                encodeContent: false,
            );
        }

        return $columns;
    }
}
