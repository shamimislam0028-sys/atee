
// Simple static site with LocalStorage. Supports CSV import; XLSX import if SheetJS is available.

const LS_KEY_EMP = 'employees';
const LS_KEY_ATT = 'attendance';

let employees = {}; // { id: {employee_id, name, department, role, join_date, status} }
let attendance = []; // [{employee_id, date, clock_in, clock_out, hours}]
let currentID = null;

function loadData(){
  try{
    employees = JSON.parse(localStorage.getItem(LS_KEY_EMP) || '{}');
    attendance = JSON.parse(localStorage.getItem(LS_KEY_ATT) || '[]');
  }catch(e){ employees = {}; attendance = []; }
  if(Object.keys(employees).length === 0){
    // seed sample
    employees = {
      'E001': {employee_id:'E001', name:'Shamim', department:'Operations', role:'Associate', join_date:'2024-01-15', status:'Active'},
      'E002': {employee_id:'E002', name:'Rafi', department:'Sales', role:'Executive', join_date:'2023-09-10', status:'Active'},
      'E003': {employee_id:'E003', name:'Nadia', department:'HR', role:'Officer', join_date:'2022-06-01', status:'Inactive'},
    };
  }
  if(attendance.length === 0){
    attendance = [
      {employee_id:'E001', date:'2025-11-28', clock_in:'09:02', clock_out:'18:05', hours:computeHours('09:02','18:05')},
      {employee_id:'E001', date:'2025-11-29', clock_in:'09:15', clock_out:'17:45', hours:computeHours('09:15','17:45')},
      {employee_id:'E002', date:'2025-11-28', clock_in:'10:00', clock_out:'19:10', hours:computeHours('10:00','19:10')},
      {employee_id:'E003', date:'2025-11-26', clock_in:'08:52', clock_out:'16:30', hours:computeHours('08:52','16:30')},
    ];
  }
}

function saveData(){
  localStorage.setItem(LS_KEY_EMP, JSON.stringify(employees));
  localStorage.setItem(LS_KEY_ATT, JSON.stringify(attendance));
}

function populateEmployeeSelect(){
  const sel = document.getElementById('employeeSelect');
  sel.innerHTML = '';
  Object.values(employees).sort((a,b)=>a.employee_id.localeCompare(b.employee_id)).forEach(emp => {
    const opt = document.createElement('option');
    opt.value = emp.employee_id; opt.textContent = `${emp.employee_id} — ${emp.name || ''}`; sel.appendChild(opt);
  });
  if(!currentID){ currentID = sel.value || Object.keys(employees)[0]; }
  sel.value = currentID;
}

function computeHours(clock_in, clock_out){
  const parse = s => { if(!s) return null; const parts = s.split(':'); return {h:parseInt(parts[0]||'0'), m:parseInt(parts[1]||'0'), s:parseInt(parts[2]||'0')}; };
  const ci = parse(clock_in), co = parse(clock_out); if(!ci||!co) return null;
  let inMin = ci.h*60+ci.m+(ci.s/60), outMin = co.h*60+co.m+(co.s/60);
  if(outMin < inMin) outMin += 24*60; // overnight
  const hrs = (outMin - inMin)/60; return Math.round(hrs*100)/100;
}

function hoursForEmployee(id){
  return attendance.filter(r=>r.employee_id===id && typeof r.hours==='number').reduce((sum,r)=>sum+r.hours,0);
}

function hoursForEmployeeFiltered(id, from, to){
  if(!from || !to) return null;
  const fromD = new Date(from), toD = new Date(to);
  return attendance.filter(r=>r.employee_id===id && r.date && (new Date(r.date)>=fromD) && (new Date(r.date)<=toD) && typeof r.hours==='number')
                   .reduce((sum,r)=>sum+r.hours,0);
}

