<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

abstract class AppTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setPrimaryKey('id');
    }

    protected function timestamps(): void
    {
        $this->addBehavior('Timestamp', ['events' => ['Model.beforeSave' => ['createdAt' => 'new', 'updatedAt' => 'always']]]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach (['code', 'name', 'title', 'description', 'firstName', 'lastName', 'email'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }
    }

    protected function text(Validator $validator, string $field, int $max, bool $required = true): void
    {
        $validator->scalar($field, 'Introduce un texto válido.')
            ->maxLength($field, $max, "No puede superar los {$max} caracteres.");
        if ($required) {
            $validator->requirePresence($field, 'create', 'Este campo es obligatorio.')
                ->notEmptyString($field, 'Este campo es obligatorio.');
        } else {
            $validator->allowEmptyString($field);
        }
    }

    protected function code(Validator $validator): void
    {
        $this->text($validator, 'code', 20);
        $validator->add('code', 'format', ['rule' => ['custom', '/^[A-Z0-9][A-Z0-9_-]{0,19}$/D'], 'message' => 'Usa mayúsculas, números, guion o guion bajo (máximo 20 caracteres).']);
    }

    protected function integerRange(Validator $validator, string $field, int $min, int $max = 2147483647): void
    {
        $validator->requirePresence($field, 'create', 'Este campo es obligatorio.')
            ->notEmptyString($field, 'Este campo es obligatorio.')
            ->add($field, 'wholeNumber', [
                'rule' => static fn(mixed $value): bool => (is_int($value) || is_string($value)) && preg_match('/^\d+$/D', (string)$value) === 1,
                'message' => 'Introduce un número entero válido.', 'last' => true,
            ])
            ->range($field, [$min, $max], "Debe ser un entero entre {$min} y {$max}.");
    }

    protected function decimalRange(Validator $validator, string $field, float $min, float $max, int $places = 2, bool $exclusive = false): void
    {
        $validator->requirePresence($field, 'create', 'Este campo es obligatorio.')
            ->notEmptyString($field, 'Este campo es obligatorio.')
            ->add($field, 'decimalPrecision', [
                'rule' => static fn(mixed $value): bool => (is_int($value) || is_float($value) || is_string($value))
                    && preg_match('/^\d+(?:\.\d{1,' . $places . '})?$/D', (string)$value) === 1
                    && is_finite((float)$value),
                'message' => "Introduce un número no negativo con punto decimal y hasta {$places} decimales.", 'last' => true,
            ])
            ->add($field, 'safeRange', [
                'rule' => static fn(mixed $value): bool => $exclusive
                    ? (float)$value > $min && (float)$value < $max
                    : (float)$value >= $min && (float)$value <= $max,
                'message' => $exclusive ? "Debe ser estrictamente mayor que {$min} y menor que {$max}." : "Debe estar entre {$min} y {$max} (inclusive).",
            ]);
    }

    protected function foreignKey(Validator $validator, string $field, bool $nullable = false): void
    {
        $this->integerRange($validator, $field, 1);
        if ($nullable) {
            $validator->allowEmptyString($field);
        }
    }

    protected function unique(RulesChecker $rules, string $field): void
    {
        $rules->add($rules->isUnique([$field]), 'unique_' . $field, ['errorField' => $field, 'message' => 'Este valor ya está registrado. Debe ser único.']);
    }

    protected function reference(RulesChecker $rules, string $field, string $association): void
    {
        $rules->add($rules->existsIn([$field], $association), 'exists_' . $field, ['errorField' => $field, 'message' => 'La opción seleccionada no existe. Selecciona un registro válido.']);
    }

    protected function restrictDelete(RulesChecker $rules, string $association, string $foreignKey): void
    {
        $rules->addDelete(function (EntityInterface $entity) use ($association, $foreignKey): bool {
            return !$this->getAssociation($association)->getTarget()->exists([$foreignKey => $entity->get('id')]);
        }, 'restrict_' . $association, ['errorField' => '_delete', 'message' => 'No se puede eliminar: existen registros relacionados.']);
    }
}
