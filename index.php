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
        <style>
            body {
                font-family: 'Cairo', sans-serif;
                background: #0d141f;
                color: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                text-align: center;
            }

            .form-description {
                font-size: 15px;
                color: #ddd;
                max-width: 720px;
                text-align: center;
                line-height: 1.6;
                margin-bottom: 25px;
            }

            .error-box {
                background: rgba(255, 255, 255, 0.05);
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
                max-width: 400px;
            }

            h1 {
                font-size: 22px;
                margin-bottom: 15px;
            }

            a {
                display: inline-block;
                margin-top: 15px;
                padding: 10px 20px;
                background: #00b4a2;
                color: #fff;
                text-decoration: none;
                border-radius: 8px;
            }

            a:hover {
                background: #00c8b2;
            }
        </style>
    </head>

    <body>
        <div class="error-box">
            <h1>⚠️ حدث خطأ</h1>
            <p><?= htmlspecialchars($message) ?></p>
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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .reset-btn {
            background: linear-gradient(135deg, #777, #555);
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
            flex: 1;
        }

        .reset-btn:hover {
            background: linear-gradient(135deg, #999, #777);
        }

        body {
            margin: 0;
            font-family: 'Cairo', sans-serif;
            background: #0d141f url('https://darah.ajdan.com/assets/images/bg-pattern.png') repeat;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            flex-direction: column;
        }

        .page-title {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #00b4a2;
        }

        .form-description {
            font-size: 15px;
            color: #ddd;
            max-width: 720px;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .form-wrapper {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 40px 35px;
            border-radius: 16px;
            max-width: 720px;
            width: 100%;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            max-height: 80px;
        }

        .form-title {
            text-align: center;
            font-size: 22px;
            margin-bottom: 30px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        input[type="text"],
        input[type="email"],
        select {
            background-color: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 14px;
            color: #fff;
            font-size: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }

        input:focus,
        select:focus {
            border-color: #00b4a2;
            outline: none;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .checkbox-container {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .static-section {
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            color: #ddd;
            line-height: 1.6;
        }

        .submit-btn {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #00b4a2, #009b88);
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
        }

        .form-footer {
            margin-top: 15px;
            font-size: 13px;
            text-align: center;
            color: #bbb;
        }
    </style>
</head>

<body>

    <div class="page-title">
        نموذج اختيار عضو جمعية "أجدان رايز" للترشح لعضوية مجلس الإدارة
    </div>

    <div class="form-description">
        حرصًا على تعزيز مشاركة الملاك في إدارة جمعية "أجدان رايز" وتحقيق أفضل تمثيل لمصالحهم، يسرنا دعوتكم لإبداء رغبتكم في الترشح لعضوية مجلس الإدارة، أو الاعتذار عن الترشح، وفق الضوابط والمعايير المعتمدة.
        علمًا بأن عدد المقاعد المتاحة هو (4) أعضاء، بالإضافة إلى منصب رئاسة الجمعية الذي تتولاه شركة صندوق أجدان للتطوير العقاري.
    </div>

    <div class="form-wrapper">
        <div class="logo">
            <img src="ajdan_logo.png" alt="Ajdan Logo">
        </div>

        <form action="submit.php" method="post" autocomplete="off">
            <input type="hidden" name="unique_id" value="<?php echo htmlspecialchars($_GET['unique_id']); ?>">

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

            <?php foreach ($questions as $q): ?>
                <div class="question-block" style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars($q['question']) ?>
                    </label>
                    <?php
                    $opts = array_filter(array_map('trim', explode(',', $q['options'])));
                    foreach ($opts as $i => $opt):
                        $radioId = htmlspecialchars($q['name'] . '_' . $i);
                    ?>
                        <div style="margin-bottom:6px;">
                            <input type="radio"
                                id="<?= $radioId ?>"
                                name="<?= htmlspecialchars($q['name']) ?>"
                                value="<?= htmlspecialchars($opt) ?>"
                                required>
                            <label for="<?= $radioId ?>" style="margin-right:6px;"><?= htmlspecialchars($opt) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="screen_resolution" id="screen_resolution">

            <div class="checkbox-container">
                <input type="checkbox" name="newsletter" id="newsletter">
                <label for="newsletter">أوافق على سياسة التواصل من قبل فريق اجدان</label>
            </div>

            <!-- Static Section -->
            <div class="static-section">
                <strong>عايير المفاضلة بين المرشحي</strong><br><br>
                المساحة المملوكة (تُعطى الأفضلية لمالك المساحة الأكبر).<br><br>
                الأقدمية (الأولوية للأقدم في التملك).<br><br>
                الإقامة (الأفضلية للمقيمين في البرج على غير المقيمين).
            </div>

            <!-- Submit & Reset Buttons -->
            <div style="grid-column: 1 / -1; display: flex; gap: 10px;">
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