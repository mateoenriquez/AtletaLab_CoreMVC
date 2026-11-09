<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\View\Helper;

class AdminHelper extends Helper
{
    protected array $helpers = ['Html'];

    public function label(string $field, array $resource): string
    {
        return $resource['fields'][$field]['label'] ?? ['createdAt' => 'Creado el', 'updatedAt' => 'Actualizado el', 'loggedAt' => 'Registrado el'][$field] ?? $field;
    }

    public function value(EntityInterface $record, string $field, array $resource): string
    {
        $meta = $resource['fields'][$field] ?? [];
        $value = $record;
        foreach (explode('.', $meta['path'] ?? $field) as $part) {
            $value = $value instanceof EntityInterface ? $value->get($part) : null;
        }
        if ($value === null || $value === '') {
            return '<span class="muted">Sin asignar</span>';
        }
        if ($field === 'status') {
            return $this->status((string)$value);
        }
        if (($meta['type'] ?? '') === 'checkbox') {
            return '<span class="tag ' . ($value ? 'tag-green' : 'tag-neutral') . '">' . ($value ? 'Sí' : 'No') . '</span>';
        }
        if ($value instanceof \DateTimeInterface) {
            if ($field === 'birthDate') {
                return h($value->format('d/m/Y'));
            }
            $date = \DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone(Configure::read('App.defaultTimezone')));

            return h($date->format('d/m/Y H:i'));
        }
        if (is_object($value) && method_exists($value, 'format')) {
            return h($value->format('d/m/Y'));
        }
        if (isset($meta['relatedController']) && $record->get($field)) {
            return $this->Html->link((string)$value, ['controller' => $meta['relatedController'], 'action' => 'view', $record->get($field)], ['class' => 'relation-link']);
        }
        if (($meta['type'] ?? '') === 'number' && ($meta['step'] ?? '1') !== '1') {
            $decimals = ($meta['step'] ?? '') === '0.1' ? 1 : 2;

            return '<span class="tabular">' . h(number_format((float)$value, $decimals, ',', '.')) . '</span>';
        }

        return h((string)$value);
    }

    public function status(string $status): string
    {
        $class = ['Activa' => 'tag-blue', 'En Descarga' => 'tag-amber', 'Estancada' => 'tag-red', 'Completada' => 'tag-green'][$status] ?? 'tag-neutral';

        return '<span class="tag ' . $class . '"><span class="status-dot" aria-hidden="true"></span>' . h($status) . '</span>';
    }

    public function errors(array $errors, array $resource): array
    {
        $messages = [];
        foreach ($errors as $field => $rules) {
            $label = str_starts_with((string)$field, '_') ? 'Formulario' : $this->label((string)$field, $resource);
            $flatten = function (mixed $value) use (&$flatten, &$messages, $label): void {
                if (is_array($value)) {
                    foreach ($value as $child) {
                        $flatten($child);
                    }
                } else {
                    $messages[] = $label . ': ' . (string)$value;
                }
            };
            $flatten($rules);
        }

        return $messages;
    }
}