function refreshProfile(){
  const emp = employees[currentID]; if(!emp) return;
  document.getElementById('empName').textContent = emp.name || '-';
  document.getElementById('empDept').textContent = emp.department || '-';
  document.getElementById('empRole').textContent = emp.role || '-';
  document.getElementById('empStatus').textContent = emp.status || '-';
  document.getElementById('empJoin').textContent = emp.join_date || '-';
  document.getElementById('hoursAll').textContent = hoursForEmployee(currentID).toFixed(2);
  const from = document.getElementById('fromDate').value, to = document.getElementById('toDate').value;
  const fh = hoursForEmployeeFiltered(currentID, from, to);
  document.getElementById('hoursFiltered').textContent = fh===null?'-':fh.toFixed(2);
}

function refreshAttendance(){
  const rows = attendance.filter(r=>r.employee_id===currentID).sort((a,b)=> new Date(b.date) - new Date(a.date));
  const tbody = document.querySelector('#attendanceTable tbody');
  tbody.innerHTML = '';
  rows.slice(0,10).forEach(r=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${r.date||''}</td><td>${r.clock_in||''}</td><td>${r.clock_out||''}</td><td>${(r.hours??'').toString()}</td>`;
    tbody.appendChild(tr);
  });
}

function clockIn(){
  const now = new Date();
  const date = now.toISOString().slice(0,10);
  const hh = String(now.getHours()).padStart(2,'0');
  const mm = String(now.getMinutes()).padStart(2,'0');
  const cin = `${hh}:${mm}`;
  let rec = attendance.find(r=>r.employee_id===currentID && r.date===date);
  if(rec){ rec.clock_in = cin; rec.hours = computeHours(rec.clock_in, rec.clock_out); }
  else{ attendance.unshift({employee_id:currentID, date, clock_in:cin, clock_out:null, hours:null}); }
  saveData(); refreshProfile(); refreshAttendance();
}

function clockOut(){
  const now = new Date();
  const date = now.toISOString().slice(0,10);
  const hh = String(now.getHours()).padStart(2,'0');
  const mm = String(now.getMinutes()).padStart(2,'0');
  const cout = `${hh}:${mm}`;
  let rec = attendance.find(r=>r.employee_id===currentID && r.date===date);
  if(!rec){ rec = {employee_id:currentID, date, clock_in:null, clock_out:cout, hours:null}; attendance.unshift(rec); }
  else{ rec.clock_out = cout; }
  rec.hours = computeHours(rec.clock_in, rec.clock_out);
  saveData(); refreshProfile(); refreshAttendance();
}

function parseCSV(text){
  const lines = text.split(/?
/).filter(l=>l.trim().length>0);
  const headers = lines[0].split(',').map(h=>h.trim());
  const rows = lines.slice(1).map(line=>{
    const cols = line.split(',');
    const obj = {}; headers.forEach((h,i)=> obj[h] = (cols[i]||'').trim()); return obj;
  });
  return {headers, rows};
}

async function importFile(){
  const input = document.getElementById('fileInput');
  const status = document.getElementById('importStatus');
  if(!input.files[0]){ status.textContent = 'Please select a .xlsx or .csv file.'; return; }
  const file = input.files[0];
  const ext = file.name.toLowerCase().split('.').pop();
  try{
    if(ext === 'xlsx'){
      if(typeof XLSX === 'undefined'){ status.textContent = 'XLSX library not loaded. Please stay online or use CSV.'; return; }
      const data = await file.arrayBuffer();
      const wb = XLSX.read(data, {type:'array'});
      const empSheet = wb.Sheets['Employees'];
      const attSheet = wb.Sheets['Attendance'];
      if(!empSheet || !attSheet){ status.textContent = 'Sheet names must be Employees and Attendance.'; return; }
      const dfEmp = XLSX.utils.sheet_to_json(empSheet, {defval:''});
      const dfAtt = XLSX.utils.sheet_to_json(attSheet, {defval:''});
      dfEmp.forEach(r=>{
        const id = String(r.EmployeeID||'').trim(); if(!id) return;
        employees[id] = {
          employee_id:id,
          name: String(r.Name||'').trim(),
          department: String(r.Department||'').trim(),
          role: String(r.Role||'').trim(),
          join_date: String(r.JoinDate||'').trim(),
          status: String(r.Status||'').trim() || 'Active',
        };
      });
      dfAtt.forEach(r=>{
        const eid = String(r.EmployeeID||'').trim(); const d = String(r.Date||'').trim();
        const cin = String(r.ClockIn||'').trim(); const cout = String(r.ClockOut||'').trim();
        const hrs = computeHours(cin, cout);
        attendance.push({employee_id:eid, date:d, clock_in:cin, clock_out:cout, hours:hrs});
      });
      status.textContent = `Imported: ${dfEmp.length} employees, ${dfAtt.length} attendance rows.`;
    } else if(ext === 'csv'){
      const text = await file.text();
      const {headers, rows} = parseCSV(text);
      const lower = headers.map(h=>h.toLowerCase());
      if(lower.includes('employeeid') && lower.includes('name')){
        // Employees CSV
        rows.forEach(r=>{
          const id = String(r.EmployeeID||r.employeeid||'').trim(); if(!id) return;
          employees[id] = {
            employee_id:id,
            name: String(r.Name||r.name||'').trim(),
            department: String(r.Department||r.department||'').trim(),
            role: String(r.Role||r.role||'').trim(),
            join_date: String(r.JoinDate||r.joindate||'').trim(),
            status: String(r.Status||r.status||'').trim() || 'Active',
          };
        });
        status.textContent = `Imported Employees: ${rows.length}`;
      } else {
        // Attendance CSV: EmployeeID,Date,ClockIn,ClockOut
        rows.forEach(r=>{
          const eid = String(r.EmployeeID||r.employeeid||'').trim(); const d = String(r.Date||r.date||'').trim();
          const cin = String(r.ClockIn||r.clockin||'').trim(); const cout = String(r.ClockOut||r.clockout||'').trim();
          const hrs = computeHours(cin, cout);
          attendance.push({employee_id:eid, date:d, clock_in:cin, clock_out:cout, hours:hrs});
        });
        status.textContent = `Imported Attendance rows: ${rows.length}`;
      }
    } else {
      status.textContent = 'Unsupported file type.'; return;
    }
    saveData(); populateEmployeeSelect(); refreshProfile(); refreshAttendance();
  } catch(e){ status.textContent = 'Import failed: '+ e.message; }
}

function bindEvents(){
  document.getElementById('employeeSelect').addEventListener('change', e=>{ currentID = e.target.value; refreshProfile(); refreshAttendance(); });
  document.getElementById('clockInBtn').addEventListener('click', clockIn);
  document.getElementById('clockOutBtn').addEventListener('click', clockOut);
  document.getElementById('applyFilter').addEventListener('click', ()=>{ refreshProfile(); });
  document.getElementById('importBtn').addEventListener('click', importFile);
  document.getElementById('addEmpForm').addEventListener('submit', (e)=>{
    e.preventDefault();
    const id = document.getElementById('newEmpID').value.trim(); if(!id){ alert('EmployeeID required'); return; }
    employees[id] = {
      employee_id:id,
      name: document.getElementById('newEmpName').value.trim(),
      department: document.getElementById('newEmpDept').value.trim(),
      role: document.getElementById('newEmpRole').value.trim(),
      join_date: document.getElementById('newEmpJoin').value,
      status: document.getElementById('newEmpStatus').value,
    };
    saveData(); populateEmployeeSelect(); refreshProfile(); refreshAttendance();
    document.getElementById('addEmpForm').reset();
  });
}

window.addEventListener('DOMContentLoaded', ()=>{
  loadData(); populateEmployeeSelect(); bindEvents(); refreshProfile(); refreshAttendance();
});
