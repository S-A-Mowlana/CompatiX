from pathlib import Path

root = Path(r'D:\xampp\htdocs\CX')

# Exact mojibake sequences found across the site; replace with the intended Unicode text.
replacements = {
    'âœ“': '✓',
    'âœ…': '✅',
    'â€': '”',
    'â€œ': '“',
    'â€™': '’',
    'â€˜': '‘',
    'â€“': '–',
    'â€”': '—',
    'â–£': '▣',
    'âš¡': '⚡',
    'âš ï¸': '⚠️',
    'âšª': 'ℹ️',
    'â—': '●',
    'â†': '←',
    'â†’': '→',
    'â˜°': '⚙',
    'âŒ': '✘',
    'Â©': '©',
    'Â·': '·',
    'ðŸŽ®': '🎮',
    'ðŸ§°': '🧰',
    'ðŸ§ ': '🔧',
    'ðŸ’¡': '💡',
    'ðŸ’»': '💻',
    'ðŸ’¾': '💾',
    'ðŸ’¿': '💿',
    'ðŸ‘»': '👻',
    'ðŸŸ¢': '🟢',
    'ðŸ“Š': '📊',
    'ðŸ“§': '📩',
    'ðŸ¤–': '🤖',
    'ðŸŽ¨': '🎨',
    'ðŸ”§': '💡',
    'ðŸ”´': '⚠️',
    'ðŸŽ': '🖥️',
    'ðŸ›': '📩',
    'ðŸ“±': '📱',
    'ðŸ”“': '🌍',
    'ðŸ‘': '👻',
    'ðŸ”': '💬',
    'ÐŸ': '🎯',
    'ã¢': '¢',
    'ã€': '—',
    'å¤–': '—',
    'â€\u0010': '✓',
    'â€\u0012': '⚙',
    'â€\x9d': '—',
    'ï¸': '️',
    'â€': '—',
    'âœ': '✓',
    'â€': '⚙',
    'â€': '✓',
    'â€\x9d': '—',
    'â€\x9d': '—',
    'â€™': '’',
    'â€': '”',
    'â€œ': '“',
}

# Final pass for the single mojibake sequences visible in the UI.  Keeping this
# explicit avoids a broad re-encoding pass that could damage valid text.
replacements.update({
    'â€”': '—', 'â€“': '–', 'â€™': '’', 'â€œ': '“', 'â€': '”',
    'â†': '←', 'â†’': '→', 'âš¡': '⚡', 'âš ï¸': '⚠️',
    'âœ…': '✅', 'âœ˜': '✘', 'â—': '●', 'â˜€ï¸': '☀️',
    'ðŸŒ™': '🌙', 'ðŸ‘»': '👻', 'ðŸŽ®': '🎮', 'ðŸ”§': '🔧',
    'ðŸ’¡': '💡', 'ðŸ› ï¸': '🛠️', 'ðŸŸ¢': '🟢', 'ðŸŸ¡': '🟡',
    'ðŸ”´': '🔴', 'âŒ': '❌'
})

for path in list(root.rglob('*.html')) + list(root.rglob('*.js')) + list(root.rglob('*.php')):
    try:
        text = path.read_text(encoding='utf-8')
    except UnicodeDecodeError:
        text = path.read_text(encoding='latin-1')
    original = text
    for bad, good in replacements.items():
        text = text.replace(bad, good)
    if original != text:
        path.write_text(text, encoding='utf-8', newline='')

# Ensure every HTML file begins with a UTF-8 meta tag in the head.
for path in root.rglob('*.html'):
    text = path.read_text(encoding='utf-8')
    if '<head>' not in text:
        continue
    head_start = text.index('<head>') + len('<head>')
    head_end = text.index('</head>', head_start)
    head = text[head_start:head_end]
    head = head.replace('<meta charset="UTF-8">', '').replace('<meta charset="UTF-8" />', '').strip()
    head = '    <meta charset="UTF-8">\n' + head
    text = text[:head_start] + head + text[head_end:]
    path.write_text(text, encoding='utf-8', newline='')

print('Fixed mojibake sequences and normalized UTF-8 meta tags.')
