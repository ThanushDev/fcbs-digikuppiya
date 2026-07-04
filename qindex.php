<?php
require_once "db.php";
$quizResult = $conn->query("SELECT * FROM quizzes ORDER BY id");
if(!$quizResult || $quizResult->num_rows == 0) die("No quizzes found.");
$quizzes = [];
while($row = $quizResult->fetch_assoc()) {
  $quizzes[] = [
    'id' => intval($row['id']),
    'title' => htmlspecialchars($row['title']),
    'time_limit' => intval($row['time_limit']),
    'password' => htmlspecialchars($row['password'])
  ];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Quiz Portal</title>
<style>
/* ======= Base Modern Theme ======= */
body{margin:0;font-family:'Segoe UI',sans-serif;background:linear-gradient(180deg,#ecf0f1,#d6e1f2);color:#2c3e50;}
h2,h3{margin:0;}
button{transition:0.3s;}

/* Header */
.topbar{background:#34495e;color:#ecf0f1;padding:14px 28px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:99;box-shadow:0 2px 10px rgba(0,0,0,0.3);}
.title{font-size:22px;font-weight:600;}
.timer-container{text-align:right;}
.timer-text{font-weight:bold;font-size:16px;}
.timer-bar-container{background:#95a5a6;height:10px;border-radius:6px;width:200px;margin-top:4px;}
.timer-bar{height:100%;width:0%;background:linear-gradient(90deg,#2ecc71,#f1c40f);border-radius:6px;transition:width 0.3s;}
.timer-text.blink{color:#e74c3c;}

/* Layout */
.layout{display:flex;max-width:1200px;margin:auto;padding:20px;}
.quiz-area{flex:1;background:#fff;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.08);padding:24px;position:relative;}

/* Password / selection box */
.enroll-box{text-align:center;margin-top:40px;}
.enroll-box h2{margin-bottom:20px;}
/* enhanced select */
.enroll-box .custom-select {
  position: relative;
  width: 70%;
  margin: 0 auto 14px;
}
.enroll-box select{
  -webkit-appearance:none;
  -moz-appearance:none;
  appearance:none;
  width:100%;
  padding:14px 44px 14px 14px;
  font-size:16px;
  border-radius:10px;
  border:2px solid #bdc3c7;
  background:#fff;
  box-shadow:0 2px 6px rgba(0,0,0,0.08);
  transition:0.25s;
}
.enroll-box .select-arrow{
  position:absolute;
  right:12px;
  top:50%;
  transform:translateY(-50%);
  pointer-events:none;
  width:28px;
  height:28px;
  display:flex;
  align-items:center;
  justify-content:center;
  opacity:0.7;
}
.enroll-box select:hover{border-color:#3498db;}
.enroll-box select:focus{outline:none;border-color:#3498db;box-shadow:0 0 8px rgba(52,152,219,0.25);}

.enroll-box input{padding:12px;width:70%;margin-bottom:12px;border-radius:8px;border:1px solid #bdc3c7;font-size:16px;}
.enroll-box button{padding:12px 24px;background:#3498db;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:16px;}
.enroll-box button:hover{background:#2176bd;}

/* Question card */
.question-card{background:#f7f9fb;padding:20px;border-radius:12px;margin-bottom:20px;box-shadow:0 3px 10px rgba(0,0,0,0.05);}
.qtext{font-size:20px;margin-bottom:18px;font-weight:600;}
.options label{display:block;margin-bottom:10px;padding:10px;border:1px solid #d0d6de;border-radius:8px;background:#fff;cursor:pointer;}
.options label:hover{background:#e8eef5;}
.flag-btn{background:none;border:none;color:#f39c12;cursor:pointer;font-size:24px;position:absolute;right:30px;top:20px;}

/* Controls */
.controls{text-align:right;margin-top:20px;}
.controls button{margin-left:8px;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-size:15px;}
.controls .blue{background:#3498db;color:#fff;}
.controls .light{background:#95a5a6;color:#fff;}
.controls .danger{background:#e74c3c;color:#fff;}

/* Nav panel */
.nav-panel{position:fixed;top:120px;right:16px;width:160px;background:#fff;border:1px solid #ddd;padding:16px;border-radius:12px;box-shadow:0 3px 10px rgba(0,0,0,0.1);}
.nav-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;}
.nav-grid button{width:34px;height:34px;border-radius:6px;border:1px solid #ccc;background:#f9f9f9;cursor:pointer;font-size:14px;}
.nav-grid button.answered{background:#f1c40f;color:#fff;}
.nav-grid button.current{border:2px solid #3498db;}
.nav-grid button.flagged{background:#9b59b6;color:#fff;}
.nav-grid button.answered.correct{background:#2ecc71!important;}

/* Results */
.results-page{text-align:center;padding:20px;}
.results-page .summary-container{display:flex;justify-content:center;flex-wrap:wrap;gap:20px;margin-top:20px;}
.results-page .card{width:160px;padding:20px;border-radius:12px;font-size:18px;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
.card.total{background:#95a5a6;color:#fff;}
.card.answered{background:#f1c40f;color:#fff;}
.card.notanswered{background:#e74c3c;color:#fff;}
.card.correct{background:#2ecc71;color:#fff;}

/* Detailed Summary - auto show */
#detailedSummary{display:block;margin-top:24px;text-align:left;max-width:900px;margin-left:auto;margin-right:auto;}
.summary-question{background:#f4f7fa;padding:16px;border-radius:10px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.summary-question h4{margin:0 0 8px;font-size:18px;}
.summary-correct span{background:#2ecc71;color:#fff;padding:4px 8px;border-radius:5px;margin-right:5px;display:inline-block;margin-top:6px;}
.summary-selected span{background:#3498db;color:#fff;padding:4px 8px;border-radius:5px;margin-right:5px;display:inline-block;margin-top:6px;}
.summary-none{color:#888;font-style:italic;margin-top:6px;}

/* Center Alert Popup */
#submitAlert{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.8);background:#2ecc71;color:#fff;padding:30px 40px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.3);font-size:22px;font-weight:bold;opacity:0;transition:all 0.4s ease;z-index:2000;}
#submitAlert.show{opacity:1;transform:translate(-50%,-50%) scale(1);}

/* Responsive */
@media(max-width:900px){
  .layout{flex-direction:column;}
  .nav-panel{position:static;width:100%;margin-top:20px;}
  .nav-grid{grid-template-columns:repeat(auto-fit,minmax(34px,1fr));}
}
</style>
</head>
<body>
<header class="topbar">
  <div class="title" id="quizTitle">Select Quiz</div>
  <div class="timer-container" id="timerContainer" style="display:none;">
    <div id="timerDisplay" class="timer-text">--:--</div>
    <div class="timer-bar-container"><div id="timerBar" class="timer-bar"></div></div>
  </div>
</header>

<main class="layout">
<section class="quiz-area">

  <!-- Quiz Selection -->
  <div id="quizSelection" class="enroll-box">
    <h2>Select a Quiz</h2>
    <div class="custom-select">
      <select id="quizSelect">
        <option value="">-- Choose Quiz --</option>
        <?php foreach($quizzes as $q): ?>
          <option value="<?= $q['id'] ?>" data-time="<?= $q['time_limit'] ?>" data-pass="<?= $q['password'] ?>">
            <?= $q['title'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="select-arrow">▾</div>
    </div>

    <input id="quizPassword" type="password" placeholder="Enter Quiz Password">
    <button id="startQuizBtn">Start Quiz</button>
    <div id="errorMsg" style="color:red;margin-top:10px;"></div>
  </div>

  <!-- Quiz Interface -->
  <div id="quizInterface" style="display:none;">
    <div class="question-card">
      <button id="flagBtn" class="flag-btn" title="Flag">&#9873;</button>
      <div id="questionText" class="qtext"></div>
      <div id="optionsList" class="options"></div>
    </div>
    <div class="controls">
      <button id="prevBtn" class="blue">Previous</button>
      <button id="nextBtn" class="blue">Next</button>
      <button id="clearBtn" class="light">Clear</button>
      <button id="finishAttemptBtn" class="danger">Submit</button>
    </div>
  </div>

  <!-- Results -->
  <div id="resultsPage" style="display:none;" class="results-page">
    <h2>Quiz Summary</h2>
    <div class="summary-container">
      <div class="card total">Total<br><span id="res_totalQ">0</span></div>
      <div class="card answered">Answered<br><span id="res_answered">0</span></div>
      <div class="card notanswered">Not Answered<br><span id="res_notanswered">0</span></div>
      <div class="card correct">Correct<br><span id="res_marks">0</span></div>
    </div>
    <div id="detailedSummary"></div>
  </div>

  <div id="submitAlert">✅ Quiz Submitted!</div>
  <aside class="nav-panel" id="navPanel" style="display:none;">
    <div class="nav-grid" id="navGrid"></div>
  </aside>

</section>
</main>

<script>
let QUIZ_ID=null, QUIZ_TITLE="", QUIZ_DURATION=0;
let QUESTIONS=[],answers=[],flagged=[],current=0,timer,interval;

const quizSelection=document.getElementById('quizSelection');
const passwordInput=document.getElementById('quizPassword');
const startQuizBtn=document.getElementById('startQuizBtn');
const errorMsg=document.getElementById('errorMsg');
const quizInterface=document.getElementById('quizInterface');
const navPanel=document.getElementById('navPanel');
const resultsPage=document.getElementById('resultsPage');
const timerContainer=document.getElementById('timerContainer');
const timerDisplay=document.getElementById('timerDisplay');
const timerBar=document.getElementById('timerBar');
const quizTitle=document.getElementById('quizTitle');

startQuizBtn.onclick=()=>{
  errorMsg.textContent='';
  const sel=document.getElementById('quizSelect');
  const selected=sel.options[sel.selectedIndex];
  if(!selected.value){errorMsg.textContent="Please select a quiz.";return;}
  const entered=passwordInput.value.trim();
  const realPass=selected.dataset.pass;
  if(entered!==realPass){errorMsg.textContent="Incorrect password.";return;}
  QUIZ_ID=+selected.value;
  QUIZ_TITLE=selected.textContent.trim();
  QUIZ_DURATION=+selected.dataset.time*60;
  quizTitle.textContent=QUIZ_TITLE;
  quizSelection.style.display="none";
  quizInterface.style.display="block";
  navPanel.style.display="block";
  timerContainer.style.display="block";
  loadQuestions();
  startTimer();
};

async function loadQuestions(){
  const resp=await fetch(`qget_quiz.php?quiz_id=${QUIZ_ID}`);
  const data=await resp.json();
  QUESTIONS=(data.questions||[]).map(q=>{
    q.allow_multiple=q.allow_multiple==1||q.allow_multiple===true||q.allow_multiple==="1";
    q.options=(q.options||[]).map(o=>({id:+o.id,option_text:o.option_text,is_correct:(o.is_correct==1||o.is_correct==="1")?1:0}));
    return q;
  });
  answers=QUESTIONS.map(()=>[]);
  flagged=QUESTIONS.map(()=>false);
  buildNav();showQ(0);
}

function startTimer(){
  timer=QUIZ_DURATION;
  timerDisplay.textContent=format(timer);
  interval=setInterval(()=>{
    timer--;
    timerDisplay.textContent=format(timer);
    timerBar.style.width=((timer/QUIZ_DURATION)*100)+'%';
    if(timer<60) timerDisplay.classList.add('blink'); else timerDisplay.classList.remove('blink');
    if(timer<=0){clearInterval(interval);finishAttempt();}
  },1000);
}

function format(s){let m=Math.floor(s/60),sec=s%60;return `${m<10?"0":""}${m}:${sec<10?"0":""}${sec}`;}

function showQ(i){
  if(i<0||i>=QUESTIONS.length)return;
  current=i;
  const q=QUESTIONS[i];
  const qText=document.getElementById('questionText');
  const opts=document.getElementById('optionsList');
  qText.innerHTML=q.question_text;
  opts.innerHTML="";
  q.options.forEach(opt=>{
    const label=document.createElement('label');
    const input=document.createElement('input');
    input.type=q.allow_multiple?"checkbox":"radio";
    input.name="q"+i;input.value=opt.id;
    if(answers[i].includes(opt.id))input.checked=true;
    input.onchange=()=>{answers[i]=q.allow_multiple?
      Array.from(document.querySelectorAll(`input[name=q${i}]:checked`)).map(x=>+x.value):[+input.value];
      updateNav();
    };
    label.appendChild(input);
    label.append(' '+opt.option_text);
    opts.appendChild(label);
  });
  updateNav();
  document.getElementById('prevBtn').style.display = i===0 ? 'none' : 'inline-block';
  document.getElementById('nextBtn').style.display = i===QUESTIONS.length-1 ? 'none' : 'inline-block';
}

function buildNav(){
  const navGrid=document.getElementById('navGrid');
  navGrid.innerHTML="";
  QUESTIONS.forEach((_,i)=>{
    const b=document.createElement('button');
    b.textContent=i+1;b.onclick=()=>showQ(i);
    navGrid.appendChild(b);
  });
  updateNav();
}
function updateNav(){
  const navGrid=document.getElementById('navGrid');
  QUESTIONS.forEach((_,i)=>{
    const b=navGrid.children[i];
    b.className='';
    if(answers[i].length>0)b.classList.add('answered');
    if(i===current)b.classList.add('current');
    if(flagged[i])b.classList.add('flagged');
  });
}

document.getElementById('prevBtn').onclick=()=>showQ(current-1);
document.getElementById('nextBtn').onclick=()=>showQ(current+1);
document.getElementById('clearBtn').onclick=()=>{answers[current]=[];showQ(current);updateNav();};
document.getElementById('flagBtn').onclick=()=>{flagged[current]=!flagged[current];updateNav();};
document.getElementById('finishAttemptBtn').onclick=finishAttempt;

function finishAttempt(){
  clearInterval(interval);
  quizInterface.style.display="none";
  navPanel.style.display="none";
  resultsPage.style.display="block";
  document.getElementById('submitAlert').classList.add('show');
  setTimeout(()=>document.getElementById('submitAlert').classList.remove('show'),2500);

  let total=QUESTIONS.length,answered=0,correct=0,notanswered=0;
  QUESTIONS.forEach((q,i)=>{
    if(answers[i].length>0)answered++; else notanswered++;
    const correctIds=q.options.filter(o=>o.is_correct===1).map(o=>o.id).sort((a,b)=>a-b);
    const selected=(answers[i]||[]).map(Number).sort((a,b)=>a-b);
    const isCorrect=correctIds.length===selected.length&&correctIds.every((v,j)=>v===selected[j]);
    if(isCorrect)correct++;
  });
  document.getElementById('res_totalQ').textContent=total;
  document.getElementById('res_answered').textContent=answered;
  document.getElementById('res_notanswered').textContent=notanswered;
  document.getElementById('res_marks').textContent=correct;

  // Auto build detailed summary
  const detailed = document.getElementById('detailedSummary');
  detailed.innerHTML = "";
  QUESTIONS.forEach((q,i)=>{
    const div = document.createElement('div');
    div.className = 'summary-question';

    const correctOpts = q.options.filter(o => o.is_correct == 1).map(o => o.option_text);
    const selectedOpts = q.options.filter(o => (answers[i]||[]).includes(o.id)).map(o => o.option_text);

    let correctHTML = correctOpts.length > 0 ?
      `<div class="summary-correct"><strong>Correct:</strong> ${correctOpts.map(a=>`<span>${escapeHtml(a)}</span>`).join(' ')}</div>` :
      `<div class="summary-correct"><strong>Correct:</strong> <span class="summary-none">No answer marked correct</span></div>`;

    let selectedHTML = selectedOpts.length > 0 ?
      `<div class="summary-selected"><strong>Your Answer:</strong> ${selectedOpts.map(a=>`<span>${escapeHtml(a)}</span>`).join(' ')}</div>` :
      `<div class="summary-selected"><strong>Your Answer:</strong> <span class="summary-none">No answer</span></div>`;

    div.innerHTML = `<h4>Q${i+1}. ${escapeHtml(q.question_text)}</h4>${correctHTML}${selectedHTML}`;
    detailed.appendChild(div);
  });
}

// small helper to escape any inserted text
function escapeHtml(unsafe) {
  return String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
</script>
</body>
</html>
