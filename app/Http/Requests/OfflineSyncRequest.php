<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OfflineSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('payload'))) {
            return;
        }

        try {
            $payload = json_decode($this->input('payload'), true, 512, JSON_THROW_ON_ERROR);
            $this->merge(is_array($payload) ? $payload : ['payload_invalid' => true]);
        } catch (\JsonException) {
            $this->merge(['payload_invalid' => true]);
        }
    }

    public function rules(): array
    {
        $responseRules = [
            'survey_question_id' => ['required', 'integer', 'min:1'],
            'survey_question_option_id' => ['nullable', 'array'],
            'survey_question_option_id.*' => ['integer', 'distinct', 'min:1'],
            'response_text' => ['nullable', 'string', 'max:1000'],
            'attachment_key' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
        $rules = [
            'payload_invalid' => ['prohibited'],
            'contract_version' => ['required', Rule::in(['1.0'])],
            'batch_id' => ['required', 'uuid'],
            'device_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.client_participation_id' => ['required', 'uuid'],
            'items.*.client_updated_at' => ['required', 'date'],
            'items.*.action' => ['required', Rule::in(['SAVE_DRAFT', 'FINALIZE'])],
            'items.*.number_document' => ['required', 'string', 'max:20'],
            'items.*.names' => ['required', 'string', 'max:1000'],
            'items.*.date_of_birth' => ['nullable', 'date'],
            'items.*.phone' => ['nullable', 'string', 'max:255'],
            'items.*.email' => ['nullable', 'email', 'max:255'],
            'items.*.genero' => ['nullable', 'string', 'max:255'],
            'items.*.household_code' => ['nullable', 'string', 'max:64', 'regex:'.Household::codePattern()],
            'items.*.survey_id' => ['required', 'integer', 'min:1'],
            'items.*.latitude' => ['nullable', 'required_with:items.*.longitude', 'numeric', 'between:-90,90'],
            'items.*.longitude' => ['nullable', 'required_with:items.*.latitude', 'numeric', 'between:-180,180'],
            'items.*.responses' => ['sometimes', 'array'],
            'items.*.measurements' => ['sometimes', 'array', 'max:7'],
            'items.*.measurements.*.client_measurement_id' => ['required', 'uuid'],
            'items.*.measurements.*.day_number' => ['required', 'integer', 'between:1,7'],
            'items.*.measurements.*.responses' => ['sometimes', 'array'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,mp4,zip'],
        ];

        foreach ($responseRules as $field => $fieldRules) {
            $rules['items.*.responses.*.'.$field] = $fieldRules;
            $rules['items.*.measurements.*.responses.*.'.$field] = $fieldRules;
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $participationIds = [];
                $measurementIds = [];
                $attachmentKeys = [];

                foreach ($this->input('items', []) as $itemIndex => $item) {
                    $participationId = $item['client_participation_id'] ?? null;
                    if ($participationId && isset($participationIds[$participationId])) {
                        $validator->errors()->add(
                            "items.$itemIndex.client_participation_id",
                            'El identificador local de participación está repetido en el lote.'
                        );
                    }
                    $participationIds[$participationId] = true;

                    $days = [];
                    foreach ($item['measurements'] ?? [] as $measurementIndex => $measurement) {
                        $measurementId = $measurement['client_measurement_id'] ?? null;
                        if ($measurementId && isset($measurementIds[$measurementId])) {
                            $validator->errors()->add(
                                "items.$itemIndex.measurements.$measurementIndex.client_measurement_id",
                                'El identificador local de medición está repetido en el lote.'
                            );
                        }
                        $measurementIds[$measurementId] = true;

                        $day = $measurement['day_number'] ?? null;
                        if ($day !== null && isset($days[$day])) {
                            $validator->errors()->add(
                                "items.$itemIndex.measurements.$measurementIndex.day_number",
                                'No puede enviarse el mismo día más de una vez para una participación.'
                            );
                        }
                        $days[$day] = true;

                        $this->collectAttachmentKeys(
                            $measurement['responses'] ?? [],
                            "items.$itemIndex.measurements.$measurementIndex.responses",
                            $attachmentKeys,
                            $validator
                        );
                    }

                    $this->collectAttachmentKeys(
                        $item['responses'] ?? [],
                        "items.$itemIndex.responses",
                        $attachmentKeys,
                        $validator
                    );
                }
            }
        );
    }

    private function collectAttachmentKeys(
        array $responses,
        string $path,
        array &$attachmentKeys,
        Validator $validator
    ): void {
        foreach ($responses as $responseIndex => $response) {
            $key = $response['attachment_key'] ?? null;
            if (! $key) {
                continue;
            }

            if (isset($attachmentKeys[$key])) {
                $validator->errors()->add(
                    "$path.$responseIndex.attachment_key",
                    'Cada archivo adjunto debe utilizar una clave única dentro del lote.'
                );
            }
            $attachmentKeys[$key] = true;

            if (! $this->hasFile("attachments.$key")) {
                $validator->errors()->add(
                    "$path.$responseIndex.attachment_key",
                    "No se recibió el archivo adjunto identificado como $key."
                );
            }
        }
    }
}
