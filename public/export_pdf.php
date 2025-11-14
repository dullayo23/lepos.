<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Load DOMPDF library
require __DIR__ . '/../vendor/autoload.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

// Get lesson ID from URL
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die('Invalid lesson ID.');
}

// Fetch lesson plan and teacher details
[$stmt, $res] = q(
    'SELECT lp.*, u.name AS teacher 
     FROM lesson_plans lp 
     JOIN users u ON lp.user_id = u.id 
     WHERE lp.id = ?', 
    'i', [$id]
);

$lesson = $res->fetch_assoc();
if (!$lesson) {
    die('Lesson not found.');
}

// Build HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; line-height: 1.5; }
    h2 { text-align: center; color: #003366; }
    h3 { color: #003366; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    td, th { padding: 8px; border: 1px solid #ccc; }
    .section { margin-bottom: 15px; }
    .label { font-weight: bold; color: #333; }
  </style>
</head>
<body>
  <h2>LESOPO - Lesson Plan</h2>

  <div class="section">
    <p><span class="label">Teacher:</span> ' . htmlspecialchars($lesson['teacher']) . '</p>
    <p><span class="label">Subject:</span> ' . htmlspecialchars($lesson['subject']) . '</p>
    <p><span class="label">Class/Level:</span> ' . htmlspecialchars($lesson['class_level']) . '</p>
    <p><span class="label">Topic:</span> ' . htmlspecialchars($lesson['topic']) . '</p>
    <p><span class="label">Date Planned:</span> ' . htmlspecialchars($lesson['date_planned']) . '</p>
  </div>

  <hr>

  <h3>Lesson Objectives</h3>
  <p>' . nl2br(htmlspecialchars($lesson['objectives'])) . '</p>

  <h3>Teaching Aids</h3>
  <p>' . nl2br(htmlspecialchars($lesson['teaching_aids'])) . '</p>

  <h3>Teaching Methods</h3>
  <p>' . nl2br(htmlspecialchars($lesson['methods'])) . '</p>

  <h3>Lesson Steps / Development</h3>
  <p>' . nl2br(htmlspecialchars($lesson['lesson_steps'])) . '</p>

  <h3>Evaluation</h3>
  <p>' . nl2br(htmlspecialchars($lesson['evaluation'])) . '</p>

  <hr>

  <p><strong>Generated on:</strong> ' . date('d M Y, H:i') . '</p>
</body>
</html>
';

// Configure DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Stream PDF to browser (inline)
$filename = 'Lesson_Plan_' . $lesson['id'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit;
?>
