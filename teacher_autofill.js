(function () {
  var data = [];
  try { data = JSON.parse(document.getElementById('schoolData').textContent) || []; } catch (e) {}
  var no = document.getElementById('sn'), nm = document.getElementById('fn');
  var gr = document.getElementById('gr'), hint = document.getElementById('afHint');
  if (!no || !nm) return;
  var byNo = {}, byName = {};
  data.forEach(function (r) {
    byNo[r.no] = r;
    var k = r.name.toLowerCase().replace(/\s+/g, ' ');
    (byName[k] = byName[k] || []).push(r);
  });
  function fmt(v) {
    var d = v.replace(/\D/g, '').slice(0, 9);
    var o = d.slice(0, 2);
    if (d.length > 2) o += '-' + d.slice(2, 6);
    if (d.length > 6) o += '-' + d.slice(6);
    return o;
  }
  function say(t) { if (hint) hint.textContent = t; }
  function apply(r, from) {
    if (from !== 'no') no.value = r.no;
    nm.value = r.name;
    if (gr && r.grade) gr.value = String(r.grade);
    say('✓ Found in the school list: ' + r.name + ' (' + r.no + ')');
  }
  no.addEventListener('input', function () {
    var f = fmt(no.value);
    if (f !== no.value) no.value = f;
    if (byNo[f]) apply(byNo[f], 'no');
    else if (f.length === 11) say('That Student ID is not on the school list yet. Type the full name to add the student anyway.');
  });
  nm.addEventListener('input', function () {
    var hit = byName[nm.value.trim().toLowerCase().replace(/\s+/g, ' ')];
    if (hit && hit.length === 1) apply(hit[0], 'name');
    else if (hit) say('More than one student has this name. Pick one by Student ID.');
  });
})();
