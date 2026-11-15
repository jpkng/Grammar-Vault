const SpeechRecognitionCtor = (typeof window!=='undefined')
  ? (window.SpeechRecognition || window.webkitSpeechRecognition)
  : null;
const sttSupported = !!SpeechRecognitionCtor;
const STT_WATCHDOG_MS = 9000;
let sttListening = false;
let sttRecognizer = null;
let sttLastInterim = '';
let sttGotFinal = false;
let sttWatchdogTimer = null;
function sttCleanTranscript(text){
  let t = String(text||'').trim();
  t = t.replace(/[.!?]+$/,'');
  return t;
}
function sttClearWatchdog(){
  if(sttWatchdogTimer){ clearTimeout(sttWatchdogTimer); sttWatchdogTimer=null; }
}
function sttDiscard(rec){
  if(!rec) return;
  rec.onstart = rec.onresult = rec.onerror = rec.onend = null;
  try{ rec.abort(); }catch(e){}
}
function sttStart(onResult,onEnd,onError){
  if(!sttSupported){ if(onError)onError('unsupported'); return; }
  if(typeof stopSpeaking==='function') stopSpeaking();
  sttDiscard(sttRecognizer);
  sttClearWatchdog();
  sttListening = false;
  sttLastInterim = '';
  sttGotFinal = false;
  let rec;
  try{ rec = new SpeechRecognitionCtor(); }
  catch(e){ if(onError) onError('unsupported'); return; }
  try{ rec.lang = 'en-US'; }catch(e){}
  rec.continuous = false;
  rec.interimResults = true;
  try{ rec.maxAlternatives = 1; }catch(e){}
  const isCurrent = ()=> sttRecognizer===rec;
  rec.onstart = ()=>{
    if(!isCurrent())return;
    sttListening = true;
    sttClearWatchdog();
  };
  rec.onresult = (e)=>{
    if(!isCurrent())return;
    let text='', isFinal=false;
    for(let i=e.resultIndex;i<e.results.length;i++){
      text += e.results[i][0].transcript;
      if(e.results[i].isFinal) isFinal=true;
    }
    const clean = sttCleanTranscript(text);
    if(isFinal) sttGotFinal = true; else sttLastInterim = clean;
    if(onResult) onResult(clean, isFinal);
  };
  rec.onerror = (e)=>{
    if(!isCurrent())return;
    sttListening=false; sttClearWatchdog();
    if(onError) onError(e.error||'error');
  };
  rec.onend = ()=>{
    if(!isCurrent())return;
    sttListening=false; sttClearWatchdog(); sttRecognizer=null;
    if(!sttGotFinal && sttLastInterim && onResult) onResult(sttLastInterim, true);
    if(onEnd) onEnd();
  };
  sttRecognizer = rec;
  try{
    rec.start();
    sttWatchdogTimer = setTimeout(()=>{
      if(!isCurrent())return;
      sttDiscard(rec);
      sttListening=false; sttRecognizer=null; sttWatchdogTimer=null;
      if(onError) onError('timeout');
      if(onEnd) onEnd();
    }, STT_WATCHDOG_MS);
  }
  catch(e){
    sttListening=false; sttRecognizer=null;
    if(onError) onError('start-failed');
  }
}
function sttStop(){
  sttClearWatchdog();
  if(sttRecognizer){
    try{ sttRecognizer.stop(); }catch(e){}
  }
  sttListening = false;
}
function drawMicButton(x,y,r,listening,accentColor){
  const color = accentColor || '#7C5CFF';
  if(listening){
    const pulse = 1 + 0.16*Math.sin((typeof fc!=='undefined'?fc:0)*0.3);
    ctx.save();
    ctx.globalAlpha = 0.30;
    ctx.fillStyle = '#E14F4F';
    ctx.beginPath(); ctx.arc(x,y,r*1.4*pulse,0,Math.PI*2); ctx.fill();
    ctx.restore();
  }
  ctx.fillStyle = listening ? '#E14F4F' : '#FFFFFF';
  ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2); ctx.fill();
  ctx.strokeStyle = listening ? '#E14F4F' : color; ctx.lineWidth = 3;
  ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2); ctx.stroke();
  ctx.font = Math.round(r*1.1)+'px sans-serif';
  ctx.textAlign='center'; ctx.textBaseline='middle';
  ctx.fillText('\u{1F3A4}', x, y+1);
  ctx.textBaseline='alphabetic';
}
function micButtonHitbox(x,y,r){
  return {x:x-r, y:y-r, w:r*2, h:r*2};
}
