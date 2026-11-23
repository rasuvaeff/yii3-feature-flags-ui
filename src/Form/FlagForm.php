<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Form;

use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormRules;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\RulesProviderInterface;

/**
 * Submitted edit input for a single flag.
 *
 * Holds the raw form fields (name, enabled, rollout, salt, killSwitch,
 * environments) as the browser sends them. Normalization to native PHP types
 * happens in {@see \Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer}
 * after validation passes.
 *
 * `present` distinguishes "form not submitted" from "submitted with defaults".
 *
 * @api
 */
final class FlagForm extends FormModel implements RulesProviderInterface
{
    public function __construct(
        public bool $present = false,
        public string $name = '',
        public bool $enabled = false,
        public string $rollout = '100',
        public string $salt = '',
        public bool $killSwitch = false,
        public string $environments = '',
    ) {}

    public static function fromParsedBody(array|object|null $body): self
    {
        if (!\is_array($body)) {
            return new self();
        }

        $scope = $body['Flag'] ?? null;
        if (!\is_array($scope)) {
            return new self();
        }

        return new self(
            present: true,
            name: self::asString($scope['name'] ?? null),
            enabled: self::toBool($scope['enabled'] ?? null),
            rollout: (string) ($scope['rollout'] ?? '100'),
            salt: (string) ($scope['salt'] ?? ''),
            killSwitch: self::toBool($scope['killSwitch'] ?? null),
            environments: self::asString($scope['environments'] ?? null),
        );
    }

    #[\Override]
    public function getRules(): iterable
    {
        return FlagFormRules::all();
    }

    private static function asString(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }

    private static function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'true'], true);
    }
}
