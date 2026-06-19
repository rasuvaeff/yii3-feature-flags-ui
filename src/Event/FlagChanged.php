<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Event;

/**
 * Dispatched after a flag is saved or deleted through the admin UI.
 *
 * `actor` carries the current user ID when the host application provides one.
 *
 * @api
 */
final readonly class FlagChanged
{
    public const string OPERATION_SAVED = 'saved';

    public const string OPERATION_DELETED = 'deleted';

    public function __construct(
        public string $name,
        public string $operation,
        public ?string $actor = null,
    ) {}

    public static function saved(string $name, ?string $actor = null): self
    {
        return new self(name: $name, operation: self::OPERATION_SAVED, actor: $actor);
    }

    public static function deleted(string $name, ?string $actor = null): self
    {
        return new self(name: $name, operation: self::OPERATION_DELETED, actor: $actor);
    }
}
