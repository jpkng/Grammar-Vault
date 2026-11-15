const GRAMMAR_GLOSSARY = {
  noun:        {label:'Noun',        icon:'\u{1F3F7}\uFE0F', color:'#3EC6E0',
                definition:'A noun is a word that names a person, animal, place, or thing.',
                example:'dog, school, Maria, happiness'},
  pronoun:     {label:'Pronoun',     icon:'\u{1F449}', color:'#4CD964',
                definition:'A pronoun is a word used in place of a noun, like when talking about someone without saying their name again.',
                example:'he, she, it, they, we'},
  verb:        {label:'Verb',        icon:'\u{1F3C3}', color:'#FF6B6B',
                definition:'A verb is an action word — it tells what someone or something does, or shows a state of being.',
                example:'run, jump, is, think'},
  adjective:   {label:'Adjective',   icon:'\u{1F3A8}', color:'#7C5CFF',
                definition:'An adjective is a word that describes a noun — it tells more about what something is like.',
                example:'big, blue, happy, three'},
  adverb:      {label:'Adverb',      icon:'\u26A1', color:'#FF9F45',
                definition:'An adverb describes a verb, adjective, or another adverb — it often tells how, when, or where something happens.',
                example:'quickly, yesterday, very, there'},
  preposition: {label:'Preposition', icon:'\u{1F4CD}', color:'#FF6FB5',
                definition:'A preposition shows how a noun relates to another word, often about place, time, or direction.',
                example:'in, on, under, before, with'},
  conjunction: {label:'Conjunction', icon:'\u{1F517}', color:'#FFD23F',
                definition:'A conjunction is a word that joins other words, phrases, or sentences together.',
                example:'and, but, or, because'},
  interjection:{label:'Interjection',icon:'\u2757', color:'#FF6B6B',
                definition:'An interjection is a short word or phrase that shows strong feeling or emotion, often followed by an exclamation mark.',
                example:'Wow!, Ouch!, Hooray!'},
  article:     {label:'Article',     icon:'\u{1F524}', color:'#9AA5B8',
                definition:'An article is a small word placed before a noun to show whether it means something specific or general.',
                example:'a, an, the'},
  subject:     {label:'Subject',     icon:'\u{1F3AF}', color:'#3EC6E0',
                definition:'The subject of a sentence is who or what the sentence is about — usually the noun or pronoun doing the action.',
                example:'In "The dog barked," dog is the subject.'},
  predicate:   {label:'Predicate',   icon:'\u{1F4AC}', color:'#7C5CFF',
                definition:'The predicate is the part of a sentence that tells what the subject does or is — it always includes the verb.',
                example:'In "The dog barked," barked is the predicate.'},
  sentence:    {label:'Sentence',    icon:'\u{1F4DD}', color:'#4CD964',
                definition:'A sentence is a group of words that expresses a complete thought, with a subject and a predicate.',
                example:'"Birds fly." is a complete sentence.'},
  singular:    {label:'Singular',    icon:'\u0031\uFE0F\u20E3', color:'#3EC6E0',
                definition:'Singular means just one person, animal, place, or thing.',
                example:'cat, child, box'},
  plural:      {label:'Plural',      icon:'\u{1F46F}', color:'#4CD964',
                definition:'Plural means more than one. Most nouns add -s or -es to show it.',
                example:'cats, boxes, children'},
  regular:     {label:'Regular',     icon:'\u2705', color:'#4CD964',
                definition:'A regular word follows the usual pattern. Regular plurals add -s or -es, and regular past-tense verbs add -ed.',
                example:'cat -> cats, walk -> walked'},
  irregular:   {label:'Irregular',   icon:'\u{1F9E9}', color:'#FF9F45',
                definition:'An irregular word does NOT follow the usual pattern, so you have to remember its special form.',
                example:'child -> children, run -> ran'},
  tense:       {label:'Tense',       icon:'\u23F0', color:'#7C5CFF',
                definition:'Tense tells WHEN something happens: in the past, right now, or in the future.',
                example:'walked (past), walks (now), will walk (future)'},
  statement:   {label:'Statement',   icon:'\u{1F4E2}', color:'#3EC6E0',
                definition:'A statement is a sentence that tells something. It ends with a period.',
                example:'I have a pet.'},
  punctuation: {label:'Punctuation', icon:'\u2753', color:'#FF9F45',
                definition:'Punctuation marks are the little symbols that help sentences make sense.',
                example:'. , ? ! \' "'},
  period:      {label:'Period',      icon:'\u{1F534}', color:'#FF6B6B',
                definition:'A period (.) is the dot at the end of a sentence that tells something.',
                example:'The sun is hot.'},
  comma:       {label:'Comma',       icon:'\u{1F4A7}', color:'#FF9F45',
                definition:'A comma (,) is a small pause mark. It separates items in a list or parts of a sentence.',
                example:'I like apples, pears, and plums.'},
  apostrophe:  {label:'Apostrophe',  icon:'\u{1F4CC}', color:'#7C5CFF',
                definition:'An apostrophe (\') shows that letters are missing in a contraction, or that something belongs to someone.',
                example:"don't, Sam's hat"},
  contraction: {label:'Contraction', icon:'\u2702\uFE0F', color:'#FF6FB5',
                definition:'A contraction squishes two words into one. An apostrophe takes the place of the missing letters.',
                example:"do not -> don't, it is -> it's"},
  possessive:  {label:'Possessive',  icon:'\u{1F381}', color:'#FFB800',
                definition:'A possessive word shows who owns something. Nouns usually add \'s to show it.',
                example:"the dog's bone, Maria's book"},
  quotation:   {label:'Quotation Marks', icon:'\u{1F4AC}', color:'#3EC6E0',
                definition:'Quotation marks (" ") go around the exact words someone says out loud.',
                example:'"I am hungry," said Mia.'},
  exclamation: {label:'Exclamation', icon:'\u2757', color:'#FF6B6B',
                definition:'An exclamation shows strong feeling and ends with an exclamation mark (!).',
                example:'What a great day!'},
  capital:     {label:'Capital Letter', icon:'\u{1F520}', color:'#4CD964',
                definition:'A capital letter is a big (uppercase) letter. Use one to start a sentence and for names of people and places.',
                example:'Texas, Maria, The dog barked.'},
  vowel:       {label:'Vowel',       icon:'\u{1F170}\uFE0F', color:'#FF6B6B',
                definition:'The vowels are the letters a, e, i, o, and u. Every syllable has at least one vowel sound.',
                example:'a, e, i, o, u'},
  consonant:   {label:'Consonant',   icon:'\u{1F171}\uFE0F', color:'#3EC6E0',
                definition:'A consonant is any letter that is not a vowel, like b, c, d, f, and g.',
                example:'b, d, m, t, s'},
  syllable:    {label:'Syllable',    icon:'\u{1F44F}', color:'#FF9F45',
                definition:'A syllable is one beat in a word. You can clap once for each syllable.',
                example:'but-ter-fly has 3 syllables'},
  prefix:      {label:'Prefix',      icon:'\u23EE\uFE0F', color:'#7C5CFF',
                definition:'A prefix is a group of letters added to the START of a word to change its meaning.',
                example:'un + happy = unhappy'},
  suffix:      {label:'Suffix',      icon:'\u23ED\uFE0F', color:'#FF6FB5',
                definition:'A suffix is a group of letters added to the END of a word to change its meaning.',
                example:'help + ful = helpful'},
  compound:    {label:'Compound Word', icon:'\u{1F9F1}', color:'#FFB800',
                definition:'A compound word is made by putting two smaller words together.',
                example:'sun + flower = sunflower'},
  synonym:     {label:'Synonym',     icon:'\u{1F91D}', color:'#4CD964',
                definition:'A synonym is a word that means the same, or almost the same, as another word.',
                example:'big = large, happy = glad'},
  antonym:     {label:'Antonym',     icon:'\u2194\uFE0F', color:'#FF6B6B',
                definition:'An antonym is a word that means the opposite of another word.',
                example:'hot <-> cold, up <-> down'},
  homophone:   {label:'Homophone',   icon:'\u{1F442}', color:'#3EC6E0',
                definition:'Homophones sound the same but have different spellings and meanings.',
                example:'see / sea, two / too / to'},
  simile:      {label:'Simile',      icon:'\u{1F50E}', color:'#FF9F45',
                definition:'A simile compares two things using the word "like" or "as".',
                example:'as quick as a fox'},
  metaphor:    {label:'Metaphor',    icon:'\u{1F3AD}', color:'#7C5CFF',
                definition:'A metaphor says one thing IS another thing to make a picture in your mind.',
                example:'The classroom was a zoo.'},
  alliteration:{label:'Alliteration',icon:'\u{1F5E3}\uFE0F', color:'#FF6FB5',
                definition:'Alliteration is when several words close together start with the same sound.',
                example:'Peter picked purple peppers.'},
  fact:        {label:'Fact',        icon:'\u{1F9EA}', color:'#3EC6E0',
                definition:'A fact is something that can be proven true.',
                example:'A week has seven days.'},
  opinion:     {label:'Opinion',     icon:'\u{1F4AD}', color:'#FF9F45',
                definition:'An opinion tells what someone thinks or feels. It cannot be proven true or false.',
                example:'Pizza is the tastiest food.'},
  setting:     {label:'Setting',     icon:'\u{1F3DE}\uFE0F', color:'#4CD964',
                definition:'The setting is WHEN and WHERE a story takes place.',
                example:'a quiet farm in the summer'},
  plot:        {label:'Plot',        icon:'\u{1F4D6}', color:'#7C5CFF',
                definition:'The plot is the series of events that happen in a story, from beginning to end.',
                example:'problem -> events -> solution'},
  theme:       {label:'Theme',       icon:'\u{1F4A1}', color:'#FFB800',
                definition:'The theme is the big message or lesson of a story.',
                example:'Kindness is rewarded.'},
  sequence:    {label:'Sequence',    icon:'\u{1F522}', color:'#FF6B6B',
                definition:'A sequence is the order things happen in, like first, next, then, and last.',
                example:'First, wake up. Next, brush teeth.'},
};
const GLOSSARY_ALIASES = {
  nouns:'noun', pronouns:'pronoun', verbs:'verb', adjectives:'adjective',
  adverbs:'adverb', prepositions:'preposition', conjunctions:'conjunction',
  interjections:'interjection', articles:'article', subjects:'subject',
  predicates:'predicate', sentences:'sentence',
  plurals:'plural', singulars:'singular', tenses:'tense', statements:'statement',
  periods:'period', commas:'comma', apostrophes:'apostrophe', contractions:'contraction',
  quotations:'quotation', exclamations:'exclamation', capitals:'capital', capitalized:'capital',
  vowels:'vowel', consonants:'consonant', syllables:'syllable', prefixes:'prefix', suffixes:'suffix',
  compounds:'compound', synonyms:'synonym', antonyms:'antonym', opposite:'antonym', opposites:'antonym',
  homophones:'homophone', similes:'simile', metaphors:'metaphor', facts:'fact', opinions:'opinion',
  settings:'setting', plots:'plot', themes:'theme', sequences:'sequence',
};
function glossaryLookup(word){
  if(!word) return null;
  const clean = word.toLowerCase().replace(/[^a-z']/g,'');
  if(GRAMMAR_GLOSSARY[clean]) return {term:clean, ...GRAMMAR_GLOSSARY[clean]};
  if(GLOSSARY_ALIASES[clean]) return {term:GLOSSARY_ALIASES[clean], ...GRAMMAR_GLOSSARY[GLOSSARY_ALIASES[clean]]};
  return null;
}
function splitGlossaryToken(tok){
  const m = tok.match(/^([^A-Za-z']*)([A-Za-z']+)([^A-Za-z']*)$/);
  if(!m) return {pre:'', core:tok, post:''};
  return {pre:m[1], core:m[2], post:m[3]};
}
function renderGlossaryText(ctx, text, x, y, maxWidth, style){
  const font = style.font;
  const fontSize = style.fontSize || 24;
  const lineHeight = style.lineHeight || Math.round(fontSize*1.4);
  const color = style.color || '#2D3047';
  const underlineColor = style.underlineColor || color;
  ctx.font = font;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'alphabetic';
  const words = String(text).split(/\s+/).filter(Boolean);
  const hitRegions = [];
  let cx = x, cy = y, lines = 1;
  const spaceW = ctx.measureText(' ').width;
  words.forEach(word=>{
    const {pre,core,post} = splitGlossaryToken(word);
    const preW = pre ? ctx.measureText(pre).width : 0;
    const coreW = core ? ctx.measureText(core).width : 0;
    const postW = post ? ctx.measureText(post).width : 0;
    const totalW = preW+coreW+postW;
    if(cx!==x && cx+totalW>x+maxWidth){
      cx = x; cy += lineHeight; lines++;
    }
    ctx.fillStyle = color;
    if(pre){ctx.fillText(pre,cx,cy); cx+=preW;}
    const hit = glossaryLookup(core);
    if(hit){
      ctx.fillStyle = hit.color;
      ctx.fillText(core,cx,cy);
      ctx.strokeStyle = hit.color; ctx.lineWidth = Math.max(2,fontSize*0.07);
      ctx.beginPath();
      ctx.moveTo(cx, cy+fontSize*0.16);
      ctx.lineTo(cx+coreW, cy+fontSize*0.16);
      ctx.stroke();
      hitRegions.push({x:cx, y:cy-fontSize*0.85, w:coreW, h:fontSize*1.1, term:hit.term});
    } else {
      ctx.fillStyle = color;
      ctx.fillText(core,cx,cy);
    }
    cx += coreW;
    if(post){ctx.fillStyle=color;ctx.fillText(post,cx,cy);cx+=postW;}
    cx += spaceW;
  });
  return {hitRegions, lines, height:lines*lineHeight, endY:cy};
}
function drawGlossaryPopup(ctx, popupState, VW, VH){
  const entry = GRAMMAR_GLOSSARY[popupState.term];
  if(!entry) return null;
  const pw = 460;
  ctx.font = '600 20px "Fredoka", sans-serif';
  const words = entry.definition.split(' ');
  let line='', defLines=[];
  words.forEach(w=>{
    const test=line?line+' '+w:w;
    if(ctx.measureText(test).width > pw-56){defLines.push(line);line=w;}
    else line=test;
  });
  if(line)defLines.push(line);
  const hasExample = !!entry.example;
  let exampleLines=[];
  if(hasExample){
    ctx.font='600 16px "Fredoka", sans-serif';
    const exWords=('Example: '+entry.example).split(' ');
    let el='';
    exWords.forEach(w=>{
      const test=el?el+' '+w:w;
      if(ctx.measureText(test).width > pw-56){exampleLines.push(el);el=w;}
      else el=test;
    });
    if(el)exampleLines.push(el);
  }
  const ph = 118 + defLines.length*28 + (hasExample?exampleLines.length*22+16:0);
  let px = popupState.x - pw/2;
  let py = popupState.y - ph - 18;
  px = Math.max(20, Math.min(VW-pw-20, px));
  if(py < 20) py = popupState.y + 30;
  py = Math.max(20, Math.min(VH-ph-20, py));
  ctx.save();
  ctx.shadowColor='rgba(45,48,71,0.35)';ctx.shadowBlur=0;ctx.shadowOffsetY=6;
  ctx.fillStyle='#FFFFFF';
  chRoundRectPath(ctx,px,py,pw,ph,20);ctx.fill();
  ctx.restore();
  ctx.strokeStyle=entry.color;ctx.lineWidth=5;
  chRoundRectPath(ctx,px,py,pw,ph,20);ctx.stroke();
  const badgeCX=px+46,badgeCY=py+46,badgeR=28;
  ctx.fillStyle=entry.color;ctx.beginPath();ctx.arc(badgeCX,badgeCY,badgeR,0,Math.PI*2);ctx.fill();
  ctx.font='24px sans-serif';ctx.textAlign='center';ctx.textBaseline='middle';
  ctx.fillText(entry.icon,badgeCX,badgeCY+1);
  ctx.textBaseline='alphabetic';
  ctx.fillStyle=entry.color;ctx.font='800 28px "Baloo 2", sans-serif';ctx.textAlign='left';
  ctx.fillText(entry.label,px+86,py+54);
  ctx.fillStyle='#2D3047';ctx.font='600 20px "Fredoka", sans-serif';
  defLines.forEach((l,i)=>ctx.fillText(l,px+28,py+100+i*28));
  if(hasExample){
    const ey = py+100+defLines.length*28+10;
    ctx.fillStyle='#9AA5B8';ctx.font='600 16px "Fredoka", sans-serif';
    exampleLines.forEach((l,i)=>ctx.fillText(l,px+28,ey+18+i*22));
  }
  ctx.fillStyle='#9AA5B8';ctx.font='500 14px "Fredoka", sans-serif';ctx.textAlign='right';
  ctx.fillText('tap anywhere to close',px+pw-20,py+ph-14);
  ctx.textAlign='left';
  return {x:px,y:py,w:pw,h:ph};
}
