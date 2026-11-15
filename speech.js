const ttsSupported = (typeof window!=='undefined' && 'speechSynthesis' in window);
let ttsVoice = null;
let ttsSpeaking = false;
let ttsVoicesReady = false;
const TTS_PREFERRED_VOICE_NAMES = [
  'Google US English', 'Samantha', 'Microsoft Aria Online (Natural) - English (United States)',
  'Microsoft Jenny Online (Natural) - English (United States)', 'Karen', 'Moira', 'Tessa', 'Google UK English Female',
];
function ttsPickVoice(){
  if(!ttsSupported) return null;
  const voices = window.speechSynthesis.getVoices();
  if(!voices || !voices.length) return null;
  for(const name of TTS_PREFERRED_VOICE_NAMES){
    const hit = voices.find(v=>v.name===name);
    if(hit) return hit;
  }
  const enUS = voices.find(v=>/^en-US/i.test(v.lang) && /female/i.test(v.name));
  if(enUS) return enUS;
  const anyEnUS = voices.find(v=>/^en-US/i.test(v.lang));
  if(anyEnUS) return anyEnUS;
  const anyEn = voices.find(v=>/^en/i.test(v.lang));
  if(anyEn) return anyEn;
  return voices[0];
}
function ttsInit(){
  if(!ttsSupported) return;
  const refresh = ()=>{ ttsVoice = ttsPickVoice(); ttsVoicesReady = !!ttsVoice; };
  refresh();
  if(typeof window.speechSynthesis.addEventListener==='function'){
    window.speechSynthesis.addEventListener('voiceschanged', refresh);
  }
}
if(ttsSupported) ttsInit();
function stopSpeaking(){
  if(!ttsSupported) return;
  try{ window.speechSynthesis.cancel(); }catch(e){}
  ttsSpeaking = false;
}
function speakText(text){
  if(!ttsSupported || !text) return;
  try{ window.speechSynthesis.cancel(); }catch(e){}
  const utter = new SpeechSynthesisUtterance(text);
  if(ttsVoice) utter.voice = ttsVoice;
  utter.rate = 0.95;
  utter.pitch = 1.05;
  utter.volume = 1;
  utter.onstart = ()=>{ ttsSpeaking = true; };
  utter.onend   = ()=>{ ttsSpeaking = false; };
  utter.onerror = ()=>{ ttsSpeaking = false; };
  try{ window.speechSynthesis.speak(utter); }catch(e){ ttsSpeaking = false; }
}
const TTS_OPTION_LETTERS = ['A','B','C','D','E','F'];
function buildQuestionScript(npc){
  if(!npc) return '';
  let script = npc.q;
  if(npc.type==='MC' && Array.isArray(npc.opts)){
    const optionsText = npc.opts.map((opt,i)=>`Option ${TTS_OPTION_LETTERS[i]||i+1}: ${opt}.`).join(' ');
    script += '  ' + optionsText;
  } else if(npc.type==='TYPED'){
    script += '  Type your answer.';
  }
  return script;
}
function speakQuestion(npc){
  speakText(buildQuestionScript(npc));
}
function drawSpeakerButton(x,y,r,speaking,accentColor){
  const color = accentColor || '#7C5CFF';
  if(speaking){
    const pulse = 1 + 0.12*Math.sin((typeof fc!=='undefined'?fc:0)*0.25);
    ctx.save();
    ctx.globalAlpha = 0.28;
    ctx.fillStyle = color;
    ctx.beginPath(); ctx.arc(x,y,r*1.35*pulse,0,Math.PI*2); ctx.fill();
    ctx.restore();
  }
  ctx.fillStyle = speaking ? color : '#FFFFFF';
  ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2); ctx.fill();
  ctx.strokeStyle = color; ctx.lineWidth = 3;
  ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2); ctx.stroke();
  ctx.font = Math.round(r*1.15)+'px sans-serif';
  ctx.textAlign='center'; ctx.textBaseline='middle';
  ctx.fillText(speaking ? '\u23F9\uFE0F' : '\u{1F50A}', x, y+1);
  ctx.textBaseline='alphabetic';
}
function speakerButtonHitbox(x,y,r){
  return {x:x-r, y:y-r, w:r*2, h:r*2};
}
