<?php
require __DIR__ . '/db.php';

// --- Check for unique_id in URL ---
if (!isset($_GET['unique_id']) || trim($_GET['unique_id']) === '') {
    showErrorPage("لم يتم توفير معرف المستخدم (Unique ID).");
}

$uniqueId = mysqli_real_escape_string($conn, $_GET['unique_id']);

// --- Check if unique_id exists in users table ---
$userCheck = mysqli_query($conn, "SELECT * FROM users WHERE unique_id = '$uniqueId' LIMIT 1");
if (!$userCheck) {
    die("Database error: " . mysqli_error($conn));
}
$currentDateTime = date('Y-m-d H:i:s');
$updateQuery = "UPDATE users 
                    SET link_access_time = '$currentDateTime' 
                    WHERE unique_id = '$uniqueId'";
mysqli_query($conn, $updateQuery);




if (mysqli_num_rows($userCheck) === 0) {
    showErrorPage("المعرف الذي أدخلته غير صحيح أو غير موجود في سجلاتنا.");
}

$user = mysqli_fetch_assoc($userCheck);
// ✅ Check if already submitted
if (!empty($user) && $user['response_submitted'] == 1) {
    showErrorPage("لقد قمت بتعبئة هذا النموذج مسبقًا. شكرًا لمشاركتك.");
}
$userId = $user['id'];
$userName = $user['name'] ?? '';
$userPhone = $user['mobile_number'] ?? '';
// --- Fetch fields from DB ---
$textFieldsRes = mysqli_query($conn, "SELECT label, name FROM form_fields ORDER BY id ASC");
$questionsRes = mysqli_query($conn, "SELECT question, options, name FROM form_questions ORDER BY id ASC");

if (!$textFieldsRes || !$questionsRes) {
    http_response_code(500);
    die("DB query failed: " . mysqli_error($conn));
}

$textFields = [];
while ($row = mysqli_fetch_assoc($textFieldsRes)) {
    $textFields[] = $row;
}

$questions = [];
while ($row = mysqli_fetch_assoc($questionsRes)) {
    $questions[] = $row;
}

// --- Error page function ---
function showErrorPage($message)
{
?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">

    <head>
        <meta charset="UTF-8">
        <title>خطأ</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/x-icon" href="favicon.png">
        <link rel="stylesheet" href="style.css">
    </head>

    <body class="message-body">
        <div class="error-box">
            <h1>⚠️ حدث خطأ</h1>
            <span><?= htmlspecialchars($message) ?></span>
            <a href="/">العودة إلى الصفحة الرئيسية</a>
        </div>
    </body>

    </html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Ajdan Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="logo">
        <!-- <img src="ajdan-logo-white.png" alt="Ajdan Logo"> -->
        <h1>جمعية ملاك اجدان رايز</h1>
    </div>

    <div class="page-title">
        نموذج اختيار عضو جمعية "أجدان رايز" للترشح لعضوية مجلس الإدارة
    </div>

    <div class="form-description">
        حرصًا على تعزيز مشاركة الملاك في إدارة جمعية "أجدان رايز" وتحقيق أفضل تمثيل لمصالحهم، يسرنا دعوتكم لإبداء رغبتكم في الترشح لعضوية مجلس الإدارة، أو الاعتذار عن الترشح، وفق الضوابط والمعايير المعتمدة.
        علمًا بأن عدد المقاعد المتاحة هو (4) أعضاء، بالإضافة إلى منصب رئاسة الجمعية الذي تتولاه شركة صندوق أجدان للتطوير العقاري.
    </div>

    <div class="line"></div>

    <div class="form-wrapper">

        <form action="submit.php" method="post" autocomplete="off">
            <input type="hidden" name="unique_id" value="<?php echo htmlspecialchars($_GET['unique_id']); ?>">

            <div class="read-only-field">
                <?php foreach ($textFields as $f): ?>
                    <?php
                    $fieldName = strtolower($f['name']);
                    $prefillValue = '';

                    if ($fieldName === 'name') {
                        $prefillValue = htmlspecialchars($userName);
                    } elseif ($fieldName === 'mobile_number') {
                        $prefillValue = htmlspecialchars($userPhone);
                    }
                    ?>
                    <input
                        type="<?= strtolower($f['name']) === 'email' ? 'email' : 'text' ?>"
                        name="<?= htmlspecialchars($f['name']) ?>"
                        value="<?= $prefillValue ?>"
                        placeholder="<?= htmlspecialchars($f['label']) ?>"
                        readonly>
                <?php endforeach; ?>
            </div>

            <?php foreach ($questions as $q): ?>
                <div class="question-block">
                    <label class="label-title">
                        <?= htmlspecialchars($q['question']) ?>
                    </label>
                    <div>
                        <?php
                        $opts = array_filter(array_map('trim', explode(',', $q['options'])));
                        foreach ($opts as $i => $opt):
                            $radioId = htmlspecialchars($q['name'] . '_' . $i);
                        ?>
                            <div class="radio-buttons">
                                <input type="radio"
                                    id="<?= $radioId ?>"
                                    name="<?= htmlspecialchars($q['name']) ?>"
                                    value="<?= htmlspecialchars($opt) ?>"
                                    required>
                                <label for="<?= $radioId ?>"><?= htmlspecialchars($opt) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="screen_resolution" id="screen_resolution">

            <textarea name="desc" id="desc" rows="4"></textarea>

            <div class="checkbox-container">
                <input type="checkbox" name="newsletter" id="newsletter">
                <label for="newsletter">أوافق على سياسة التواصل من قبل فريق اجدان</label>
            </div>

            <!-- Static Section -->
            <div class="static-section">
                <span class="label-title">عايير المفاضلة بين المرشحي</span>
                <ul>
                    <li>المساحة المملوكة (تُعطى الأفضلية لمالك المساحة الأكبر).</li>
                    <li>الأقدمية (الأولوية للأقدم في التملك).</li>
                    <li>الإقامة (الأفضلية للمقيمين في البرج على غير المقيمين).</li>
                </ul>
            </div>

            <div class="line line-inside"></div>

            <!-- Submit & Reset Buttons -->
            <div class="buttons">
                <button type="submit" class="submit-btn">إرسال</button>
                <button type="button" onclick="window.location.href=window.location.href;" class="reset-btn">إعادة تعيين</button>
            </div>
        </form>

        <!-- Form Footer -->
        <div class="form-footer">
            920000658:في حال واجهتك أي مشكلة تقنية أثناء تعبئة النموذج، يرجى التواصل على الرقم
        </div>
    </div>

    <script>
        document.getElementById('screen_resolution').value = screen.width + 'x' + screen.height;
    </script>
</body>

</html>