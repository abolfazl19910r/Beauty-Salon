// لینک اشتراک اپ ntfy: ntfy://<host>/<topic> (برای سرور http باید secure=false داشته باشد) — docs.ntfy.sh/subscribe/phone
export function ntfySubscribeLink(server: string, topic: string, display: string): string | null {
  const match = /^(https?):\/\/([^/\s]+)\/?$/i.exec(server.trim());
  if (!match || !topic) return null;
  const params = [`display=${encodeURIComponent(display)}`];
  if (match[1].toLowerCase() === 'http') params.push('secure=false');
  return `ntfy://${match[2]}/${topic}?${params.join('&')}`;
}

export function randomTopic(): string {
  const chars = 'abcdefghijkmnpqrstuvwxyz23456789';
  let suffix = '';
  for (let i = 0; i < 12; i++) suffix += chars[Math.floor(Math.random() * chars.length)];
  return `mahru-probe-${suffix}`;
}
