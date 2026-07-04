<?php
session_start();
require_once "db.php";
// REMOVED: Authentication check to allow direct access

$success_msg = isset($_GET['msg']) ? $_GET['msg'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $title = $_POST['quiz_title'] ?? '';
    $time_limit = intval($_POST['time_limit'] ?? 10);
    $stmt = $conn->prepare("INSERT INTO quizzes (title, time_limit) VALUES (?, ?)");
    $stmt->bind_param("si", $title, $time_limit);
    $stmt->execute();
    $success_msg = "Quiz '$title' created successfully!";
    header("Location: qadmin.php?msg=" . urlencode($success_msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $quiz_id = intval($_POST['quiz_id']);
    $qtext = $_POST['question_text'];
    $allow_multi = isset($_POST['allow_multiple']) ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, allow_multiple) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $quiz_id, $qtext, $allow_multi);
    $stmt->execute();
    $qid = $stmt->insert_id;
    if (!empty($_POST['option_text'])) {
        foreach ($_POST['option_text'] as $idx => $optText) {
            $isCorrect = (isset($_POST['is_correct'][$idx]) && $_POST['is_correct'][$idx] === 'on') ? 1 : 0;
            $stmt2 = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            $stmt2->bind_param("isi", $qid, $optText, $isCorrect);
            $stmt2->execute();
        }
    }
    $success_msg = "Question added successfully!";
    header("Location: qadmin.php?msg=" . urlencode($success_msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_password'])) {
    $quiz_id = intval($_POST['quiz_id_password']);
    $password = $_POST['quiz_password'];
    $stmt = $conn->prepare("UPDATE quizzes SET password=? WHERE id=?");
    $stmt->bind_param("si", $password, $quiz_id);
    $stmt->execute();
    $success_msg = "Password updated successfully!";
    header("Location: qadmin.php?msg=" . urlencode($success_msg));
    exit;
}

$quizzesResult = $conn->query("SELECT * FROM quizzes ORDER BY id DESC");
$quizzes = $quizzesResult ? $quizzesResult->fetch_all(MYSQLI_ASSOC) : [];

$quizTitles = [];
foreach ($quizzes as $q) { $quizTitles[$q['id']] = $q['title']; }

$questionsResult = $conn->query("SELECT q.*, (SELECT COUNT(*) FROM options o WHERE o.question_id=q.id) AS opt_count FROM questions q ORDER BY q.id DESC");
$questions = $questionsResult ? $questionsResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel — Quiz Manager</title>
<style>
/* ===== Global Styles ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 20px;
    background: linear-gradient(135deg,#f5f7fa,#c3cfe2);
    color: #333;
}
h1, h3 { margin: 0 0 12px 0; font-weight: 600; color: #2c3e50; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }

.box {
    background: #fff;
    padding: 24px;
    margin: 20px 0;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.box:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}

label { display:block; margin: 10px 0 6px 0; font-weight: 500; color: #34495e; }
input[type="text"], textarea, select, input[type="number"] {
    width: 100%; padding: 12px; margin-bottom: 12px;
    border: 1px solid #ccc; border-radius: 8px; font-size: 15px;
    box-sizing: border-box; transition: border 0.2s, box-shadow 0.2s;
}
input[type="text"]:focus, textarea:focus, select:focus, input[type="number"]:focus {
    border-color: #3498db; box-shadow: 0 0 6px rgba(52,152,219,0.3); outline: none;
}
textarea { resize: vertical; min-height: 60px; }

button {
    padding: 12px 20px; border: none; border-radius: 8px;
    background: #3498db; color: #fff; font-size: 15px; cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
button:hover { background: #2176bd; transform: translateY(-2px); }
button.light { background: #95a5a6; }
button.light:hover { background: #7f8c8d; }
button.danger { background: #e74c3c; }
button.danger:hover { background: #c0392b; }

.opt-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
.opt-row input[type="text"] { flex: 1; }

table {
    width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 14px;
    background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; }
th { background: #3498db; color: #fff; font-weight: 500; }
tr:hover { background: #f0f8ff; }

.user-info { margin: 10px 0; font-size: 14px; }

#addOptionBtn { background: #28a745; }
#addOptionBtn:hover { background: #218838; }

#alertBox {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
    padding: 14px 22px; background: #28a745; color: #fff; border-radius: 10px;
    display: none; z-index: 999; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

@media (max-width: 768px){
    .opt-row { flex-direction: column; align-items: flex-start; }
    table, th, td { font-size: 13px; }
    .box { padding: 16px; }
}
</style>
</head>
<body>
<h1>Admin Panel — Quiz Manager</h1>
<p class="user-info">Public Admin Access — Management Mode</p>

<div class="box">
  <h3>Create New Quiz</h3>
  <form method="post">
    <label>Quiz Title</label>
    <input type="text" name="quiz_title" placeholder="Enter quiz title" required>
    <label>Time Limit (minutes)</label>
    <input type="number" name="time_limit" value="10" required>
    <button name="create_quiz">Create Quiz</button>
  </form>
</div>

<div class="box">
  <h3>Add Question</h3>
  <form method="post">
    <label>Select Quiz</label>
    <select name="quiz_id" required>
      <option value="">Select Quiz</option>
      <?php foreach($quizzes as $q): ?>
        <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['title']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Question Text</label>
    <textarea name="question_text" placeholder="Enter question text" required></textarea>
    <label><input type="checkbox" name="allow_multiple"> Allow multiple correct answers</label>

    <div id="optionsContainer">
      <div class="opt-row">
        <input type="text" name="option_text[]" placeholder="Option text" required>
        <label><input type="checkbox" name="is_correct[0]"> Correct</label>
        <button type="button" class="delOpt">X</button>
      </div>
      <div class="opt-row">
        <input type="text" name="option_text[]" placeholder="Option text" required>
        <label><input type="checkbox" name="is_correct[1]"> Correct</label>
        <button type="button" class="delOpt">X</button>
      </div>
    </div>
    <button type="button" id="addOptionBtn">Add Option</button>
    <br><br>
    <button name="add_question">Add Question</button>
  </form>
</div>

<div class="box">
  <h3>Set Quiz Password</h3>
  <table>
    <thead>
      <tr>
        <th>Quiz Title</th>
        <th>Current Password</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($quizzes as $q): ?>
      <tr>
        <form method="post">
          <td><?= htmlspecialchars($q['title']) ?></td>
          <td><input type="text" name="quiz_password" value="<?= htmlspecialchars($q['password']) ?>"></td>
          <td>
            <input type="hidden" name="quiz_id_password" value="<?= $q['id'] ?>">
            <button type="submit">Update</button>
            <a href="quiz_delete.php?id=<?= $q['id'] ?>" class="danger">Remove Full Quiz</a>
          </td>
        </form>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="box">
  <h3>Existing Questions</h3>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Quiz</th>
        <th>Question</th>
        <th>Options</th>
        <th>Multiple Correct</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($questions as $qq): ?>
      <tr>
        <td><?= $qq['id'] ?></td>
        <td><?= htmlspecialchars($quizTitles[$qq['quiz_id']] ?? 'Unknown') ?></td>
        <td><?= htmlspecialchars(substr($qq['question_text'],0,120)) ?></td>
        <td><?= $qq['opt_count'] ?></td>
        <td><?= $qq['allow_multiple'] ? 'Yes' : 'No' ?></td>
        <td>
          <a href="question_edit.php?id=<?= $qq['id'] ?>">Edit</a> |
          <a href="question_delete.php?id=<?= $qq['id'] ?>" class="danger">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div id="alertBox"></div>

<script>
let optIndex = document.querySelectorAll('#optionsContainer .opt-row').length;
document.getElementById('addOptionBtn').addEventListener('click', () => {
  const cont = document.getElementById('optionsContainer');
  const div = document.createElement('div');
  div.className = 'opt-row';
  div.innerHTML = `<input type="text" name="option_text[]" placeholder="Option text" required>
    <label><input type="checkbox" name="is_correct[${optIndex}]"> Correct</label>
    <button type="button" class="delOpt">X</button>`;
  cont.appendChild(div);
  div.querySelector('.delOpt').addEventListener('click', () => div.remove());
  optIndex++;
});

document.querySelectorAll('.delOpt').forEach(btn=>{
  btn.addEventListener('click', function(){ this.parentElement.remove(); });
});

function showAlert(msg, success=true){
  const box = document.getElementById('alertBox');
  box.innerText = msg;
  box.style.background = success ? '#28a745' : '#dc3545';
  box.style.display = 'block';
  setTimeout(()=>box.style.display='none', 3000);
}

document.querySelectorAll('a.danger').forEach(a=>{
  a.addEventListener('click', function(e){
    e.preventDefault();
    if(confirm(`Are you sure you want to delete this? This action cannot be undone.`)){
      window.location.href = a.href;
    }
  });
});

<?php if($success_msg): ?>
showAlert("<?= addslashes($success_msg) ?>");
<?php endif; ?>
</script>
</body>
</html>