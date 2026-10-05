<?php

namespace App\Enums;

enum StopStatus: string
{
    case Pending = 'pending';
    case Arrived = 'arrived';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /**
     * Get the display label for the stop status.
     */
    public function label(): string
    {
        return __("stop_statuses.{$this->value}");
    }

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Arrived, self::Skipped, self::Failed],
            self::Arrived => [self::Completed, self::Failed],
            self::Completed, self::Failed, self::Skipped => [],
        };
    }

    /**
     * Determine whether the stop may move to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get the stop statuses as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the stop status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
