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
  assert.match(doc,/-\s*`tugas_hots_materi`:\s*bank perintah HOTS Guru/);
  assert.match(doc,/-\s*`jawaban_hots_materi`:\s*pengumpulan HOTS siswa/);
  assert.match(doc,/materi\.petunjuk_hots/);
});
test('SOP memerlukan dump terbaru dan mengakui hosting belum deploy',()=>{
  const a=read('docs/AUDIT-KONTRAK-DATABASE-LOKAL.md');
  const agents=read('AGENTS.md');
  assert.match(a,/hosting belum pernah dipakai\/deploy/);
  assert.match(agents,/dump SQL lokal terbaru/);
  assert.match(a,/SQL 0018/);
  assert.match(a,/sla_sks_v3 \(3\)\.sql/);
  assert.match(a,/52 tabel/);
  assert.match(a,/jawaban_hots_materi.*14 kolom/);
  assert.doesNotMatch(a,/dump terbaru diterima\./i);
});
