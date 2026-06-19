<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Validation;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;

/**
 * Casts an already-validated {@see FlagForm} to a {@see Flag} ready for the
 * writable provider. Assumes {@see FlagFormRules} have already accepted the
 * raw input; this step performs the type coercion (string rollout -> int,
 * environments JSON -> list<string>).
 *
 * @api
 */
final readonly class FlagFormNormalizer
{
    public function toFlag(FlagForm $form): Flag
    {
        return new Flag(
            name: $form->name,
            enabled: $form->enabled,
            salt: $form->salt,
            rollout: $this->normalizeRollout($form->rollout),
            killSwitch: $form->killSwitch,
            environments: $this->normalizeEnvironments($form->environments),
        );
    }

    private function normalizeRollout(string $rollout): int
    {
        $trimmed = trim($rollout);

        return $trimmed === '' ? 100 : (int) $trimmed;
    }

    /**
     * @return list<string>
     */
    private function normalizeEnvironments(string $environments): array
    {
        $trimmed = trim($environments);
        if ($trimmed === '') {
            return [];
        }

        /** @var list<string> $decoded */
        $decoded = json_decode(json: $trimmed, associative: true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
