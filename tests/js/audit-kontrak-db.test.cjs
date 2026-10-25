'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const read = file => fs.readFileSync(path.join(root,file), 'utf8');
test('QA SQL audit hanya SELECT (tidak mutasi)', () => {
  const src=read('database/sql/qa/audit_kontrak_database_readonly.sql')
    .replace(/^\s*--.*$/gm,'');
  const statements=src.split(';').map(s=>s.trim()).filter(Boolean);
  assert.equal(statements.length,5);
  statements.forEach(s=>assert.match(s,/^SELECT\b/i));
  assert.doesNotMatch(src,/\b(?:CREATE|DROP|ALTER|TRUNCATE|INSERT|UPDATE|DELETE|REPLACE)\b/i);
});
test('Master memisahkan bank HOTS Guru dan jawaban siswa',()=>{
  const doc=read('docs/10-DATABASE.md');
  assert.match(doc,/\|\s*Konten\s*\|[^\n]*tugas_hots_materi/);
  assert.match(doc,/\|\s*Belajar\s*\|[^\n]*jawaban_hots_materi/);
  assert.match(doc,/materi\.petunjuk_hots/);
});
test('SOP memerlukan dump terbaru dan mengakui hosting belum deploy',()=>{
  const a=read('docs/AUDIT-KONTRAK-DATABASE-LOKAL.md');
  const agents=read('AGENTS.md');
  assert.match(a,/belum pernah diunggah ke hosting/);
  assert.match(agents,/dump SQL lokal terbaru/);
  assert.match(a,/SQL 0018/);
});
