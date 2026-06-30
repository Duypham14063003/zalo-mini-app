<?php

namespace App\Observers;

use App\Support\AuditLogWriter;
use Illuminate\Database\Eloquent\Model;

class ConfigurationAuditObserver
{
    public function __construct(
        protected AuditLogWriter $auditLogWriter,
    ) {
    }

    public function created(Model $model): void
    {
        $this->auditLogWriter->logModelChange(
            action: 'created',
            model: $model,
            afterState: $this->extractState($model),
        );
    }

    public function updated(Model $model): void
    {
        if ($model->getChanges() === []) {
            return;
        }

        $beforeState = [];
        $afterState = [];

        foreach (array_keys($model->getChanges()) as $attribute) {
            if (in_array($attribute, ['updated_at', 'created_at'], true)) {
                continue;
            }

            $beforeState[$attribute] = $model->getOriginal($attribute);
            $afterState[$attribute] = $model->getAttribute($attribute);
        }

        if ($afterState === []) {
            return;
        }

        $this->auditLogWriter->logModelChange(
            action: 'updated',
            model: $model,
            beforeState: $beforeState,
            afterState: $afterState,
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditLogWriter->logModelChange(
            action: 'deleted',
            model: $model,
            beforeState: $this->extractState($model),
        );
    }

    protected function extractState(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['created_at', 'updated_at'])
            ->map(fn ($value) => $this->normaliseValue($value))
            ->all();
    }

    protected function normaliseValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
