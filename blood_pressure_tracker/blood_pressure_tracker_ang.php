<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blood Pressure Tracker - Browser Version</title>
  <style>
    :root{
      --bg-day:#f6f7fb;
      --bg-night:#121212;
      --text-day:#111;
      --text-night:#e0e0e0;
      --card-day:#fff;
      --card-night:#1e1e1e;
      --accent:#0f62fe;
    }
    body{font-family:Arial,sans-serif;margin:0;padding:20px;background:var(--bg-day);color:var(--text-day);transition:background 0.3s,color 0.3s}
    .night{background:var(--bg-night);color:var(--text-night)}
    .wrap{max-width:800px;margin:auto}
    h1,h2{margin:0 0 10px 0}
    form{background:var(--card-day);padding:16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);margin-bottom:20px;transition:background 0.3s}
    .night form{background:var(--card-night)}
    label{display:block;margin-top:10px;margin-bottom:4px;font-size:14px}
    input,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;font-size:14px}
    button{margin-top:12px;padding:10px 14px;background:var(--accent);color:#fff;border:none;border-radius:10px;cursor:pointer}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border-bottom:1px solid #eee;padding:6px;text-align:left}
    th{color:#555;font-size:13px}
    .stats{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .stat{background:var(--card-day);padding:10px;border-radius:10px;flex:1;min-width:120px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,0.05);transition:background 0.3s}
    .night .stat{background:var(--card-night)}
    #themeToggle{float:right;margin-bottom:10px}
  </style>
</head>
<body>
  <div class="wrap">
    <button id="themeToggle">Toggle Night Mode</button>
    <h1>Blood Pressure Tracker</h1>
    <form id="bpForm">
      <h2>Add Measurement</h2>
      <label for="systolic">Systolic (mmHg)</label>
      <input type="number" id="systolic" min="50" max="250" required>
      <label for="diastolic">Diastolic (mmHg)</label>
      <input type="number" id="diastolic" min="30" max="150" required>
      <label for="pulse">Pulse (bpm)</label>
      <input type="number" id="pulse" min="30" max="220" required>
      <label for="note">Note (optional)</label>
      <textarea id="note" maxlength="200" placeholder="e.g. after coffee, cold, stress"></textarea>
      <label for="taken_at">Date & Time</label>
      <input type="datetime-local" id="taken_at">
      <button type="submit">Save Measurement</button>
    </form>

    <div class="stats">
      <div class="stat"><div>Average Systolic</div><div id="avgSys">—</div></div>
      <div class="stat"><div>Average Diastolic</div><div id="avgDia">—</div></div>
      <div class="stat"><div>Average Pulse</div><div id="avgPulse">—</div></div>
    </div>

    <h2>Recent Measurements</h2>
    <table id="table">
      <thead><tr><th>Date</th><th>Systolic</th><th>Diastolic</th><th>Pulse</th><th>Note</th></tr></thead>
      <tbody></tbody>
    </table>

    <button id="exportBtn">Export CSV</button>
  </div>

  <script>
    const form = document.getElementById('bpForm');
    const tableBody = document.querySelector('#table tbody');
    const avgSys = document.getElementById('avgSys');
    const avgDia = document.getElementById('avgDia');
    const avgPulse = document.getElementById('avgPulse');
    const themeToggle = document.getElementById('themeToggle');

    function loadData(){
      const data = JSON.parse(localStorage.getItem('bpData')||'[]');
      renderTable(data);
      updateStats(data);
    }

    function saveData(entry){
      const data = JSON.parse(localStorage.getItem('bpData')||'[]');
      data.push(entry);
      localStorage.setItem('bpData', JSON.stringify(data));
      loadData();
    }

    function renderTable(data){
      tableBody.innerHTML='';
      if(data.length===0){
        tableBody.innerHTML='<tr><td colspan="5" style="color:#888">No records</td></tr>';
        return;
      }
      data.slice().reverse().forEach(d=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td>${d.taken_at}</td><td>${d.systolic}</td><td>${d.diastolic}</td><td>${d.pulse}</td><td>${d.note||''}</td>`;
        tableBody.appendChild(tr);
      });
    }

    function updateStats(data){
      if(data.length===0){avgSys.textContent='—';avgDia.textContent='—';avgPulse.textContent='—';return;}
      const avg = arr=>Math.round(arr.reduce((a,b)=>a+b,0)/arr.length);
      avgSys.textContent = avg(data.map(d=>d.systolic)) + ' mmHg';
      avgDia.textContent = avg(data.map(d=>d.diastolic)) + ' mmHg';
      avgPulse.textContent = avg(data.map(d=>d.pulse)) + ' bpm';
    }

    form.addEventListener('submit', e=>{
      e.preventDefault();
      const s = parseInt(document.getElementById('systolic').value);
      const d = parseInt(document.getElementById('diastolic').value);
      const p = parseInt(document.getElementById('pulse').value);
      const note = document.getElementById('note').value.trim().slice(0,200);
      const taken_at_input = document.getElementById('taken_at').value;
      const taken_at = taken_at_input ? taken_at_input : new Date().toLocaleString();
      saveData({systolic:s,diastolic:d,pulse:p,note, taken_at});
      form.reset();
    });

    document.getElementById('exportBtn').addEventListener('click', ()=>{
      const data = JSON.parse(localStorage.getItem('bpData')||'[]');
      if(data.length===0) return;
      let csv = 'timestamp,systolic,diastolic,pulse,note\n';
      data.forEach(d=>{csv += `${d.taken_at},${d.systolic},${d.diastolic},${d.pulse},${d.note}\n`});
      const blob = new Blob([csv], {type:'text/csv'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'bp_data.csv';
      document.body.appendChild(a); a.click(); a.remove();
      URL.revokeObjectURL(url);
    });

    themeToggle.addEventListener('click', ()=>{
      document.body.classList.toggle('night');
    });

    loadData();
  </script>
</body>
</html>
