const RESULTS_API = 'results_api.php';
const RESULTS_QUEUE_KEY = 'gv_results_queue';
const RESULTS_QUEUE_MAX = 1000;
const RESULTS_BATCH = 50;
const RESULTS_RETRY_MS = 20000;
let gvRunId = '';
let gvQueue = [];
let gvSending = false;
function gvUid(){
  try{ if(window.crypto && crypto.randomUUID) return crypto.randomUUID(); }catch(e){}
  return 'id-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,10);
}
function gvLoadQueue(){
  try{
    const raw = localStorage.getItem(RESULTS_QUEUE_KEY);
    const q = raw ? JSON.parse(raw) : [];
    if(Array.isArray(q)) gvQueue = q;
  }catch(e){ gvQueue = []; }
}
function gvSaveQueue(){
  try{ localStorage.setItem(RESULTS_QUEUE_KEY, JSON.stringify(gvQueue)); }catch(e){}
}
function gvResultsNewRun(){ gvRunId = gvUid(); return gvRunId; }
function gvResultsSetRun(id){ gvRunId = id || gvUid(); return gvRunId; }
function gvResultsRunId(){ if(!gvRunId) gvResultsNewRun(); return gvRunId; }
function gvTeacherName(){
  return (typeof playerTeacher!=='undefined' && playerTeacher) ? String(playerTeacher) : '';
}
function gvStudentNo(){
  return (typeof playerStudent!=='undefined' && playerStudent && playerStudent.student_no) ? String(playerStudent.student_no) : '';
}
function gvEnqueue(ev){
  ev.id = gvUid();
  ev.run = gvResultsRunId();
  const sno = gvStudentNo();
  if(sno){
    ev.student = sno;
    ev.token = playerStudent.token || '';
  } else {
    const name = (typeof playerName!=='undefined' && playerName) ? playerName : '';
    if(!name) return;
    ev.name = name;
    ev.grade = (typeof playerGrade!=='undefined') ? playerGrade : 0;
    ev.teacher = gvTeacherName();
    if(!ev.teacher) return;
  }
  gvQueue.push(ev);
  if(gvQueue.length > RESULTS_QUEUE_MAX) gvQueue.splice(0, gvQueue.length-RESULTS_QUEUE_MAX);
  gvSaveQueue();
  gvFlush();
}
async function gvFlush(){
  if(gvSending || !gvQueue.length) return;
  gvSending = true;
  try{
    while(gvQueue.length){
      const batch = gvQueue.slice(0, RESULTS_BATCH);
      const cur = gvTeacherName();
      batch.forEach(ev=>{ if(!ev.teacher && !ev.cls && cur) ev.teacher = cur; });
      const res = await fetch(RESULTS_API, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({events: batch}),
      });
      const data = await res.json();
      if(!data || !data.success) break;
      gvQueue.splice(0, batch.length);
      gvSaveQueue();
    }
  }catch(e){  }
  gvSending = false;
}
async function gvCheckTeacher(name){
  try{
    const res = await fetch(RESULTS_API, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'check_class', teacher:name}),
    });
    if(res.status===429) return {ok:false, limited:true};
    const data = await res.json();
    if(data && data.success) return {ok:true, valid:!!data.valid, teacher:data.teacher||''};
  }catch(e){  }
  return {ok:false};
}
async function gvSuggestTeachers(text){
  try{
    const res = await fetch(RESULTS_API, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'suggest_teachers', teacher:text}),
    });
    if(res.status===429) return {ok:false, limited:true};
    const data = await res.json();
    if(data && data.success) return {ok:true, matches:Array.isArray(data.matches)?data.matches:[], exact:data.exact||null};
  }catch(e){  }
  return {ok:false};
}
async function gvStudentCall(payload){
  try{
    const res = await fetch('student_api.php', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload),
    });
    const data = await res.json();
    if(data && data.success) return {ok:true, student:data.student, token:data.token};
    return {ok:false, error:(data && data.error) || 'Something went wrong.'};
  }catch(e){ return {ok:false, error:'offline'}; }
}
function gvStudentLogin(ident, password){ return gvStudentCall({action:'login', id:ident, password:password}); }
function gvStudentRegister(f){ return gvStudentCall(Object.assign({action:'register'}, f)); }
function gvStudentWhoAmI(token){ return gvStudentCall({action:'whoami', token:token}); }
function gvLogAnswer(npc, given, correct, extra){
  gvEnqueue({
    seconds: extra && typeof extra.seconds==='number' ? Math.round(extra.seconds*10)/10 : undefined,
    timed_out: !!(extra && extra.timedOut),
    type:'answer',
    level: (typeof lvlIdx!=='undefined' ? lvlIdx+1 : 1),
    category: npc.category,
    question: npc.q,
    qtype: npc.type,
    given: String(given==null?'':given),
    correct: !!correct,
  });
}
function gvLogLevelComplete(){
  const st = (typeof levelStats!=='undefined' && levelStats[lvlIdx]) || {correct:0,total:0};
  const stars = (typeof getLevelStars==='function') ? getLevelStars(st.correct, st.total).stars : 0;
  gvEnqueue({type:'level_complete', level:lvlIdx+1, correct:st.correct, total:st.total, stars});
}
function gvLogRunFinished(){
  gvEnqueue({type:'run_finished', score, total: totalAnswered});
}
gvLoadQueue();
setInterval(gvFlush, RESULTS_RETRY_MS);
window.addEventListener('online', gvFlush);
setTimeout(gvFlush, 1500);
