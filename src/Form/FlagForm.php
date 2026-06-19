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
    public function __construct(public bool $present = false, public string $name = '', public bool $enabled = false, public string $rollout = '100', public string $salt = '', public bool $killSwitch = false, public string $environments = '') {}

    public static function fromParsedBody(array|object|null $body): self
    {
        if (!\is_array($body)) {
            return new self();
        }

        $scope = $body['Flag'] ?? null;
        if (!\is_array($scope)) {
            return new self();
        }

        /** @var mixed $name */
        $name = $scope['name'] ?? '';
        /** @var mixed $rollout */
        $rollout = $scope['rollout'] ?? '100';
        /** @var mixed $salt */
        $salt = $scope['salt'] ?? '';
        /** @var mixed $environments */
        $environments = $scope['environments'] ?? '';

        return new self(
            present: true,
            name: \is_string($name) ? $name : '',
            enabled: self::toBool($scope['enabled'] ?? null),
            rollout: \is_string($rollout) ? $rollout : (string) $rollout,
            salt: \is_string($salt) ? $salt : (string) $salt,
            killSwitch: self::toBool($scope['killSwitch'] ?? null),
            environments: \is_string($environments) ? $environments : '',
        );
    }

    #[\Override]
    public function getRules(): iterable
    {
        return FlagFormRules::all();
    }

    private static function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'true'], true);
    }
}
