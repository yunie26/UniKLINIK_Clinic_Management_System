<?php

function clinic_secret_questions()
{
  return array(
    "What is your mother's name?",
    "What was the name of your first pet?",
    "What city were you born in?",
    "What is the name of your primary school?",
    "What is your favourite food?"
  );
}

function clinic_is_valid_secret_question($question)
{
  $question = trim((string) $question);
  if ($question === '') {
    return false;
  }
  return in_array($question, clinic_secret_questions(), true);
}

function clinic_render_secret_question_select($name, $selected = '', $required = false, $extraAttrs = '')
{
  $requiredAttr = $required ? ' required' : '';
  $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-control secret-question-select"' . $requiredAttr;
  if ($extraAttrs !== '') {
    $html .= ' ' . $extraAttrs;
  }
  $html .= '>';
  $html .= '<option value="">-- Select a secret question --</option>';
  foreach (clinic_secret_questions() as $question) {
    $isSelected = ($selected === $question) ? ' selected' : '';
    $html .= '<option value="' . htmlspecialchars($question, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>';
    $html .= htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
    $html .= '</option>';
  }
  $html .= '</select>';
  return $html;
}

?>