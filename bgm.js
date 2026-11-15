const BGM_FOLDER = 'bgm/';
const BGM_SCENE_GROUPS = {
  menu: ['menu','username','customizePrompt','loadGame','options','quit'],
  game: ['game','pause'],
  win:  ['win','statsReport'],
};
const BGM_FILES = {
  menu: ['menu.mp3','menu.ogg','theme.mp3','theme.ogg'],
  game: ['gameplay.mp3','gameplay.ogg','game.mp3','level.mp3'],
  win:  ['win.mp3','victory.mp3','fanfare.mp3','fanfare.ogg'],
};
const BGM_VOLUME = 0.35;
const BGM_FADE_MS = 500;
let bgmRealActive = false;
let bgmReady = false;
let bgmTracks = {};
let bgmCurrentMood = null;
let bgmUnlocked = false;
let bgmPendingMood = null;
function bgmMoodForScene(scene){
  for(const mood in BGM_SCENE_GROUPS){
    if(BGM_SCENE_GROUPS[mood].includes(scene)) return mood;
  }
  return 'menu';
}
function bgmActiveForCurrentScene(){
  return !!(bgmCurrentMood && bgmTracks[bgmCurrentMood]);
}
function bgmProbeMood(mood){
  const candidates = BGM_FILES[mood] || [];
  let i = 0;
  return new Promise(resolve=>{
    function tryNext(){
      if(i >= candidates.length){ resolve(null); return; }
      const url = BGM_FOLDER + candidates[i++];
      const audio = new Audio();
      let settled = false;
      const cleanup = ()=>{
        audio.removeEventListener('canplaythrough', onOk);
        audio.removeEventListener('error', onErr);
      };
      const onOk = ()=>{
        if(settled) return; settled = true; cleanup();
        bgmMakeLoop(audio);
        audio.volume = 0;
        resolve(audio);
      };
      const onErr = ()=>{
        if(settled) return; settled = true; cleanup();
        tryNext();
      };
      audio.addEventListener('canplaythrough', onOk, {once:true});
      audio.addEventListener('error', onErr, {once:true});
      audio.preload = 'auto';
      audio.src = url;
      audio.load();
    }
    tryNext();
  });
}
function bgmMakeLoop(audio){
  audio.loop = true;
  audio.addEventListener('ended', ()=>{
    try{ audio.currentTime = 0; audio.play().catch(()=>{}); }catch(e){}
  });
}
async function bgmInit(){
  const moods = Object.keys(BGM_FILES);
  const results = await Promise.all(moods.map(bgmProbeMood));
  moods.forEach((mood,i)=>{ bgmTracks[mood] = results[i]; });
  bgmRealActive = moods.some(mood=>!!bgmTracks[mood]);
  bgmReady = true;
  bgmSetMood(bgmPendingMood || (typeof scene!=='undefined' ? bgmMoodForScene(scene) : 'menu'));
  bgmPendingMood = null;
}
bgmInit();
function bgmFade(audio, to, ms){
  if(!audio) return;
  const from = audio.volume;
  const start = performance.now();
  function step(now){
    const t = Math.min(1, (now-start)/ms);
    audio.volume = from + (to-from)*t;
    if(t < 1) requestAnimationFrame(step);
    else if(to === 0) audio.pause();
  }
  requestAnimationFrame(step);
}
function bgmSetMood(mood){
  if(!bgmReady){ bgmPendingMood = mood; return; }
  if(mood === bgmCurrentMood) return;
  const prevAudio = bgmCurrentMood ? bgmTracks[bgmCurrentMood] : null;
  bgmCurrentMood = mood;
  if(prevAudio) bgmFade(prevAudio, 0, BGM_FADE_MS);
  const audio = bgmTracks[mood];
  if(!audio) return;
  const muted = (typeof musicEnabled !== 'undefined') && !musicEnabled;
  if(bgmUnlocked){
    audio.muted = false;
    audio.volume = 0;
    audio.play().catch(()=>{});
    bgmFade(audio, muted ? 0 : BGM_VOLUME, BGM_FADE_MS);
  } else {
    audio.muted = true;
    audio.volume = muted ? 0 : BGM_VOLUME;
    audio.play().catch(()=>{});
  }
}
function bgmUpdateScene(scene){
  if(!bgmRealActive) return;
  bgmSetMood(bgmMoodForScene(scene));
}
function bgmSetEnabled(enabled){
  const audio = bgmCurrentMood ? bgmTracks[bgmCurrentMood] : null;
  if(!audio) return;
  bgmFade(audio, enabled ? BGM_VOLUME : 0, BGM_FADE_MS);
}
function bgmUnlock(){
  if(bgmUnlocked) return;
  bgmUnlocked = true;
  const audio = bgmCurrentMood ? bgmTracks[bgmCurrentMood] : null;
  if(audio){
    audio.muted = false;
    if(audio.paused) audio.play().catch(()=>{});
  }
}
['pointerdown','keydown','touchstart'].forEach(evt=>{
  document.addEventListener(evt, bgmUnlock, {once:true, capture:true});
});
