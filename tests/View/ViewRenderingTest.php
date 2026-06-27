<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\View;

use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\ViewTemplateRenderer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

#[Test]
#[Covers(ViewTemplateRenderer::class)]
final class ViewRenderingTest
{
    private ViewTemplateRenderer $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->renderer = $this->renderer();
    }

    public function listTemplateRendersGridHtmlAndCreateButton(): void
    {
        $html = $this->render('list', [
            'flags' => [],
            'isWritable' => true,
            'createUrl' => '/admin/flags/new',
            'gridHtml' => '<table id="flags-grid"></table>',
        ]);

        Assert::string($html)->contains('<!DOCTYPE html>');
        Assert::string($html)->contains('<table id="flags-grid"></table>');
        Assert::string($html)->contains('Feature flags');
        Assert::string($html)->contains('href="/admin/flags/new"');
        Assert::string($html)->contains('New flag');
    }

    public function listTemplateShowsReadOnlyBannerWhenProviderReadOnly(): void
    {
        $html = $this->render('list', [
            'flags' => [],
            'isWritable' => false,
            'createUrl' => '/admin/flags/new',
            'gridHtml' => '',
        ]);

        Assert::string($html)->contains('Read-only provider');
    }

    public function editTemplateRendersFieldsAndValuesForExistingFlag(): void
    {
        $flag = new Flag(name: 'checkout.v2', enabled: true, salt: 'salt', rollout: 75, killSwitch: false, environments: ['prod']);
        $form = new FlagForm(present: false, name: 'checkout.v2', enabled: true, rollout: '75', salt: 'salt', environments: '["prod"]');

        $html = $this->render('edit', $this->editParams(form: $form, flag: $flag, isNew: false));

        Assert::string($html)->contains('Edit flag');
        Assert::string($html)->contains('checkout.v2');
        Assert::string($html)->contains('75');
        Assert::string($html)->contains('["prod"]');
    }

    public function editTemplateRendersKillSwitchWarning(): void
    {
        $form = new FlagForm(present: false, name: 'x', killSwitch: true);

        $html = $this->render('edit', $this->editParams(form: $form, isNew: false));

        Assert::string($html)->contains('Kill switch');
        Assert::string($html)->contains('Overrides rollout');
    }

    public function editTemplateShowsValidationError(): void
    {
        $form = new FlagForm(present: true, name: 'x', rollout: 'abc');
        $params = $this->editParams(form: $form, isNew: false);
        $params['error'] = 'Rollout must be an integer between 0 and 100';

        $html = $this->render('edit', $params);

        Assert::string($html)->contains('Rollout must be an integer');
    }

    public function editTemplateShowsDeleteButtonForExistingWritableFlag(): void
    {
        $flag = new Flag(name: 'x');
        $form = new FlagForm(present: false, name: 'x');

        $html = $this->render('edit', $this->editParams(form: $form, flag: $flag, isNew: false));

        Assert::string($html)->contains('Delete');
    }

    public function editTemplateOmitsDeleteButtonForNewFlag(): void
    {
        $form = new FlagForm(present: false);

        $html = $this->render('edit', $this->editParams(form: $form, isNew: true));

        Assert::string($html)->notContains('btn-outline-danger');
        Assert::string($html)->notContains('formaction');
    }

    public function editTemplateDisablesFieldsWhenReadOnly(): void
    {
        $form = new FlagForm(present: false, name: 'x');

        $params = $this->editParams(form: $form, isNew: false);
        $params['isWritable'] = false;

        $html = $this->render('edit', $params);

        Assert::string($html)->contains('disabled');
        Assert::string($html)->contains('read-only');
    }

    public function configuredListAndEditViewsOverrideDefaults(): void
    {
        $basePath = sys_get_temp_dir() . '/yii3-feature-flags-ui-' . bin2hex(random_bytes(4));
        $overridesPath = $basePath . '/overrides';
        mkdir($overridesPath, 0o777, true);

        file_put_contents($basePath . '/_layout-standalone.php', '<?php declare(strict_types=1); ?><html><body><?= $content ?></body></html>');
        file_put_contents($overridesPath . '/list.php', '<?php declare(strict_types=1); ?>LIST OVERRIDE');
        file_put_contents($overridesPath . '/edit.php', '<?php declare(strict_types=1); ?>EDIT OVERRIDE');

        $renderer = $this->renderer(
            viewPath: $basePath,
            layout: $basePath . '/_layout-standalone.php',
            views: [
                'list' => 'overrides/list.php',
                'edit' => 'overrides/edit.php',
            ],
        );

        $listHtml = (string) $renderer->render('list', ['flags' => [], 'isWritable' => true, 'createUrl' => '', 'gridHtml' => ''])->getBody();
        $editHtml = (string) $renderer->render('edit', $this->editParams())->getBody();

        Assert::string($listHtml)->contains('LIST OVERRIDE');
        Assert::string($editHtml)->contains('EDIT OVERRIDE');
    }

    /**
     * @return array<string, mixed>
     */
    private function editParams(?FlagForm $form = null, ?Flag $flag = null, bool $isNew = false): array
    {
        return [
            'form' => $form ?? new FlagForm(present: false, name: 'x'),
            'flag' => $flag,
            'isNew' => $isNew,
            'isWritable' => true,
            'updateUrl' => '/admin/flags/x',
            'deleteUrl' => '/admin/flags/x/delete',
            'listUrl' => '/admin/flags',
            'error' => null,
            'csrf' => null,
        ];
    }

    /**
     * @param array<string, string> $views
     */
    private function renderer(?string $viewPath = null, ?string $layout = null, array $views = []): ViewTemplateRenderer
    {
        $psr17 = new Psr17Factory();
        $webRenderer = new WebViewRenderer(
            responseFactory: $psr17,
            streamFactory: $psr17,
            aliases: new Aliases(),
            view: new WebView(),
        );

        return new ViewTemplateRenderer(
            renderer: $webRenderer,
            viewPath: $viewPath,
            layout: $layout,
            views: $views,
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function render(string $view, array $params): string
    {
        return (string) $this->renderer->render($view, $params)->getBody();
    }
}
