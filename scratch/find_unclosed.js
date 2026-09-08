const fs = require('fs');
const content = fs.readFileSync(__dirname + '/extracted_1.js', 'utf8');

let stack = [];
let inString = null;
let escape = false;

for (let i = 0; i < content.length; i++) {
    const ch = content[i];
    const line = content.substring(0, i).split('\n').length;

    if (escape) {
        escape = false;
        continue;
    }
    if (ch === '\\') {
        escape = true;
        continue;
    }

    if (inString) {
        if (ch === inString) {
            inString = null;
        }
    } else {
        if (ch === '"' || ch === "'" || ch === '`') {
            inString = ch;
        } else if (ch === '{') {
            stack.push({ line, i });
        } else if (ch === '}') {
            if (stack.length > 0) {
                stack.pop();
            } else {
                console.log(`Extra closing brace at line ${line}`);
            }
        }
    }
}

console.log(`Unclosed braces count: ${stack.length}`);
stack.forEach(s => {
    console.log(`Unclosed '{' at line ${s.line}`);
});
