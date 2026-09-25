<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_SurveyQuestion;
use App\Models\SurveyQuestion;

class SurveyQuestionService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyQuestionById(int $id): ?SurveyQuestion
    {
        return SurveyQuestion::find($id);
    }

    public function createSurveyQuestion(array $data): SurveyQuestion
    {
        $data = $this->applyValueType($data);
        $proyect = SurveyQuestion::create($data);

        return $proyect;
    }

    public function updateSurveyQuestion(SurveyQuestion $proyect, array $data): SurveyQuestion
    {
        $proyect->update($this->applyValueType($data));

        return $proyect;
    }

    public function destroyById($id)
    {
        return SurveyQuestion::find($id)?->delete() ?? false;
    }

    private function applyValueType(array $data): array
    {
        $fieldType = strtoupper(trim((string) ($data['type_field'] ?? '')));

        if (
            ($fieldType === SurveyQuestion::FIELD_TYPE_DECIMAL
                || in_array($fieldType, SurveyQuestion::INTEGER_FIELD_TYPES, true))
            && empty($data['calculator_value_type'])
        ) {
            $data['calculator_value_type'] = 'number';
        }

        return $data;
    }
}
