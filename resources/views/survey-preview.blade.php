<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista previa — {{ $survey->survey_name }}</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #175fc2;
            --primary-soft: #eef5ff;
            --border: #d9e2ee;
            --text: #172033;
            --muted: #65748b;
            --surface: #ffffff;
            --background: #f4f7fb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .preview-shell { width: min(980px, calc(100% - 28px)); margin: 28px auto; }
        .survey-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(31, 58, 94, .08);
            overflow: hidden;
        }
        .survey-header {
            background: linear-gradient(135deg, #113b72, #1f68ce);
            color: white;
            padding: 28px 32px;
        }
        .survey-header h1 { font-size: clamp(22px, 4vw, 32px); margin: 6px 0 10px; }
        .eyebrow { font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        .pill {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 999px;
            font-size: 12px;
            padding: 5px 9px;
        }
        .survey-body { padding: 28px 32px 34px; }
        .days { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .day-button {
            background: white;
            border: 1px solid #b9cbe2;
            border-radius: 8px;
            color: #36516f;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 13px;
        }
        .day-button[aria-selected="true"] { background: var(--primary); border-color: var(--primary); color: white; }
        .question {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 14px;
            padding: 20px;
        }
        .question[hidden] { display: none; }
        .question-heading { align-items: flex-start; display: flex; gap: 12px; margin-bottom: 14px; }
        .question-number {
            align-items: center;
            background: var(--primary);
            border-radius: 8px;
            color: white;
            display: inline-flex;
            flex: 0 0 34px;
            font-size: 13px;
            font-weight: 800;
            height: 34px;
            justify-content: center;
        }
        .question-title { font-size: 16px; font-weight: 750; line-height: 1.45; margin: 3px 0 0; }
        .required { color: #b3261e; font-size: 12px; font-weight: 700; margin-left: 5px; }
        .badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 7px; }
        .badge { background: var(--primary-soft); border-radius: 999px; color: #2d5e9f; font-size: 11px; padding: 4px 8px; }
        .control {
            background: #f9fbfd;
            border: 1px solid #c7d5e5;
            border-radius: 8px;
            color: #68788b;
            display: block;
            font: inherit;
            min-height: 43px;
            padding: 10px 12px;
            width: 100%;
        }
        textarea.control { min-height: 92px; resize: none; }
        .option-list { display: grid; gap: 9px; }
        .option { align-items: center; display: flex; gap: 9px; color: #42536a; font-size: 14px; }
        .location-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .empty { color: var(--muted); padding: 34px 0; text-align: center; }
        .preview-footer { color: var(--muted); font-size: 12px; padding-top: 14px; text-align: center; }
        @media (max-width: 640px) {
            .preview-shell { margin: 12px auto; width: min(100% - 16px, 980px); }
            .survey-header, .survey-body { padding: 20px 18px; }
            .location-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="preview-shell">
    <article class="survey-card">
        <header class="survey-header">
            <div class="eyebrow">{{ $survey->proyect?->name ?? 'Encuesta' }}</div>
            <h1>{{ $survey->survey_name }}</h1>
            <div class="meta">
                <span class="pill">{{ $survey->survey_type === 'PRE' ? 'Línea base' : 'Monitoreo' }}</span>
                <span class="pill">{{ $survey->status }}</span>
                @if($survey->code)<span class="pill">{{ $survey->code }}</span>@endif
            </div>
        </header>

        <section class="survey-body">
            @if($supportsDailyMeasurements)
                <nav class="days" aria-label="Día de la encuesta">
                    @for($day = 1; $day <= $expectedDays; $day++)
                        <button class="day-button" type="button" data-day="{{ $day }}" aria-selected="{{ $day === 1 ? 'true' : 'false' }}">Día {{ $day }}</button>
                    @endfor
                </nav>
            @endif

            @forelse($survey->survey_questions as $question)
                @php
                    $scope = $question->effectiveResponseScope();
                    $days = $question->applicable_days ?: [];
                    $fieldType = strtoupper((string) $question->type_field);
                    $questionType = strtoupper((string) $question->question_type);
                    $isSelect = in_array($fieldType, ['SELECT', 'LISTADO'], true);
                @endphp
                <section
                    class="question"
                    data-scope="{{ $scope }}"
                    data-days="{{ implode(',', $days) }}"
                >
                    <div class="question-heading">
                        <span class="question-number">{{ (int) ($question->order ?? $loop->iteration) }}</span>
                        <div>
                            <h2 class="question-title">
                                {{ $question->question_text }}
                                @if($question->is_required)<span class="required">obligatorio</span>@endif
                            </h2>
                            <div class="badges">
                                @if($question->section_title)<span class="badge">{{ $question->section_title }}</span>@endif
                                @if($question->scenario)<span class="badge">{{ str_replace('_', ' ', $question->scenario) }}</span>@endif
                            </div>
                        </div>
                    </div>

                    @if($question->calculator_key === 'household.identifier' && $survey->survey_type === 'POST')
                        <select class="control" disabled aria-label="{{ $question->question_text }}">
                            <option>Buscar y seleccionar un ID de hogar de línea base</option>
                        </select>
                    @elseif($questionType === 'OPCIONES' && $isSelect)
                        <select class="control" disabled aria-label="{{ $question->question_text }}">
                            <option>Seleccione una opción</option>
                            @foreach($question->survey_questions_options as $option)
                                <option>{{ $option->description }}</option>
                            @endforeach
                        </select>
                    @elseif($questionType === 'OPCIONES')
                        <div class="option-list">
                            @forelse($question->survey_questions_options as $option)
                                <label class="option"><input type="radio" disabled> {{ $option->description }}</label>
                            @empty
                                <span class="option">Sin opciones configuradas</span>
                            @endforelse
                        </div>
                    @elseif($questionType === 'FILE')
                        <input class="control" type="file" disabled aria-label="{{ $question->question_text }}">
                    @elseif($questionType === 'UBICACION' || $fieldType === 'UBICACION')
                        <div class="location-grid">
                            <input class="control" type="text" placeholder="Latitud" disabled>
                            <input class="control" type="text" placeholder="Longitud" disabled>
                        </div>
                    @elseif($fieldType === 'LARGO')
                        <textarea class="control" placeholder="Escribe tu respuesta…" disabled></textarea>
                    @elseif($fieldType === 'FECHA')
                        <input class="control" type="date" disabled aria-label="{{ $question->question_text }}">
                    @elseif(in_array($fieldType, ['NUMERICO', 'NUMERO', 'NUMBER', 'DECIMAL'], true))
                        <input class="control" type="number" step="{{ $fieldType === 'DECIMAL' ? 'any' : '1' }}" placeholder="{{ $fieldType === 'DECIMAL' ? '0.00' : '0' }}" disabled>
                    @else
                        <input class="control" type="text" placeholder="Escribe tu respuesta…" disabled>
                    @endif
                </section>
            @empty
                <div class="empty">Esta encuesta todavía no tiene preguntas.</div>
            @endforelse

            <div class="preview-footer">Vista previa administrativa · Encuesta #{{ $survey->id }}</div>
        </section>
    </article>
</main>

@if($supportsDailyMeasurements)
<script>
    const dayButtons = document.querySelectorAll('[data-day]');
    const questions = document.querySelectorAll('.question');

    function showDay(day) {
        dayButtons.forEach(button => button.setAttribute('aria-selected', button.dataset.day === String(day) ? 'true' : 'false'));
        questions.forEach(question => {
            if (question.dataset.scope !== 'MEASUREMENT') {
                question.hidden = false;
                return;
            }

            const applicableDays = question.dataset.days.split(',').filter(Boolean).map(Number);
            question.hidden = applicableDays.length > 0 && !applicableDays.includes(Number(day));
        });
    }

    dayButtons.forEach(button => button.addEventListener('click', () => showDay(button.dataset.day)));
    showDay(1);
</script>
@endif
</body>
</html>
