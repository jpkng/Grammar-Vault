const TUTORIAL_HIDE_KEY = 'gv_tutorial_hidden';
let tutorialActive = false;
let tutorialPage = 0;
let tutorialPrevState = 'play';
function tutorialHidden(){
  try{ return localStorage.getItem(TUTORIAL_HIDE_KEY) === '1'; }catch(e){ return false; }
}
function tutorialSetHidden(v){
  try{
    if(v) localStorage.setItem(TUTORIAL_HIDE_KEY,'1');
    else  localStorage.removeItem(TUTORIAL_HIDE_KEY);
  }catch(e){}
}
function tutorialShouldAutoShow(){ return !tutorialHidden(); }
function tutorialOpen(){
  if(typeof stopSpeaking==='function') stopSpeaking();
  tutorialPrevState = (gameState==='tutorial') ? tutorialPrevState : gameState;
  tutorialActive = true;
  tutorialPage = 0;
  gameState = 'tutorial';
}
function tutorialClose(){
  tutorialActive = false;
  gameState = tutorialPrevState || 'play';
  if(gameState==='banner' && bannerTimer<=0) bannerTimer = 150;
}
function tutorialNext(){
  if(tutorialPage >= TUTORIAL_PAGES.length-1) tutorialClose();
  else tutorialPage++;
}
function tutorialBack(){
  if(tutorialPage > 0) tutorialPage--;
}
function tutorialHandleKey(e){
  const k = e.key;
  if(k==='Escape') tutorialClose();
  else if(k==='Enter' || k==='e' || k==='E' || k===' ' || k==='ArrowRight') tutorialNext();
  else if(k==='ArrowLeft') tutorialBack();
  if(['ArrowUp','ArrowDown','ArrowLeft','ArrowRight',' '].includes(k)) e.preventDefault();
}
function tutKey(cx,y,w,h,label,hot){
  const x = cx - w/2;
  ctx.fillStyle = '#C9D3E2'; rr(x,y+7,w,h,14); ctx.fill();
  ctx.fillStyle = hot ? '#FFD23F' : '#FFFFFF'; rr(x,y,w,h,14); ctx.fill();
  ctx.strokeStyle = KC.panelEdge; ctx.lineWidth = 3; rr(x,y,w,h,14); ctx.stroke();
  ctx.fillStyle = KC.ink;
  ctx.font = `700 ${Math.round(Math.min(h*0.46,34))}px "Fredoka", sans-serif`;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillText(label, cx, y+h/2+1);
  ctx.textBaseline = 'alphabetic';
}
function tutCaption(txt,cx,y){
  ctx.fillStyle = KC.inkSoft; ctx.font = '700 24px "Fredoka", sans-serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
  ctx.fillText(txt, cx, y);
}
function tutArtWelcome(cx,cy){
  const items = [
    ['\u{1F4AC}','1. Talk','#3EC6E0'],
    ['\u270F\uFE0F','2. Answer','#FF9F45'],
    ['\u{1F6AA}','3. Reach the EXIT','#4CD964'],
  ];
  const gap = 400;
  items.forEach((it,i)=>{
    const x = cx + (i-1)*gap, y = cy - 10 + Math.sin(fc*0.06+i*1.3)*5;
    ctx.fillStyle = it[2]; ctx.beginPath(); ctx.arc(x,y,70,0,Math.PI*2); ctx.fill();
    ctx.strokeStyle = KC.panelEdge; ctx.lineWidth = 5; ctx.beginPath(); ctx.arc(x,y,70,0,Math.PI*2); ctx.stroke();
    ctx.font = '66px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillStyle = '#FFFFFF'; ctx.fillText(it[0], x, y+3);
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = KC.ink; ctx.font = '800 30px "Baloo 2", sans-serif';
    ctx.fillText(it[1], x, cy+112);
    if(i < items.length-1){
      ctx.fillStyle = '#9AA5B8'; ctx.font = '700 56px "Fredoka", sans-serif';
      ctx.textBaseline = 'middle'; ctx.fillText('\u279C', x+gap/2, cy-10); ctx.textBaseline = 'alphabetic';
    }
  });
}
function tutArtMove(cx,cy){
  const wasd = (typeof opts!=='undefined' && opts.controls==='wasd');
  const labels = wasd ? ['W','A','S','D'] : ['\u25B2','\u25C0','\u25BC','\u25B6'];
  const hot = Math.floor(fc/40) % 4;
  const kx = cx - 330, k = 78, g = 12;
  tutKey(kx,        cy-92, k, k, labels[0], hot===0);
  tutKey(kx-(k+g),  cy-92+k+g, k, k, labels[1], hot===1);
  tutKey(kx,        cy-92+k+g, k, k, labels[2], hot===2);
  tutKey(kx+(k+g),  cy-92+k+g, k, k, labels[3], hot===3);
  tutCaption(wasd ? 'WASD or Arrow Keys' : 'Arrow Keys', kx, cy+112);
  const sx = cx + 250;
  const bw = 210, bh = 58, bx = sx+105-bw/2+30, by = cy-112;
  ctx.fillStyle = '#FFFFFF'; rr(bx,by,bw,bh,16); ctx.fill();
  ctx.strokeStyle = KC.panelEdge; ctx.lineWidth = 3; rr(bx,by,bw,bh,16); ctx.stroke();
  ctx.fillStyle = KC.ink; ctx.font = '700 26px "Fredoka", sans-serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillText('\u{1F4AC} Press E', bx+bw/2, by+bh/2+1);
  ctx.textBaseline = 'alphabetic';
  const npcX = bx+bw/2, npcY = cy+10;
  ctx.fillStyle = '#FF9F45'; ctx.beginPath(); ctx.arc(npcX,npcY,44,0,Math.PI*2); ctx.fill();
  ctx.strokeStyle = KC.panelEdge; ctx.lineWidth = 4; ctx.beginPath(); ctx.arc(npcX,npcY,44,0,Math.PI*2); ctx.stroke();
  ctx.fillStyle = KC.ink;
  ctx.beginPath(); ctx.arc(npcX-14,npcY-6,5,0,Math.PI*2); ctx.arc(npcX+14,npcY-6,5,0,Math.PI*2); ctx.fill();
  ctx.lineWidth = 4; ctx.beginPath(); ctx.arc(npcX,npcY+8,14,0.15*Math.PI,0.85*Math.PI); ctx.stroke();
  const plX = npcX - 150 + ((fc*1.2)%40), plY = npcY;
  ctx.fillStyle = '#3A7FFF'; ctx.beginPath(); ctx.arc(plX,plY,34,0,Math.PI*2); ctx.fill();
  ctx.strokeStyle = KC.panelEdge; ctx.lineWidth = 4; ctx.beginPath(); ctx.arc(plX,plY,34,0,Math.PI*2); ctx.stroke();
  tutCaption('Walk close to a character', npcX-60, cy+112);
}
function tutArtTalk(cx,cy){
  const colX = [cx-430, cx, cx+430], keyY = cy-50, capY = cy+112;
  tutKey(colX[0], keyY-12, 104, 104, 'E', Math.floor(fc/40)%2===0);
  tutCaption('Talk / Continue', colX[0], capY);
  const w = 72, g = 12, startX = colX[1] - (4*w+3*g)/2 + w/2;
  for(let i=0;i<4;i++) tutKey(startX+i*(w+g), keyY, w, w, String(i+1), false);
  tutCaption('Pick a multiple-choice answer', colX[1], capY);
  tutKey(colX[2], keyY, 170, 72, 'Enter', false);
  tutCaption('Submit a typed answer', colX[2], capY);
}
function tutArtTools(cx,cy){
  const colX = [cx-400, cx, cx+400], y = cy-10, capY = cy+112;
  ctx.fillStyle = '#3EC6E0'; ctx.font = '800 60px "Baloo 2", sans-serif';
  ctx.textAlign = 'center'; ctx.fillText('Noun', colX[0], y+20);
  const nw = ctx.measureText('Noun').width;
  ctx.strokeStyle = '#3EC6E0'; ctx.lineWidth = 5;
  ctx.beginPath(); ctx.moveTo(colX[0]-nw/2, y+32); ctx.lineTo(colX[0]+nw/2, y+32); ctx.stroke();
  tutCaption('Tap a colored word', colX[0], capY);
  if(typeof drawSpeakerButton==='function') drawSpeakerButton(colX[1], y, 54, false, '#7C5CFF');
  tutCaption('Hear it read aloud', colX[1], capY);
  if(typeof sttSupported!=='undefined' && sttSupported && typeof drawMicButton==='function'){
    drawMicButton(colX[2], y, 54, false, '#7C5CFF');
    tutCaption('Say your answer', colX[2], capY);
  } else {
    ctx.font = '70px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillStyle = '#FFFFFF'; ctx.fillText('\u2328\uFE0F', colX[2], y+4); ctx.textBaseline = 'alphabetic';
    tutCaption('Type your answer', colX[2], capY);
  }
}
function tutArtStars(cx,cy){
  const chips = [['\u2713 2','#E6F9EC','#1E7A3C'],['\u2717 1','#FDEAEA','#B5352F'],['\u2B50 3','#FFD23F','#7A520A']];
  const cw = 104, ch = 54, g = 14, lx = cx - 420;
  const total = chips.length*cw + (chips.length-1)*g;
  chips.forEach((c,i)=>{
    const x = lx - total/2 + i*(cw+g), y = cy-44;
    ctx.fillStyle = c[1]; rr(x,y,cw,ch,16); ctx.fill();
    ctx.strokeStyle = 'rgba(45,48,71,0.2)'; ctx.lineWidth = 3; rr(x,y,cw,ch,16); ctx.stroke();
    ctx.fillStyle = c[2]; ctx.font = '800 28px "Baloo 2", sans-serif';
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(c[0], x+cw/2, y+ch/2+1);
    ctx.textBaseline = 'alphabetic';
  });
  tutCaption('Right, wrong, and score', lx, cy+112);
  const sx = cx + 400, gap = 96;
  for(let i=0;i<3;i++){
    const x = sx + (i-1)*gap, filled = i < 2;
    ctx.fillStyle = filled ? '#FFD23F' : '#E7ECF2';
    drawStarShape(x, cy-14, 42, 42*0.42, 5); ctx.fill();
    ctx.strokeStyle = filled ? '#DB9A17' : '#CBD4E0'; ctx.lineWidth = 3;
    drawStarShape(x, cy-14, 42, 42*0.42, 5); ctx.stroke();
  }
  tutCaption('Fewer misses = more stars', sx, cy+112);
}
const TUTORIAL_PAGES = [
  { title:'Welcome to Grammar Vault!', icon:'\u{1F989}', color:'#7C5CFF', art:tutArtWelcome,
    lines:()=>[
      'Explore each level and meet the characters who live there.',
      'Answer their grammar, spelling, vocabulary, and reading questions.',
      'Each character asks 3 questions. Get enough right to master them!',
      'Master everyone and reach the accuracy goal, then run to the glowing EXIT!',
    ]},
  { title:'How to Move', icon:'\u{1F3C3}', color:'#4CD964', art:tutArtMove,
    lines:()=>{
      const wasd = (typeof opts!=='undefined' && opts.controls==='wasd');
      return [
        `Use ${wasd?'W, A, S, D or the Arrow Keys':'the Arrow Keys'} to walk around the map.`,
        'Walls and characters block your way, so find a path around them.',
        'Walk close to a character until a \u{1F4AC} Press E bubble pops up above them.',
        'Press P or Esc any time to pause.',
      ];
    }},
  { title:'Talk & Answer', icon:'\u{1F4AC}', color:'#3EC6E0', art:tutArtTalk,
    lines:()=>[
      'Press E (or click) to start talking, and again to move on after each message.',
      'Each character asks 3 questions in a row. Look for the \u2713 or \u2717 dots at the top.',
      'You have 30 seconds for each question (1:30 per character). Watch the \u23F1 timer \u2014 if it runs out, the answer counts as wrong.',
      'Multiple choice: press 1\u20134 or click your answer.',
      'Typed questions: type your answer, then press Enter or click Submit.',
    ]},
  { title:'Handy Helpers', icon:'\u{1F9F0}', color:'#FF9F45', art:tutArtTools,
    lines:()=>{
      const out = [
        'Colored, underlined grammar words open a kid-friendly definition when you click them.',
        'Click the \u{1F50A} speaker to hear a question read aloud, as many times as you like.',
      ];
      if(typeof sttSupported!=='undefined' && sttSupported) out.push('On typed questions, click the \u{1F3A4} microphone to say your answer instead of typing it.');
      return out;
    }},
  { title:'Stars & the Exit', icon:'\u2B50', color:'#FFB800', art:tutArtStars,
    lines:()=>[
      'Get 2 of 3 right to master a character. Fewer than that? They\'ll ask 3 new questions.',
      'Points: 100 per answer (150 if typed), +10 per answer in a row, +100 for a perfect round.',
      'Keep your \u{1F3AF} accuracy above the level goal to open the EXIT. Low? Characters offer Review Rounds.',
      'Pause with P to save your game!',
    ]},
];
function drawTutorial(){
  clickables = [];
  ctx.fillStyle = 'rgba(45,48,71,0.62)'; ctx.fillRect(0,0,VW,VH);
  const page = TUTORIAL_PAGES[tutorialPage];
  const pw = 1360, ph = 860, px = (VW-pw)/2, py = (VH-ph)/2;
  ctx.save();
  ctx.shadowColor = 'rgba(45,48,71,0.35)'; ctx.shadowBlur = 0; ctx.shadowOffsetY = 10;
  ctx.fillStyle = '#FFFFFF'; rr(px,py,pw,ph,32); ctx.fill();
  ctx.restore();
  ctx.strokeStyle = page.color; ctx.lineWidth = 8; rr(px,py,pw,ph,32); ctx.stroke();
  ctx.fillStyle = page.color; ctx.beginPath(); ctx.arc(px+110,py+92,46,0,Math.PI*2); ctx.fill();
  ctx.font = '48px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillStyle = '#FFFFFF'; ctx.fillText(page.icon, px+110, py+94);
  ctx.textBaseline = 'alphabetic';
  ctx.textAlign = 'left'; ctx.fillStyle = KC.ink; ctx.font = '800 56px "Baloo 2", sans-serif';
  ctx.fillText(page.title, px+180, py+110);
  ctx.fillStyle = '#9AA5B8'; ctx.font = '700 22px "Fredoka", sans-serif'; ctx.textAlign = 'right';
  ctx.fillText(`${tutorialPage+1} / ${TUTORIAL_PAGES.length}`, px+pw-110, py+104);
  const xR = 26, xCX = px+pw-52, xCY = py+92, xHi = optHover===72;
  ctx.fillStyle = xHi ? '#E14F4F' : '#FF6B6B'; ctx.beginPath(); ctx.arc(xCX,xCY,xR,0,Math.PI*2); ctx.fill();
  ctx.strokeStyle = '#FFFFFF'; ctx.lineWidth = 5; ctx.lineCap = 'round';
  ctx.beginPath(); ctx.moveTo(xCX-9,xCY-9); ctx.lineTo(xCX+9,xCY+9); ctx.moveTo(xCX+9,xCY-9); ctx.lineTo(xCX-9,xCY+9); ctx.stroke();
  ctx.lineCap = 'butt';
  reg(xCX-xR, xCY-xR, xR*2, xR*2, tutorialClose, 72);
  const artX = px+60, artY = py+180, artW = pw-120, artH = 290;
  ctx.fillStyle = '#F3FAFF'; rr(artX,artY,artW,artH,24); ctx.fill();
  ctx.strokeStyle = 'rgba(45,48,71,0.10)'; ctx.lineWidth = 3; rr(artX,artY,artW,artH,24); ctx.stroke();
  page.art(px+pw/2, artY+artH/2-8);
  ctx.textAlign = 'left'; ctx.font = '600 26px "Fredoka", sans-serif';
  let ty = py+520;
  page.lines().forEach(line=>{
    ctx.fillStyle = page.color; ctx.beginPath(); ctx.arc(px+84,ty-9,7,0,Math.PI*2); ctx.fill();
    ctx.fillStyle = KC.ink; ctx.font = '600 26px "Fredoka", sans-serif'; ctx.textAlign = 'left';
    wrapT(line, pw-200).forEach(l=>{ ctx.fillText(l, px+108, ty); ty += 34; });
    ty += 8;
  });
  const dotY = py+ph-146;
  TUTORIAL_PAGES.forEach((p,i)=>{
    const dx = px+pw/2 - (TUTORIAL_PAGES.length-1)*14 + i*28;
    ctx.fillStyle = i===tutorialPage ? page.color : '#D5DCE8';
    ctx.beginPath(); ctx.arc(dx,dotY,i===tutorialPage?9:7,0,Math.PI*2); ctx.fill();
  });
  const cbX = px+60, cbY = py+ph-110, cbS = 36, hidden = tutorialHidden();
  ctx.fillStyle = hidden ? '#7C5CFF' : '#FFFFFF'; rr(cbX,cbY,cbS,cbS,9); ctx.fill();
  ctx.strokeStyle = hidden ? '#5B3FE0' : '#9AA5B8'; ctx.lineWidth = 4; rr(cbX,cbY,cbS,cbS,9); ctx.stroke();
  if(hidden){
    ctx.strokeStyle = '#FFFFFF'; ctx.lineWidth = 5; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    ctx.beginPath(); ctx.moveTo(cbX+9,cbY+19); ctx.lineTo(cbX+16,cbY+26); ctx.lineTo(cbX+28,cbY+11); ctx.stroke();
    ctx.lineCap = 'butt'; ctx.lineJoin = 'miter';
  }
  ctx.fillStyle = KC.inkSoft; ctx.font = '600 22px "Fredoka", sans-serif'; ctx.textAlign = 'left';
  ctx.fillText("Don't show this on new games", cbX+cbS+14, cbY+cbS-9);
  reg(cbX-6, cbY-6, 420, cbS+12, ()=>tutorialSetHidden(!tutorialHidden()), 73);
  const bh = 72, by = py+ph-124;
  const last = tutorialPage === TUTORIAL_PAGES.length-1;
  const nextW = last ? 340 : 280, nextX = px+pw-60-nextW;
  drawChunkyButton(nextX, by, nextW, bh, last?"Let's Play!":'Next', last?'\u{1F680}':'\u27A1\uFE0F', KC.play, KC.playDark, optHover===70);
  reg(nextX, by, nextW, bh+10, tutorialNext, 70);
  if(tutorialPage > 0){
    const backW = 220, backX = nextX-24-backW;
    drawChunkyButton(backX, by, backW, bh, 'Back', '\u2B05\uFE0F', KC.opts, KC.optsDark, optHover===71);
    reg(backX, by, backW, bh+10, tutorialBack, 71);
  }
  ctx.textAlign = 'left';
}
