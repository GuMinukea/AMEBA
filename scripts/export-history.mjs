import fs from 'node:fs';
import path from 'node:path';

const source = process.argv[2];
if (!source) {
  console.error('Usage: node scripts/export-history.mjs <Codex rollout JSONL>');
  process.exit(2);
}
const rows = fs.readFileSync(source, 'utf8').split(/\r?\n/).filter(Boolean).map(line => JSON.parse(line));
const messages = [];
for (const row of rows) {
  const p = row.payload;
  if (row.type !== 'response_item' || p?.type !== 'message' || !['user', 'assistant'].includes(p.role)) continue;
  const text = (p.content || []).filter(c => ['input_text', 'output_text'].includes(c.type)).map(c => c.text || '').join('\n').trim();
  if (!text) continue;
  // Remove common credential patterns while preserving surrounding conversation.
  const safe = text.replace(/\b(sk-[A-Za-z0-9_-]{12,}|gh[pousr]_[A-Za-z0-9]{20,}|AKIA[0-9A-Z]{16})\b/g, '[REDACTED SECRET]');
  messages.push(`## ${p.role === 'user' ? 'User' : 'Assistant'} — ${row.timestamp || 'timestamp unavailable'}\n\n${safe}`);
}
const dest = path.resolve('docs/AI_LOG.md');
fs.mkdirSync(path.dirname(dest), { recursive: true });
fs.writeFileSync(dest, `# AI Conversation Log\n\nExported from the Codex rollout session source. Tool plumbing and internal events are omitted; all user messages and assistant text messages are retained in order.\n\n${messages.join('\n\n---\n\n')}\n`, 'utf8');
console.log(`Exported ${messages.length} user/assistant messages to ${dest}`);
