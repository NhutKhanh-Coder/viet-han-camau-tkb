const fs = require('fs');
const { parse } = require('node:path');
const vm = require('vm');

const content = fs.readFileSync('c:/xampp/htdocs/tkb/admin/ai_studio.php', 'utf8');

// Regex to find script tags (ignoring external src)
const scriptRegex = /<script\b(?![^>]*\bsrc\b)[^>]*>([\s\S]*?)<\/script>/gi;
let match;
let count = 0;

while ((match = scriptRegex.exec(content)) !== null) {
    count++;
    const scriptCode = match[1];
    const lineOffset = content.substring(0, match.index).split('\n').length;
    console.log(`Checking script #${count} starting around line ${lineOffset}...`);
    try {
        // Strip php tags like <?= ... ?> or <?php ... ?> with safe mocks
        const sanitized = scriptCode.replace(/<\?php[\s\S]*?\?>/g, '/*php*/null/*endphp*/')
                                    .replace(/<\?=[\s\S]*?\?>/g, '"mock_val"');
        new vm.Script(sanitized, { filename: `script_${count}.js` });
        console.log(`✔ Script #${count} parsed OK!`);
    } catch (err) {
        console.error(`✖ Syntax Error in Script #${count}:`, err.message);
        console.error(err.stack);
        // Find line number in sanitized
        const lines = scriptCode.split('\n');
        console.error(`Approximate line in file: ${lineOffset}`);
    }
}
