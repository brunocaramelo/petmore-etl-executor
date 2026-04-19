<?php

namespace App\Casts;

use App\Casts\ValueCast;

use InvalidArgumentException;

class ConfigRowProcessor
{
    private $config;

    private $caster;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->caster = new ValueCast();
    }


    public function processDepara(): array
    {
        $depara = [];

        if (!isset($this->config[0])) {
            return $depara;
        }

        $configNode = $this->config[0];

        if (isset($configNode['infos']) && is_array($configNode['infos'])) {
            foreach ($configNode['infos'] as $info) {
                $this->processInfoNode($info, $depara, $configNode);
            }
        }

        return $depara;
    }


    private function processInfoNode(array $info, array &$depara, array $configNode): void
    {
        if (!isset($info['plan_fields'], $info['rule'])) {
            return;
        }

        $planFields = $info['plan_fields'];
        $rule = $info['rule'];

        if (in_array($rule['name'], $planFields, true)) {
            $identify = $this->getIdentifyValue($rule['name'], $configNode);

            $deparaItem = [
                'field' => $rule['name'],
                'identify' => $identify,
                'type' => $rule['type'],
                'required' => $rule['required'] ?? false
            ];

            $deparaItem['plan_fields'] = $planFields;

            $depara[] = $deparaItem;
        }
    }


    public function getIdentifyValue(array $fieldName)
    {
        $configNode = $this->config['reference'];

        $choicedValue = null;

        foreach ($configNode as $reference) {
            foreach($fieldName as $indexField => $field) {
                if (in_array($indexField, $reference['plan_fields'])) {
                    $choicedValue = $field;
                    break 2;
                }
            }
        }
        return $choicedValue;
    }


    public function processField(array $dataRow): array
    {
        $processed = [];
        $caster = $this->caster;
        $depara = $this->config['infos'];

        $this->validateRequiredFields($dataRow);

        foreach ($depara as $mapping) {

            $field = $mapping['rule']['name'];
            $type = $mapping['rule']['type'];

            if (isset($dataRow[$field])) {
                $processed[$field] = $caster->castValue($dataRow[$field], $type);
            } elseif (isset($mapping['plan_fields'])) {
                foreach ($mapping['plan_fields'] as $planField) {
                    if (isset($dataRow[$planField])) {
                        $processed[$field] = $caster->castValue($dataRow[$planField], $type);
                        break;
                    }
                }
            }

            if (!isset($processed[$field]) && ($mapping['required'] ?? false)) {
                $processed[$field] = null;
            }
        }

        return $processed;
    }


       public function validateRequiredFields(array $dataRow): void
    {
        if (!isset($this->config[0])) {
            return;
        }

        $configNode = $this->config[0];
        $missingRequiredFields = [];

        if (isset($configNode['infos']) && is_array($configNode['infos'])) {
            foreach ($configNode['infos'] as $info) {
                if (!isset($info['plan_fields'], $info['rule'])) {
                    continue;
                }

                $rule = $info['rule'];
                $isRequired = $rule['required'] ?? false;

                if ($isRequired) {
                    $planFields = $info['plan_fields'];
                    $fieldFound = false;

                    foreach ($planFields as $planField) {
                        if (isset($dataRow[$planField]) && $dataRow[$planField] !== null && $dataRow[$planField] !== '') {
                            $fieldFound = true;
                            break;
                        }
                    }

                    if (!$fieldFound) {
                        $missingRequiredFields[] = [
                            'field_name' => $rule['name'],
                            'plan_fields' => $planFields
                        ];
                    }
                }
            }
        }

        if (!empty($missingRequiredFields)) {
            $this->throwMissingFieldsException($missingRequiredFields, $dataRow);
        }
    }

    private function throwMissingFieldsException(array $missingFields, array $dataRow): void
    {
        $fieldNames = array_column($missingFields, 'field_name');
        $fieldList = implode("', '", $fieldNames);

        $availableFields = array_keys($dataRow);
        $availableFieldsList = !empty($availableFields) ? implode("', '", $availableFields) : 'Nenhum campo disponível';

        $message = "Campos obrigatórios não encontrados: \n";

        foreach ($missingFields as $missing) {
            $planFieldsList = implode("', '", $missing['plan_fields']);
            $message .= sprintf(
                "- Campo '%s' (procurar em: '%s')\n",
                $missing['field_name'],
                $planFieldsList
            );
        }

        $message .= sprintf(
            "\nCampos disponíveis na linha: '%s'\n" .
            "Dados da linha: %s",
            $availableFieldsList,
            json_encode($dataRow, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        throw new InvalidArgumentException($message);
    }

}
