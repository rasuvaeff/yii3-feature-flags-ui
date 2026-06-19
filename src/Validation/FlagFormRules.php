<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Validation;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;

/**
 * Builds yiisoft/validator rules encoding the accepted-value contract for flag
 * form fields. Mirrors the {@see \Rasuvaeff\Yii3FeatureFlags\Flag} constructor
 * constraints so form validation rejects invalid input before reaching the
 * provider.
 *
 * @internal
 */
final readonly class FlagFormRules
{
    public const string NAME_PATTERN = '/^[a-z][a-z0-9._-]*$/';

    /**
     * @return array<string, list<Callback|Regex|Required>>
     */
    public static function all(): array
    {
        return [
            'name' => [
                new Required(message: 'Flag name is required'),
                new Regex(
                    pattern: self::NAME_PATTERN,
                    message: 'Flag name must start with a lowercase letter and contain only lowercase letters, digits, dots, hyphens and underscores',
                ),
            ],
            'rollout' => [
                new Callback(static function (mixed $value): Result {
                    if (!\is_string($value) && !\is_int($value)) {
                        return self::error('Rollout must be an integer between 0 and 100');
                    }

                    $trimmed = trim((string) $value);

                    return ctype_digit($trimmed) && (int) $trimmed >= 0 && (int) $trimmed <= 100
                        ? new Result()
                        : self::error('Rollout must be an integer between 0 and 100');
                }),
            ],
            'environments' => [
                new Callback(static function (mixed $value): Result {
                    if (!\is_string($value)) {
                        return self::error('Environments must be a JSON array of strings');
                    }

                    $trimmed = trim($value);
                    if ($trimmed === '') {
                        return new Result();
                    }

                    try {
                        $decoded = json_decode(json: $trimmed, associative: true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        return self::error('Environments must be valid JSON');
                    }

                    if (!\is_array($decoded) || !array_is_list($decoded)) {
                        return self::error('Environments must be a JSON array');
                    }

                    foreach ($decoded as $item) {
                        if (!\is_string($item) || $item === '') {
                            return self::error('Each environment must be a non-empty string');
                        }
                    }

                    return new Result();
                }),
            ],
        ];
    }

    private static function error(string $message): Result
    {
        return (new Result())->addError($message);
    }
}
