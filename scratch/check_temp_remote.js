const fs = require('fs');
const vm = require('vm');
const content = fs.readFileSync('scratch/temp_remote.php', 'utf8');
const match = /<script\b(?![^>]*\bsrc\b)[^>]*>([\s\S]*?)<\/script>/gi.exec(content);
if (!match) {
    console.log('No script tag found!');
    process.exit(1);
}
const sanitized = match[1]
    .replace(/<\?php[\s\S]*?\?>/g, '/*php*/null/*endphp*/')
    .replace(/<\?=[\s\S]*?\?>/g, '"mock_val"');

try {
    new vm.Script(sanitized);
    console.log('REMOTE SCRIPT IS 100% VALID JAVASCRIPT!');
} catch(e) {
    console.error('Syntax error:', e.message);
    console.error(e.stack);
}
