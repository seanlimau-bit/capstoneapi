<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Preview - {{ $question->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css">
    <style>
        :root {
            --dark-red: #8B0000;
            --light-grey: #F5F5F5;
            --input-inactive: #E0E0E0;
            --input-active: #8B0000;
            --success: #28a745;
            --error: #dc3545;
        }

        body {
            background-color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .preview-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .preview-header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .progress-bar-custom {
            height: 6px;
            background-color: var(--input-inactive);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--dark-red);
            transition: width 0.3s ease;
        }

        .question-container {
            background: white;
            padding: 32px;
            border-radius: 16px;
            border: 1px solid var(--input-inactive);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .question-text {
            font-size: 18px;
            line-height: 1.6;
            color: #333;
        }

        .question-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 16px 0;
        }

        .mcq-option {
            background: white;
            border: 2px solid var(--input-inactive);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mcq-option:hover {
            border-color: var(--dark-red);
            background-color: rgba(139, 0, 0, 0.05);
        }

        .mcq-option.selected {
            border-color: var(--dark-red);
            background-color: rgba(139, 0, 0, 0.1);
        }

        .mcq-option.correct {
            border-color: var(--success);
            background-color: rgba(40, 167, 69, 0.1);
        }

        .mcq-option.incorrect {
            border-color: var(--error);
            background-color: rgba(220, 53, 69, 0.1);
        }

        .fib-input {
            display: inline-block;
            min-width: 80px;
            padding: 8px 12px;
            margin: 0 4px;
            border: 2px solid var(--input-inactive);
            border-radius: 6px;
            background: var(--light-grey);
            text-align: center;
            font-weight: 500;
        }

        .fib-input.active {
            border-color: var(--input-active);
            background: rgba(139, 0, 0, 0.1);
        }

        .keypad {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 16px;
        }

        .keypad-button {
            padding: 12px;
            background: var(--light-grey);
            border: 1px solid var(--input-inactive);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .keypad-button:hover {
            background: #e0e0e0;
        }

        .btn-check {
            width: 100%;
            padding: 16px;
            background: var(--dark-red);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-check:disabled {
            background: #999;
            cursor: not-allowed;
        }

        .preview-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff9800;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="preview-badge">PREVIEW MODE</div>
    
    <div class="preview-container">
        <!-- Header -->
        <div class="preview-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold">Question Preview</span>
                <div class="d-flex gap-1">
                    @for($i = 0; $i < 5; $i++)
                        <i class="fas fa-heart text-danger"></i>
                    @endfor
                </div>
            </div>
            <div class="progress-bar-custom">
                <div class="progress-fill" style="width: 50%"></div>
            </div>
            <small class="text-muted d-block mt-2">Question 1 of 1</small>
        </div>

        <!-- Question Content -->
        <div class="question-container">
            <div class="w-100">
                @if($question->question_image)
                    <img src="{{ asset($question->question_image) }}" alt="Question" class="question-image">
                @endif
                
                <div class="question-text" id="questionText">
                    {!! $question->question !!}
                </div>
            </div>
        </div>

        <!-- Answer Area -->
        <div id="answerArea">
            @if($question->type_id == 1)
                <!-- Multiple Choice -->
                @php
                    $options = [
                        ['text' => $question->answer0, 'image' => $question->answer0_image, 'index' => 0],
                        ['text' => $question->answer1, 'image' => $question->answer1_image, 'index' => 1],
                        ['text' => $question->answer2, 'image' => $question->answer2_image, 'index' => 2],
                        ['text' => $question->answer3, 'image' => $question->answer3_image, 'index' => 3],
                    ];
                    $validOptions = array_filter($options, fn($o) => !empty($o['text']) || !empty($o['image']));
                @endphp

                @foreach($validOptions as $option)
                    <div class="mcq-option" onclick="selectOption({{ $option['index'] }})">
                        @if($option['image'])
                            <img src="{{ asset($option['image']) }}" alt="Option" style="max-width: 100%; height: auto; margin-bottom: 8px; border-radius: 8px;">
                        @endif
                        <div>{!! $option['text'] !!}</div>
                    </div>
                @endforeach
            @else
                <!-- Fill in the Blank -->
                <div class="bg-light p-4 rounded">
                    <div class="keypad">
                        @foreach(['1','2','3','⌫','4','5','6',':','7','8','9','.'] as $key)
                            <button class="keypad-button" onclick="handleKeypad('{{ $key }}')">
                                @if($key == '⌫')
                                    <i class="fas fa-backspace"></i>
                                @else
                                    {{ $key }}
                                @endif
                            </button>
                        @endforeach
                        <button class="keypad-button" style="grid-column: span 2" onclick="handleKeypad('0')">0</button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Action Button -->
        <button class="btn-check mt-4" id="checkButton" onclick="checkAnswer()" disabled>
            Check Answer
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/contrib/auto-render.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    
    <script>
        let selectedAnswer = null;
        let fillInAnswers = {};
        let activeInput = 'input_0';
        const correctAnswer = {{ $question->correct_answer ?? 0 }};
        const questionType = {{ $question->type_id }};

        // Render KaTeX
        document.addEventListener('DOMContentLoaded', function() {
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: false},
                    {left: '$', right: '$', display: false}
                ]
            });
            
            // Initialize fill-in-the-blank inputs
            if (questionType === 2) {
                processFillInBlanks();
            }
        });

        function processFillInBlanks() {
            const questionText = document.getElementById('questionText');
            let html = questionText.innerHTML;
            
            // Replace input tags with interactive spans
            let inputCounter = 0;
            html = html.replace(/<input[^>]*>/gi, function() {
                const inputId = 'input_' + inputCounter;
                inputCounter++;
                return `<span class="fib-input ${inputId === activeInput ? 'active' : ''}" 
                             id="${inputId}" 
                             onclick="setActiveInput('${inputId}')">____</span>`;
            });
            
            questionText.innerHTML = html;
            
            // Re-render math after modification
            renderMathInElement(questionText, {
                delimiters: [
                    {left: '$$', right: '$$', display: false},
                    {left: '$', right: '$', display: false}
                ]
            });
        }

        function setActiveInput(inputId) {
            document.querySelectorAll('.fib-input').forEach(el => el.classList.remove('active'));
            document.getElementById(inputId).classList.add('active');
            activeInput = inputId;
        }

        function handleKeypad(value) {
            if (value === '⌫') {
                if (fillInAnswers[activeInput]) {
                    fillInAnswers[activeInput] = fillInAnswers[activeInput].slice(0, -1);
                }
            } else {
                fillInAnswers[activeInput] = (fillInAnswers[activeInput] || '') + value;
            }
            
            document.getElementById(activeInput).textContent = fillInAnswers[activeInput] || '____';
            
            // Enable check button if any input has value
            const hasInput = Object.values(fillInAnswers).some(v => v && v.length > 0);
            document.getElementById('checkButton').disabled = !hasInput;
        }

        function selectOption(index) {
            document.querySelectorAll('.mcq-option').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            selectedAnswer = index;
            document.getElementById('checkButton').disabled = false;
        }

        function checkAnswer() {
            if (questionType === 1) {
                // MCQ
                const options = document.querySelectorAll('.mcq-option');
                options.forEach((opt, idx) => {
                    if (idx === correctAnswer) {
                        opt.classList.add('correct');
                    } else if (idx === selectedAnswer) {
                        opt.classList.add('incorrect');
                    }
                });
            }
            
            document.getElementById('checkButton').textContent = 'Continue';
            document.getElementById('checkButton').onclick = () => window.close();
        }
    </script>
</body>
</html>