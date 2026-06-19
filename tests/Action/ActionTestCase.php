<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeUrlGenerator;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;

abstract class ActionTestCase extends TestCase
{
    protected Psr17Factory $http;

    #[\Override]
    protected function setUp(): void
    {
        $this->http = new Psr17Factory();
    }

    /**
     * @return array<string, Flag>
     */
    protected function flags(): array
    {
        return [
            'checkout.v2' => new Flag(name: 'checkout.v2', enabled: true, rollout: 100),
            'search.beta' => new Flag(name: 'search.beta', enabled: false, rollout: 50, killSwitch: false, environments: ['prod', 'staging']),
            'billing.maintenance' => new Flag(name: 'billing.maintenance', enabled: true, rollout: 100, killSwitch: true),
        ];
    }

    protected function urls(): FlagUrls
    {
        return new FlagUrls(urlGenerator: new FakeUrlGenerator());
    }

    protected function listResponder(FakeTemplateRenderer $renderer, FlagProvider $provider): ListFlagsResponder
    {
        return new ListFlagsResponder(
            renderer: $renderer,
            provider: $provider,
            urls: $this->urls(),
            gridFactory: new FlagsGridFactory(new TestContainer()),
        );
    }

    protected function editResponder(FakeTemplateRenderer $renderer, FlagProvider $provider): EditFlagResponder
    {
        return new EditFlagResponder(
            editPage: $this->editPage($renderer),
            responseFactory: $this->http,
            provider: $provider,
        );
    }

    protected function editPage(FakeTemplateRenderer $renderer): EditPageRenderer
    {
        return new EditPageRenderer(
            renderer: $renderer,
            urls: $this->urls(),
        );
    }

    protected function updateProcessor(
        FlagProvider $provider,
        FakeTemplateRenderer $renderer,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): UpdateFlagProcessor {
        return new UpdateFlagProcessor(
            provider: $provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            editPage: $this->editPage($renderer),
            validator: new Validator(),
            normalizer: new FlagFormNormalizer(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
        );
    }

    protected function deleteProcessor(
        FlagProvider $provider,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): DeleteFlagProcessor {
        return new DeleteFlagProcessor(
            provider: $provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
        );
    }

    protected function currentUser(?string $id): CurrentUser
    {
        $currentUser = new CurrentUser(
            identityRepository: $this->createMock(IdentityRepositoryInterface::class),
            eventDispatcher: $this->createMock(EventDispatcherInterface::class),
        );

        if ($id !== null) {
            $currentUser->overrideIdentity(new readonly class ($id) implements IdentityInterface {
                public function __construct(private string $id) {}

                #[\Override]
                public function getId(): string
                {
                    return $this->id;
                }
            });
        }

        return $currentUser;
    }

    /**
     * @param array<string, mixed>|null $parsedBody
     */
    protected function request(string $method, ?array $parsedBody = null): ServerRequestInterface
    {
        $request = $this->http->createServerRequest($method, '/admin/flags');

        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }

    protected function writableProvider(): WritableFlagProvider
    {
        return new RecordingWritableProvider(flags: $this->flags());
    }
}
